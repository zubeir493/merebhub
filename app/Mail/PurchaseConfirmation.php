<?php

namespace App\Mail;

use App\Models\License;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class PurchaseConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public License $license)
    {
        $this->afterCommit();
        $this->license->loadMissing(['product.versions', 'order']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Your {$this->license->product->name} license");
    }

    public function content(): Content
    {
        $version = $this->license->product->versions->sortByDesc('created_at')->first();

        return new Content(
            markdown: 'mail.purchase-confirmation',
            with: [
                'downloadUrl' => $version ? URL::temporarySignedRoute(
                    'downloads.show',
                    now()->addDays(7),
                    ['version' => $version, 'license' => $this->license],
                ) : null,
            ],
        );
    }
}
