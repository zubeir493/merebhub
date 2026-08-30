<?php

namespace App\Domain\Shared\Events;

use App\Models\OutboxMessage;

class OutboxMessageRecorded implements DomainEvent
{
    public function __construct(public OutboxMessage $message) {}
}
