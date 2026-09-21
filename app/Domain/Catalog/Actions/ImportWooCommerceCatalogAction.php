<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Enums\ProductPublicationState;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Lunar\Core\Contracts\AttributeCache;
use Lunar\Core\FieldTypes\TranslatedText;
use Lunar\Core\Models\Channel;
use Lunar\Core\Models\Currency;
use Lunar\Core\Models\CustomerGroup;
use Lunar\Core\Models\ProductType;
use Lunar\Core\Models\TaxClass;
use RuntimeException;
use Throwable;

final class ImportWooCommerceCatalogAction
{
    public function __construct(
        private readonly SyncProductConfigurationAction $syncProductConfiguration,
        private readonly SyncProductDownloadsAction $syncProductDownloads,
    ) {}

    /**
     * Import variable WooCommerce products and their variations.
     *
     * @param  array{download_remote_assets?: bool, assets_path?: ?string, source_type?: string}  $options
     * @return array{products: int, variants: int, downloads: int, warnings: list<string>}
     */
    public function handle(string $path, array $options = []): array
    {
        $rows = $this->readRows($path);
        $parents = [];
        $variations = [];

        foreach ($rows as $row) {
            $type = Str::lower(trim((string) ($row['Type'] ?? '')));

            if (Str::startsWith($type, 'variable')) {
                $parents[(string) $row['SKU']] = $row;
            } elseif (Str::startsWith($type, 'variation')) {
                $variations[(string) $row['Parent']][] = $row;
            } else {
                throw new InvalidArgumentException("Unsupported WooCommerce product type [{$type}].");
            }
        }

        if ($parents === []) {
            throw new InvalidArgumentException('The WooCommerce export does not contain any variable products.');
        }

        $this->assertPrerequisites();
        $warnings = [];
        $downloadCount = 0;
        $variantCount = 0;

        foreach ($parents as $parentSku => $parentRow) {
            $parentVariations = $variations[$parentSku] ?? [];
            $image = $this->resolveImage(
                $this->firstUrl((string) ($parentRow['Images'] ?? '')),
                $parentSku,
                $options,
                $warnings,
            );
            $product = $this->upsertProduct($parentRow, $image, $options);
            $variantRows = $this->variantRows($product, $parentRow, $parentVariations);

            $this->syncProductConfiguration->handle($product, [
                'catalog_category' => $this->primaryCategory((string) ($parentRow['Categories'] ?? '')),
                'catalog_platform' => trim((string) ($parentRow['Attribute 2 value(s)'] ?? '')),
                'default_price' => $variantRows[0]['price'] ?? null,
                'default_compare_at_price' => $variantRows[0]['compare_at_price'] ?? null,
                'variants_data' => $variantRows,
                'fulfillment_summary' => ['type' => 'none'],
            ]);

            $downloadResult = $this->syncDownloads(
                $product->fresh(),
                $parentVariations,
                $options,
                $warnings,
            );
            $downloadCount += $downloadResult;
            $variantCount += count($variantRows);

            $this->syncStorefrontVisibility($product->fresh());
            $this->syncCategories((string) ($parentRow['Categories'] ?? ''));
        }

        return [
            'products' => count($parents),
            'variants' => $variantCount,
            'downloads' => $downloadCount,
            'warnings' => $warnings,
        ];
    }

    /** @return list<array<string, string>> */
    private function readRows(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException("WooCommerce export [{$path}] does not exist or is not readable.");
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException("Unable to open WooCommerce export [{$path}].");
        }

        try {
            $headers = fgetcsv($handle, null, ',', '"', '\\');

            if ($headers === false) {
                throw new InvalidArgumentException('The WooCommerce export is empty.');
            }

            $headers = array_map(static fn (mixed $header): string => trim((string) $header), $headers);
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]) ?? $headers[0];

            foreach (['ID', 'Type', 'SKU', 'Name'] as $requiredHeader) {
                if (! in_array($requiredHeader, $headers, true)) {
                    throw new InvalidArgumentException("The WooCommerce export is missing the [{$requiredHeader}] column.");
                }
            }

            $rows = [];

            while (($values = fgetcsv($handle, null, ',', '"', '\\')) !== false) {
                if (count(array_filter($values, static fn (mixed $value): bool => trim((string) $value) !== '')) === 0) {
                    continue;
                }

                $values = array_pad($values, count($headers), '');
                $rows[] = array_map(
                    static fn (mixed $value): string => trim((string) $value),
                    array_combine($headers, array_slice($values, 0, count($headers))) ?: [],
                );
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    private function assertPrerequisites(): void
    {
        app(AttributeCache::class)->flush();

        if (! ProductType::query()->where('name', 'Software')->exists()) {
            throw new RuntimeException('The Software product type is missing. Run php artisan migrate --force first.');
        }

        if (Currency::getDefault() === null || TaxClass::query()->where('default', true)->first() === null) {
            throw new RuntimeException('The default currency or tax class is missing. Run php artisan migrate --force first.');
        }

        if (Channel::query()->where('handle', 'webstore')->first() === null || CustomerGroup::query()->where('handle', 'retail')->first() === null) {
            throw new RuntimeException('The webstore channel or retail customer group is missing. Run php artisan migrate --force first.');
        }
    }

    /** @param  array<string, string>  $row */
    private function upsertProduct(array $row, ?string $image, array $options): Product
    {
        $wooId = trim((string) ($row['ID'] ?? ''));
        $sku = trim((string) ($row['SKU'] ?? ''));
        $name = $this->cleanText((string) ($row['Name'] ?? ''));

        if ($wooId === '' || $sku === '' || $name === '') {
            throw new InvalidArgumentException('Every variable WooCommerce product must have an ID, SKU, and name.');
        }

        $product = Product::query()->get()->first(
            fn (Product $candidate): bool => (string) $candidate->attr('legacy_id') === $wooId,
        );

        $product?->loadMissing('urls');

        $published = $this->isTruthy($row['Published'] ?? '');
        $description = $this->cleanText((string) ($row['Description'] ?? ''));
        $shortDescription = $this->cleanText((string) ($row['Short description'] ?? ''));
        $category = $this->primaryCategory((string) ($row['Categories'] ?? ''));
        $sourceType = (string) ($options['source_type'] ?? 'local_developer');

        if (! in_array($sourceType, ['local_developer', 'global_partner'], true)) {
            throw new InvalidArgumentException('The source type must be local_developer or global_partner.');
        }

        $attributes = $product?->getAttribute('attribute_data');
        $attributes = $attributes instanceof Collection ? $attributes : collect($attributes ?: []);
        $translated = fn (mixed $value): TranslatedText => new TranslatedText(collect(['en' => (string) $value]));
        $attributes->put('source_system', $translated('woocommerce'));
        $attributes->put('legacy_id', $translated($wooId));
        $attributes->put('source_sku', $translated($sku));
        $attributes->put('source_image_url', $translated($this->firstUrl((string) ($row['Images'] ?? ''))));
        $attributes->put('category', $translated($category));
        $attributes->put('cover_url', $translated($image ?? $this->firstUrl((string) ($row['Images'] ?? ''))));
        $attributes->put('rating', $translated('0'));
        $attributes->put('ratings_count', $translated('0'));

        $payload = [
            'product_type_id' => ProductType::query()->where('name', 'Software')->value('id'),
            'status' => $published ? 'published' : 'draft',
            'publication_state' => $published ? ProductPublicationState::Published->value : ProductPublicationState::Draft->value,
            'source_type' => $sourceType,
            'support_owner' => 'merebhub',
            'name' => collect(['en' => $name]),
            'description' => collect(['en' => $description]),
            'short_description' => $shortDescription === '' ? null : collect(['en' => $shortDescription]),
            'attribute_data' => $attributes,
            'fulfillment_summary' => ['type' => 'none', 'source' => 'woocommerce'],
        ];

        if ($product === null) {
            $product = Product::create($payload);
        } else {
            $product->fill($payload);
            $product->published_at = $published ? ($product->published_at ?? now()) : null;
            $product->save();
        }

        if ($product->published_at === null && $published) {
            $product->forceFill(['published_at' => now()])->save();
        }

        return $product;
    }

    /**
     * @param  array<string, string>  $parentRow
     * @param  list<array<string, string>>  $variations
     * @return list<array<string, mixed>>
     */
    private function variantRows(Product $product, array $parentRow, array $variations): array
    {
        $rows = $variations !== [] ? $variations : [$parentRow];
        $existingVariants = $product->variants()->get()->keyBy('sku');

        return collect($rows)
            ->map(function (array $row) use ($existingVariants): array {
                $sku = trim((string) ($row['SKU'] ?? ''));
                $name = $this->cleanText((string) ($row['Attribute 1 value(s)'] ?? ''));
                $name = $name !== '' ? $name : $this->cleanText((string) ($row['Name'] ?? 'Standard license'));
                $regularPrice = $this->decimal($row['Regular price'] ?? null);
                $salePrice = $this->decimal($row['Sale price'] ?? null);
                $price = $salePrice ?? $regularPrice ?? 0;

                return [
                    'id' => $existingVariants->get($sku)?->getKey(),
                    'name' => $name,
                    'sku' => $sku !== '' ? $sku : null,
                    'price' => $price,
                    'compare_at_price' => $salePrice !== null && $regularPrice !== null ? $regularPrice : null,
                    'shippable' => false,
                    'selling_policy' => 'always',
                ];
            })
            ->values()
            ->all();
    }

    /** @param  list<array<string, string>>  $variations */
    private function syncDownloads(Product $product, array $variations, array $options, array &$warnings): int
    {
        $downloadRows = collect($variations)
            ->map(fn (array $row): array => [
                'name' => trim((string) ($row['Download 1 name'] ?? '')),
                'url' => trim((string) ($row['Download 1 URL'] ?? '')),
            ])
            ->filter(fn (array $download): bool => $download['url'] !== '')
            ->unique('url')
            ->values();

        if ($downloadRows->isEmpty()) {
            $this->syncProductDownloads->handle($product, [], []);

            return 0;
        }

        $paths = [];
        $filenames = [];

        foreach ($downloadRows as $download) {
            $filename = $this->safeFilename($download['name'] ?: basename((string) parse_url($download['url'], PHP_URL_PATH)));
            $storedPath = $this->storeAsset(
                $download['url'],
                $filename,
                'downloads/products',
                (string) config('marketplace.private_files_disk', 'private'),
                $options,
                $warnings,
            );

            if ($storedPath === null) {
                return 0;
            }

            $paths[] = $storedPath;
            $filenames[$storedPath] = $filename;
        }

        $this->syncProductDownloads->handle($product, $paths, $filenames);

        return count($paths);
    }

    private function resolveImage(?string $url, string $sku, array $options, array &$warnings): ?string
    {
        if ($url === null || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        if (($options['download_remote_assets'] ?? true) !== true) {
            return $url;
        }

        $extension = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        $extension = preg_match('/^[a-z0-9]{1,8}$/', $extension) === 1 ? $extension : 'bin';
        $path = 'products/imported/'.Str::slug($sku).'.'.$extension;
        $storedPath = $this->storeAsset(
            $url,
            basename($path),
            dirname($path),
            (string) config('marketplace.public_media_disk', 'public'),
            $options,
            $warnings,
            $path,
        );

        return $storedPath ?? $url;
    }

    private function storeAsset(
        string $url,
        string $filename,
        string $directory,
        string $disk,
        array $options,
        array &$warnings,
        ?string $path = null,
    ): ?string {
        $contents = null;
        $assetsPath = $options['assets_path'] ?? null;

        if (filled($assetsPath)) {
            $localPath = rtrim((string) $assetsPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$filename;

            if (is_file($localPath) && is_readable($localPath)) {
                $contents = file_get_contents($localPath);
            }
        }

        if ($contents === false) {
            $contents = null;
        }

        if ($contents === null && ($options['download_remote_assets'] ?? true) === true) {
            try {
                $response = $this->httpClient()->get($url);

                if ($response->successful()) {
                    $contents = $response->body();
                } else {
                    $warnings[] = "Unable to download {$url} (HTTP {$response->status()}).";
                }
            } catch (Throwable $exception) {
                $warnings[] = "Unable to download {$url}: {$exception->getMessage()}";
            }
        }

        if ($contents === null) {
            $warnings[] = "Skipped asset {$url}; provide --assets-path or rerun when the source URL is reachable.";

            return null;
        }

        if ($path === null) {
            $path = trim($directory, '/').'/'.Str::slug(pathinfo($filename, PATHINFO_FILENAME)).'-'.Str::lower(Str::random(8));
            $extension = pathinfo($filename, PATHINFO_EXTENSION);

            if ($extension !== '') {
                $path .= '.'.Str::lower($extension);
            }
        }

        try {
            Storage::disk($disk)->put($path, $contents);
        } catch (Throwable $exception) {
            $warnings[] = "Unable to store {$url} on disk [{$disk}]: {$exception->getMessage()}";

            return null;
        }

        return $path;
    }

    private function httpClient(): PendingRequest
    {
        return Http::timeout(30)->retry(2, 100);
    }

    private function syncStorefrontVisibility(Product $product): void
    {
        $channel = Channel::query()->where('handle', 'webstore')->firstOrFail();
        $customerGroup = CustomerGroup::query()->where('handle', 'retail')->firstOrFail();

        $product->channels()->syncWithoutDetaching([$channel->getKey() => ['enabled' => true]]);
        DB::table('lunar_channelables')
            ->where('channelable_id', $product->getKey())
            ->where('channelable_type', Product::class)
            ->update(['channelable_type' => 'product']);
        $product->customerGroups()->syncWithoutDetaching([$customerGroup->getKey() => [
            'purchasable' => true,
            'visible' => true,
            'enabled' => true,
        ]]);
    }

    private function syncCategories(string $categories): void
    {
        foreach (explode(',', $categories) as $name) {
            $name = trim($this->cleanText($name));

            if ($name === '') {
                continue;
            }

            Category::query()->firstOrCreate([
                'name' => $name,
            ], [
                'slug' => Str::slug($name),
                'icon' => Category::defaultIconFor($name),
            ]);
        }
    }

    private function primaryCategory(string $categories): string
    {
        return trim($this->cleanText((string) (explode(',', $categories)[0] ?? ''))) ?: 'Software';
    }

    private function cleanText(string $value): string
    {
        return trim(strip_tags($value));
    }

    private function firstUrl(string $value): ?string
    {
        $url = trim((string) (explode(',', $value)[0] ?? ''));

        return $url === '' ? null : $url;
    }

    private function safeFilename(string $filename): string
    {
        $filename = basename(str_replace('\\', '/', trim($filename)));

        return $filename !== '' ? $filename : 'download.bin';
    }

    private function isTruthy(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function decimal(?string $value): ?float
    {
        return $value === null || trim($value) === '' ? null : (float) $value;
    }
}
