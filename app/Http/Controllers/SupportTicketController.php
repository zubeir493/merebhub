<?php

namespace App\Http\Controllers;

use App\Domain\Shared\Actions\RecordAuditEventAction;
use App\Domain\Support\Actions\CreateSupportTicketAction;
use App\Domain\Support\Actions\PostSupportTicketMessageAction;
use App\Http\Requests\CreateSupportTicketRequest;
use App\Http\Requests\ReplySupportTicketRequest;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupportTicketController extends Controller
{
    public function index(Request $request): View
    {
        $tickets = $request->user()->supportTickets()
            ->withCount(['messages as customer_message_count' => fn (Builder $query): Builder => $query->where('is_internal', false)])
            ->latest('last_message_at')
            ->paginate(15)
            ->withQueryString();

        return view('storefront.account.support.index', ['tickets' => $tickets]);
    }

    public function store(
        CreateSupportTicketRequest $request,
        CreateSupportTicketAction $createTicket,
    ): RedirectResponse {
        Gate::forUser($request->user())->authorize('create', SupportTicket::class);

        /** @var array{subject: string, body: string, attachments?: list<UploadedFile>} $data */
        $data = $request->validated();
        $ticket = $createTicket->handle($request->user(), $data);

        return redirect()->route('account.support.show', $ticket)
            ->with('status', 'Your support request has been created.');
    }

    public function show(Request $request, string $supportTicket): View
    {
        $ticket = $this->customerTicket($request->user(), $supportTicket);
        Gate::forUser($request->user())->authorize('view', $ticket);

        return view('storefront.account.support.show', ['ticket' => $ticket]);
    }

    public function reply(
        ReplySupportTicketRequest $request,
        string $supportTicket,
        PostSupportTicketMessageAction $postMessage,
    ): RedirectResponse {
        $ticket = $this->customerTicket($request->user(), $supportTicket);
        Gate::forUser($request->user())->authorize('reply', $ticket);

        /** @var array{body: string, attachments?: list<UploadedFile>} $data */
        $data = $request->validated();
        $postMessage->handle(
            $ticket,
            $request->user(),
            $data['body'],
            isInternal: false,
            files: $data['attachments'] ?? [],
        );

        return back()->with('status', 'Your reply has been sent.');
    }

    public function downloadAttachment(
        Request $request,
        string $attachment,
        RecordAuditEventAction $audit,
    ): StreamedResponse {
        $user = $request->user();
        $file = SupportTicketAttachment::query()
            ->where('public_id', $attachment)
            ->whereHas('message.ticket', fn (Builder $query): Builder => $query->whereBelongsTo($user))
            ->with('message.ticket')
            ->firstOrFail();

        abort_unless(Gate::forUser($user)->allows('download', $file), 404);
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        $audit->handle(
            'support.attachment.downloaded',
            actor: $user,
            subject: $file,
            metadata: ['attachment_id' => $file->public_id],
        );

        return Storage::disk($file->disk)->download($file->path, $file->original_name, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => 'sandbox',
        ]);
    }

    private function customerTicket(User $user, string $publicId): SupportTicket
    {
        return $user->supportTickets()
            ->where('public_id', $publicId)
            ->with([
                'messages' => fn (HasMany $query): HasMany => $query
                    ->where('is_internal', false)
                    ->oldest(),
                'messages.user',
                'messages.staff',
                'messages.attachments',
            ])
            ->firstOrFail();
    }
}
