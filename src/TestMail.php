<?php

namespace CheeseDriven\LaravelTasks;

use CheeseDriven\LaravelTasks\Contracts\WithConstraints;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestMail extends Mailable implements WithConstraints
{
    use Queueable, SerializesModels;

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Test Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            htmlString: '<h1>Test Mail</h1><p>This is a test email from Laravel Tasks.</p>',
        );
    }

    public function constraints(): array
    {
        return [
            // add custom constraints here
        ];
    }
}
