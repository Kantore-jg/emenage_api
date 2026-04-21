<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeUserMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $plainPassword,
        public ?string $createdByName = null,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('Bienvenue sur e-Menage')
            ->view('emails.welcome-user');
    }
}
