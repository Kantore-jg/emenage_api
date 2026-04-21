<?php

namespace App\Mail;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GlobalAnnouncementMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Announcement $announcement,
        public ?string $authorName = null,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject($this->announcement->titre)
            ->view('emails.global-announcement');
    }
}
