@extends('storefront.account.layout')

@section('account-content')
    <header class="mb-8">
        <p class="text-sm font-bold uppercase tracking-[0.16em] text-teal-700">Customer care</p>
        <h1 class="mt-2 text-4xl font-extrabold tracking-tight">Support requests</h1>
        <p class="mt-2 max-w-2xl text-zinc-600">Send our team a message and follow each reply from your account.</p>
    </header>

    <section class="mb-8 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-7">
        <h2 class="text-xl font-extrabold">Start a support request</h2>
        <form method="POST" action="{{ route('account.support.store') }}" enctype="multipart/form-data" class="mt-5 grid gap-4">
            @csrf
            <div>
                <label for="subject" class="form-label">Subject</label>
                <input id="subject" name="subject" value="{{ old('subject') }}" maxlength="160" class="form-input" required>
                @error('subject')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="body" class="form-label">How can we help?</label>
                <textarea id="body" name="body" rows="5" maxlength="10000" class="form-input" required>{{ old('body') }}</textarea>
                @error('body')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="attachments" class="form-label">Attachments <span class="font-normal text-zinc-500">(up to 3 files, PDF, PNG, JPG, or TXT; 10 MB each)</span></label>
                <input id="attachments" name="attachments[]" type="file" accept=".pdf,.png,.jpg,.jpeg,.txt" multiple class="block w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm">
                @error('attachments')<p class="form-error">{{ $message }}</p>@enderror
                @error('attachments.*')<p class="form-error">{{ $message }}</p>@enderror
                <p class="mt-2 text-xs text-zinc-500">Uploaded files stay private and are not available for download until staff review them.</p>
            </div>
            <div>
                <button type="submit" class="btn-dark">Send request <x-heroicon-o-paper-airplane class="size-4" aria-hidden="true" /></button>
            </div>
        </form>
    </section>

    <section>
        <h2 class="mb-4 text-xl font-extrabold">Your requests</h2>
        @if ($tickets->isEmpty())
            <div class="rounded-2xl border border-dashed border-zinc-300 bg-zinc-50 px-6 py-10 text-center">
                <x-heroicon-o-chat-bubble-left-right class="mx-auto size-10 text-zinc-400" aria-hidden="true" />
                <p class="mt-3 font-bold">No support requests yet</p>
            </div>
        @else
            <div class="grid gap-3">
                @foreach ($tickets as $ticket)
                    <a href="{{ route('account.support.show', $ticket) }}" class="flex flex-col gap-3 rounded-2xl border border-zinc-200 bg-white p-5 transition hover:border-teal-300 hover:shadow-sm sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="font-bold">{{ $ticket->subject }}</h3>
                            <p class="mt-1 font-mono text-xs text-zinc-500">{{ $ticket->public_id }} · {{ $ticket->last_message_at?->diffForHumans() ?? $ticket->created_at->diffForHumans() }}</p>
                        </div>
                        <span class="inline-flex w-fit rounded-full bg-zinc-100 px-3 py-1 text-xs font-bold text-zinc-700">{{ str($ticket->status->value)->replace('_', ' ')->title() }}</span>
                    </a>
                @endforeach
            </div>
            <div class="mt-6">{{ $tickets->links() }}</div>
        @endif
    </section>
@endsection
