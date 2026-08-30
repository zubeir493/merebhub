<?php

namespace App\Domain\Shared\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

interface DomainEvent extends ShouldDispatchAfterCommit {}
