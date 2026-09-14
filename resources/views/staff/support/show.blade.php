@extends('layouts.staff', ['title' => 'Ticket '.$ticket->public_id])

@section('content')
    <header class="mb-8">
        <a href="{{ route('staff.support.index') }}" class="text-sm font-bold text-teal-800 underline decoration-teal-300 underline-offset-4">Back to inbox</a>
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="font-mono text-xs text-zinc-500">{{ $ticket->public_id }}</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight">{{ $ticket->subject }}</h1>
                <p class="mt-2 text-sm text-zinc-600">{{ $ticket->user->name }} · {{ $ticket->user->email }}</p>
            </div>
            <span class="inline-flex w-fit rounded-full bg-zinc-100 px-3 py-1 text-xs font-bold text-zinc-700">{{ str($ticket->status->value)->replace('_', ' ')->title() }}</span>
        </div>
    </header>

    <section class="mb-8 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-7">
        <h2 class="text-lg font-extrabold">Ticket management</h2>
        <form method="POST" action="{{ route('staff.support.update', $ticket) }}" class="mt-5 grid gap-4 sm:grid-cols-3">
            @csrf
            @method('PATCH')
            <div>
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-input">
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($ticket->status === $status)>{{ str($status->value)->replace('_', ' ')->title() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="priority" class="form-label">Priority</label>
                <select id="priority" name="priority" class="form-input">
                    @foreach (\App\Domain\Support\Enums\SupportTicketPriority::cases() as $priority)
                        <option value="{{ $priority->value }}" @selected($ticket->priority === $priority)>{{ str($priority->value)->title() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="assigned_staff_id" class="form-label">Assigned staff</label>
                <select id="assigned_staff_id" name="assigned_staff_id" class="form-input">
                    <option value="">Unassigned</option>
                    @foreach ($staffMembers as $staffMember)
                        <option value="{{ $staffMember->id }}" @selected((int) $ticket->assigned_staff_id === (int) $staffMember->id)>{{ $staffMember->full_name }}</option>
                    @endforeach
                </select>
            </div>
            @error('status')<p class="form-error sm:col-span-3">{{ $message }}</p>@enderror
            @error('priority')<p class="form-error sm:col-span-3">{{ $message }}</p>@enderror
            @error('assigned_staff_id')<p class="form-error sm:col-span-3">{{ $message }}</p>@enderror
            <div class="sm:col-span-3"><button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-bold text-white hover:bg-zinc-700">Save ticket</button></div>
        </form>
    </section>

    <section class="grid gap-4">
        @foreach ($ticket->messages as $message)
            <article class="rounded-2xl border p-5 {{ $message->is_internal ? 'border-amber-300 bg-amber-50' : ($message->user_id ? 'border-zinc-200 bg-white' : 'border-teal-100 bg-teal-50/60') }}">
                <header class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-sm font-extrabold">
                        @if ($message->is_internal)
                            Internal staff note
                        @elseif ($message->user_id)
                            {{ $message->user?->name ?? 'Customer' }}
                        @else
                            {{ $message->staff?->full_name ?? 'MerebHub Support' }}
                        @endif
                    </h2>
                    <time class="text-xs text-zinc-500">{{ $message->created_at->format('M j, Y · g:i A') }}</time>
                </header>
                <p class="mt-4 whitespace-pre-wrap text-sm leading-7 text-zinc-800">{{ $message->body }}</p>
                @if ($message->attachments->isNotEmpty())
                    <ul class="mt-4 grid gap-3 border-t border-zinc-200/70 pt-4">
                        @foreach ($message->attachments as $attachment)
                            <li class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm font-semibold">{{ $attachment->original_name }}</p>
                                    <p class="text-xs text-zinc-500">{{ number_format($attachment->size / 1024, 0) }} KB · {{ $attachment->mime_type }} · {{ str($attachment->scan_status->value)->title() }}</p>
                                </div>
                                <div class="flex flex-wrap items-center gap-3">
                                    @if ($attachment->scan_status->value !== 'rejected')
                                        <a href="{{ route('staff.support.attachments.download', $attachment) }}" class="text-sm font-bold text-teal-800 underline decoration-teal-300 underline-offset-4">Download for review</a>
                                    @endif
                                    @if ($attachment->scan_status->value === 'pending')
                                        <form method="POST" action="{{ route('staff.support.attachments.review', $attachment) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="scan_status" value="clean">
                                            <button type="submit" class="rounded-md bg-teal-700 px-3 py-2 text-xs font-bold text-white hover:bg-teal-800">Approve file</button>
                                        </form>
                                        <form method="POST" action="{{ route('staff.support.attachments.review', $attachment) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="scan_status" value="rejected">
                                            <button type="submit" class="rounded-md border border-rose-200 px-3 py-2 text-xs font-bold text-rose-700 hover:bg-rose-50">Reject</button>
                                        </form>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </article>
        @endforeach
    </section>

    <section class="mt-8 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-7">
        <h2 class="text-xl font-extrabold">Reply to customer</h2>
        <p class="mt-1 text-sm text-zinc-600">Internal notes are visible only to staff. Attachments stay private and require manual review.</p>
        <form method="POST" action="{{ route('staff.support.reply', $ticket) }}" enctype="multipart/form-data" class="mt-5 grid gap-4">
            @csrf
            <div>
                <label for="body" class="form-label">Message</label>
                <textarea id="body" name="body" rows="5" maxlength="10000" class="form-input" required>{{ old('body') }}</textarea>
                @error('body')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <label class="flex items-center gap-3 text-sm font-semibold">
                <input type="checkbox" name="is_internal" value="1" @checked(old('is_internal')) class="size-4 rounded border-zinc-300 text-teal-700">
                Add as internal staff note
            </label>
            <div>
                <label for="attachments" class="form-label">Attachments <span class="font-normal text-zinc-500">(PDF, PNG, JPG, or TXT; up to 3 files)</span></label>
                <input id="attachments" name="attachments[]" type="file" accept=".pdf,.png,.jpg,.jpeg,.txt" multiple class="block w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm">
                @error('attachments')<p class="form-error">{{ $message }}</p>@enderror
                @error('attachments.*')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div><button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-bold text-white hover:bg-zinc-700">Send reply</button></div>
        </form>
    </section>
@endsection
