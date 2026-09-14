<?php

namespace App\Http\Controllers;

use App\Domain\Shared\Actions\RecordAuditEventAction;
use App\Domain\Support\Actions\PostSupportTicketMessageAction;
use App\Domain\Support\Actions\ReviewSupportTicketAttachmentAction;
use App\Domain\Support\Actions\UpdateSupportTicketAction;
use App\Domain\Support\Enums\SupportAttachmentScanStatus;
use App\Domain\Support\Enums\SupportTicketStatus;
use App\Http\Requests\ReplySupportTicketRequest;
use App\Http\Requests\ReviewSupportTicketAttachmentRequest;
use App\Http\Requests\UpdateSupportTicketRequest;
use App\Models\Staff;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffSupportController extends Controller
{
    public function index(Request $request): View
    {
        $status = SupportTicketStatus::tryFrom($request->string('status')->toString());
        $tickets = SupportTicket::query()
            ->with(['user', 'assignee'])
            ->withCount('messages')
            ->when($status, fn (Builder $query) => $query->where('status', $status->value))
            ->latest('last_message_at')
            ->paginate(20)
            ->withQueryString();

        return view('staff.support.index', [
            'tickets' => $tickets,
            'statuses' => SupportTicketStatus::cases(),
            'selectedStatus' => $status,
        ]);
    }

    public function show(Request $request, SupportTicket $supportTicket): View
    {
        $staff = $this->staff($request);
        Gate::forUser($staff)->authorize('view', $supportTicket);

        $supportTicket->load([
            'user',
            'assignee',
            'messages' => fn (HasMany $query): HasMany => $query->oldest(),
            'messages.user',
            'messages.staff',
            'messages.attachments',
        ]);

        return view('staff.support.show', [
            'ticket' => $supportTicket,
            'staffMembers' => Staff::query()
                ->where('admin', true)
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name']),
            'statuses' => SupportTicketStatus::cases(),
        ]);
    }

    public function reply(
        Request $request,
        SupportTicket $supportTicket,
        ReplySupportTicketRequest $replyRequest,
        PostSupportTicketMessageAction $postMessage,
    ): RedirectResponse {
        $staff = $this->staff($request);
        Gate::forUser($staff)->authorize('reply', $supportTicket);

        /** @var array{body: string, is_internal?: bool, attachments?: list<UploadedFile>} $data */
        $data = $replyRequest->validated();
        $postMessage->handle(
            $supportTicket,
            $staff,
            $data['body'],
            (bool) ($data['is_internal'] ?? false),
            $data['attachments'] ?? [],
        );

        return back()->with('status', 'The support reply has been saved.');
    }

    public function update(
        Request $request,
        SupportTicket $supportTicket,
        UpdateSupportTicketRequest $updateRequest,
        UpdateSupportTicketAction $updateTicket,
    ): RedirectResponse {
        $staff = $this->staff($request);
        Gate::forUser($staff)->authorize('update', $supportTicket);

        /** @var array{status: string, priority: string, assigned_staff_id: int|null} $data */
        $data = $updateRequest->validated();
        $updateTicket->handle($supportTicket, $staff, $data);

        return back()->with('status', 'The ticket has been updated.');
    }

    public function reviewAttachment(
        Request $request,
        SupportTicketAttachment $attachment,
        ReviewSupportTicketAttachmentRequest $reviewRequest,
        ReviewSupportTicketAttachmentAction $reviewAttachment,
    ): RedirectResponse {
        $staff = $this->staff($request);
        $status = SupportAttachmentScanStatus::from($reviewRequest->validated('scan_status'));
        $reviewAttachment->handle($attachment, $staff, $status);

        return back()->with('status', 'The attachment review has been recorded.');
    }

    public function downloadAttachment(
        Request $request,
        SupportTicketAttachment $attachment,
        RecordAuditEventAction $audit,
    ): StreamedResponse {
        $staff = $this->staff($request);
        $attachment->loadMissing('message.ticket');
        Gate::forUser($staff)->authorize('download', $attachment);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        $audit->handle(
            'support.attachment.downloaded',
            actor: $staff,
            subject: $attachment,
            metadata: ['attachment_id' => $attachment->public_id],
        );

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => 'sandbox',
        ]);
    }

    private function staff(Request $request): Staff
    {
        $staff = $request->user('staff');
        abort_unless($staff instanceof Staff && $staff->admin, 403);

        return $staff;
    }
}
