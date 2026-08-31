<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Enums\ProductPublicationState;
use App\Models\Product;
use DomainException;

class SubmitProductForReviewAction
{
    public function handle(Product $product): Product
    {
        if (! in_array($product->publication_state, [
            ProductPublicationState::Draft->value,
            ProductPublicationState::ChangesRequested->value,
        ], true)) {
            throw new DomainException('Only draft products can be submitted for review.');
        }

        $product->forceFill([
            'publication_state' => ProductPublicationState::Submitted->value,
        ])->save();

        return $product->refresh();
    }
}
