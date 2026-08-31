<?php

namespace App\Domain\Catalog\Enums;

enum ProductPublicationState: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case ChangesRequested = 'changes_requested';
    case Approved = 'approved';
    case Published = 'published';
    case Rejected = 'rejected';
    case Archived = 'archived';
}
