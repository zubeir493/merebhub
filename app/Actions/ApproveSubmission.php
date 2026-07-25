<?php

namespace App\Actions;

use App\Enums\AppSubmissionStatus;
use App\Enums\ProductStatus;
use App\Models\AppSubmission;
use App\Models\AppVersion;
use App\Models\Author;
use App\Models\Platform;
use App\Models\Product;
use App\Models\User;
use App\Services\WooCommerceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ApproveSubmission
{
    public function __construct(private WooCommerceService $woocommerce) {}

    public function handle(AppSubmission $submission, User $reviewer, array $data): Product
    {
        if ($submission->status !== AppSubmissionStatus::Pending) {
            throw new RuntimeException('Only pending submissions can be approved.');
        }

        return DB::transaction(function () use ($submission, $reviewer, $data): Product {
            $author = filled($data['author_id'] ?? null)
                ? Author::findOrFail($data['author_id'])
                : Author::create([
                    'name' => $data['new_author_name'],
                    'slug' => $this->uniqueSlug(Author::class, $data['new_author_name']),
                    'bio' => $data['new_author_bio'] ?? null,
                    'is_public' => true,
                ]);

            $product = Product::create([
                'author_id' => $author->id,
                'category' => $data['category'],
                'name' => $submission->app_name,
                'slug' => $this->uniqueSlug(Product::class, $submission->app_name),
                'tagline' => $data['tagline'],
                'description' => $submission->description,
                'price' => $data['price'],
                'compare_at_price' => $data['compare_at_price'] ?? null,
                'cover_path' => $data['cover_path'],
                'keygen_policy_id' => $data['keygen_policy_id'] ?? null,
                'is_featured' => (bool) ($data['is_featured'] ?? false),
                'status' => ProductStatus::Published,
            ]);

            $platform = Platform::firstOrCreate(
                ['slug' => Str::slug($submission->platform)],
                ['name' => $submission->platform],
            );
            $product->platforms()->sync([$platform->id]);

            AppVersion::create([
                'product_id' => $product->id,
                'version_number' => $data['version_number'],
                'file_path' => $submission->file_path,
                'file_size' => Storage::disk(config('filesystems.builds_disk'))->size($submission->file_path),
                'changelog' => 'Initial marketplace release.',
            ]);

            $wooProduct = $this->woocommerce->createProduct($product);
            $product->update(['wc_product_id' => $wooProduct['id']]);

            $submission->update([
                'status' => AppSubmissionStatus::Approved,
                'reviewed_by' => $reviewer->id,
                'linked_author_id' => $author->id,
                'rejection_reason' => null,
            ]);

            return $product;
        });
    }

    private function uniqueSlug(string $model, string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 2;

        while ($model::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
