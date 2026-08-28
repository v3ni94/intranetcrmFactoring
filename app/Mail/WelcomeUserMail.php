<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Willkommens-Mail fuer neu angelegte Benutzer (v3.01): enthaelt einen
 * zeitlich begrenzten Passwort-Setz-Link statt eines Klartext-Passworts.
 */
class WelcomeUserMail extends Mailable
{
    public function __construct(
        public User $user,
        public string $setPasswordUrl,
        public bool $isReset = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->isReset
                ? 'Aurevia Intranet – Passwort zurückgesetzt'
                : 'Willkommen im Aurevia Intranet – Zugang einrichten',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.welcome-user');
    }
}
