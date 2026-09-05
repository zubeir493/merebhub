<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Enums\ProductPublicationState;
use App\Domain\Shared\Actions\RecordAuditEventAction;
use App\Models\Product;
use DomainException;
use Lunar\Core\Models\Staff;

class ReviewProductAction
{
    public function __construct(private RecordAuditEventAction $audit) {}

    public function handle(
        Product $product,
        Staff $reviewer,
        ProductPublicationState $state,
        ?string $reason = null,
    ): Product {
        if (! in_array($state, [
            ProductPublicationState::UnderReview,
            ProductPublicationState::Approved,
            ProductPublicationState::ChangesRequested,
            ProductPublicationState::Rejected,
            ProductPublicationState::Published,
            ProductPublicationState::Archived,
        ], true)) {
            throw new DomainException('The selected publication state is not a review decision.');
        }

        $product->forceFill([
            'publication_state' => $state->value,
            'published_at' => $state === ProductPublicationState::Published ? now() : $product->published_at,
            'archived_at' => $state === ProductPublicationState::Archived ? now() : $product->archived_at,
        ])->save();

        $this->audit->handle(
            'catalog.product.reviewed',
            actor: $reviewer,
            subject: $product,
            metadata: [
                'state' => $state->value,
                'reason' => $reason,
            ],
        );

        return $product->refresh();
    }
}
