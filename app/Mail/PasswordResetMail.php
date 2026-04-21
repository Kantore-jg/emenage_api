<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $plainPassword,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('Votre mot de passe e-Menage a ete reinitialise')
            ->view('emails.password-reset');
    }
}
