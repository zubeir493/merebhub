<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Notifications\ContactMessageReceivedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(): View
    {
        return view('storefront.contact', [
            'title' => 'Talk to MerebHub',
            'metaDescription' => 'Get help with a MerebHub purchase, ask about the marketplace, or start a partnership conversation.',
        ]);
    }

    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        /** @var array{name: string, email: string, topic: string, subject: string, message: string} $message */
        $message = $request->validated();
        $inboxEmail = (string) (config('support.inbox_email') ?: config('mail.from.address'));

        Notification::route('mail', $inboxEmail)
            ->notify((new ContactMessageReceivedNotification(...$message))->afterCommit());

        return redirect()
            ->to(route('contact.index').'#contact-form')
            ->with('status', 'Your message is on its way. We’ll get back to you by email.');
    }
}
