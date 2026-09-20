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
            'title' => 'Contact',
            'metaDescription' => 'Contact the MerebHub team about the marketplace, partnerships, purchases, or anything else.',
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
            ->with('status', 'Message sent. Thanks for reaching out.');
    }
}
