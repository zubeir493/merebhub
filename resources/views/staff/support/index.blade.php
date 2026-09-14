@extends('layouts.staff', ['title' => 'Support inbox'])

@section('content')
    <header class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-bold uppercase tracking-[0.16em] text-teal-700">Customer operations</p>
            <h1 class="mt-2 text-4xl font-extrabold tracking-tight">Support inbox</h1>
            <p class="mt-2 text-zinc-600">Triage, assign, and respond to customer requests.</p>
        </div>
        <form method="GET" action="{{ route('staff.support.index') }}" class="flex items-center gap-2">
            <label for="status" class="text-sm font-semibold">Status</label>
            <select id="status" name="status" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm">
                <option value="">All tickets</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected($selectedStatus === $status)>{{ str($status->value)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-bold text-white hover:bg-zinc-700">Filter</button>
        </form>
    </header>

    @if ($tickets->isEmpty())
        <div class="rounded-2xl border border-dashed border-zinc-300 bg-white px-6 py-16 text-center">
            <x-heroicon-o-inbox class="mx-auto size-12 text-zinc-400" aria-hidden="true" />
            <h2 class="mt-4 text-xl font-bold">No matching tickets</h2>
            <p class="mt-2 text-sm text-zinc-600">New customer requests will appear here.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-zinc-100 bg-zinc-50">
                        <tr class="text-left text-xs uppercase tracking-wide text-zinc-500">
                            <th class="px-5 py-3">Request</th>
                            <th class="px-5 py-3">Customer</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Priority</th>
                            <th class="px-5 py-3">Assignee</th>
                            <th class="px-5 py-3">Last activity</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @foreach ($tickets as $ticket)
                            <tr class="hover:bg-zinc-50">
                                <td class="px-5 py-4">
                                    <a href="{{ route('staff.support.show', $ticket) }}" class="font-semibold text-teal-800 hover:underline">{{ $ticket->subject }}</a>
                                    <p class="mt-1 font-mono text-xs text-zinc-500">{{ $ticket->public_id }} · {{ $ticket->messages_count }} messages</p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-sm font-semibold">{{ $ticket->user->name }}</p>
                                    <p class="text-xs text-zinc-500">{{ $ticket->user->email }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm">{{ str($ticket->status->value)->replace('_', ' ')->title() }}</td>
                                <td class="px-5 py-4 text-sm">{{ str($ticket->priority->value)->title() }}</td>
                                <td class="px-5 py-4 text-sm">{{ $ticket->assignee?->full_name ?? 'Unassigned' }}</td>
                                <td class="px-5 py-4 text-sm text-zinc-600">{{ $ticket->last_message_at?->diffForHumans() ?? $ticket->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-6">{{ $tickets->links() }}</div>
    @endif
@endsection
