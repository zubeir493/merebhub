@extends('storefront.account.layout')

@section('account-content')
    <header class="account-page-header flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <a href="{{ route('account.support.index') }}" class="text-sm font-bold text-teal-800 transition hover:text-teal-600">← All support requests</a>
            <h1 class="mt-3 text-3xl font-extrabold tracking-[-0.035em]">{{ $ticket->subject }}</h1>
            <p class="mt-2 font-mono text-xs text-zinc-500">{{ $ticket->public_id }} · {{ str($ticket->status->value)->replace('_', ' ')->title() }}</p>
        </div>
        @if ($ticket->status->value === 'closed')
            <p class="rounded-lg bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900">Replying will reopen this request.</p>
        @endif
    </header>

    <section class="grid gap-4">
        @foreach ($ticket->messages as $message)
            <article class="rounded-md border {{ $message->user_id === auth()->id() ? 'border-zinc-200 bg-white' : 'border-teal-200 bg-teal-50/60' }} p-5">
                <header class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-sm font-extrabold">{{ $message->user_id === auth()->id() ? 'You' : 'MerebHub Support' }}</h2>
                    <time class="text-xs text-zinc-500">{{ $message->created_at->format('M j, Y · g:i A') }}</time>
                </header>
                <p class="mt-4 whitespace-pre-wrap text-sm leading-7 text-zinc-800">{{ $message->body }}</p>
                @if ($message->attachments->isNotEmpty())
                    <ul class="mt-4 grid gap-2 border-t border-zinc-200/70 pt-4">
                        @foreach ($message->attachments as $attachment)
                            <li class="flex flex-wrap items-center justify-between gap-2 text-sm">
                                <span class="font-semibold">{{ $attachment->original_name }}</span>
                                @if ($attachment->scan_status->value === 'clean')
                                    <a href="{{ route('account.support.attachments.download', $attachment) }}" class="font-bold text-teal-800 transition hover:text-teal-600">Download</a>
                                @elseif ($attachment->scan_status->value === 'pending')
                                    <span class="text-xs font-semibold text-amber-800">Pending staff review</span>
                                @else
                                    <span class="text-xs font-semibold text-rose-700">Not approved for download</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </article>
        @endforeach
    </section>

    <section class="account-form-surface mt-8">
        <h2 class="text-xl font-extrabold">Reply to support</h2>
        <form method="POST" action="{{ route('account.support.reply', $ticket) }}" enctype="multipart/form-data" class="mt-5 grid gap-4">
            @csrf
            <div>
                <label for="body" class="form-label">Message</label>
                <textarea id="body" name="body" rows="5" maxlength="10000" class="form-input" required>{{ old('body') }}</textarea>
                @error('body')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="attachments" class="form-label">Attachments <span class="font-normal text-zinc-500">(PDF, PNG, JPG, or TXT; up to 3 files)</span></label>
                <input id="attachments" name="attachments[]" type="file" accept=".pdf,.png,.jpg,.jpeg,.txt" multiple class="block w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm">
                @error('attachments')<p class="form-error">{{ $message }}</p>@enderror
                @error('attachments.*')<p class="form-error">{{ $message }}</p>@enderror
                <p class="mt-2 text-xs text-zinc-500">Files remain private while staff review them.</p>
            </div>
            <div>
                <button type="submit" class="btn-dark">Send reply <x-heroicon-o-paper-airplane class="size-4" aria-hidden="true" /></button>
            </div>
        </form>
    </section>
@endsection
