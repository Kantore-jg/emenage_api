<?php

namespace App\Services;

use App\Mail\GlobalAnnouncementMail;
use App\Mail\PasswordResetMail;
use App\Mail\WelcomeUserMail;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class UserEmailService
{
    public function sendWelcomeEmail(User $user, string $plainPassword, ?User $createdBy = null): bool
    {
        if (!$this->hasDeliverableEmail($user)) {
            return false;
        }

        return $this->sendToUser(
            $user,
            new WelcomeUserMail($user, $plainPassword, $createdBy?->nom)
        );
    }

    public function sendPasswordResetEmail(User $user, string $plainPassword): bool
    {
        if (!$this->hasDeliverableEmail($user)) {
            return false;
        }

        return $this->sendToUser(
            $user,
            new PasswordResetMail($user, $plainPassword)
        );
    }

    public function sendAnnouncementToAllUsers(Announcement $announcement, ?User $author = null): int
    {
        $sentCount = 0;

        User::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('id')
            ->chunk(100, function ($users) use (&$sentCount, $announcement, $author) {
                foreach ($users as $user) {
                    if ($this->sendToUser($user, new GlobalAnnouncementMail($announcement, $author?->nom))) {
                        $sentCount++;
                    }
                }
            });

        return $sentCount;
    }

    private function hasDeliverableEmail(User $user): bool
    {
        return filled($user->email);
    }

    private function sendToUser(User $user, $mailable): bool
    {
        try {
            Mail::to($user->email)->send($mailable);
            return true;
        } catch (\Throwable $e) {
            Log::error('Email sending failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
