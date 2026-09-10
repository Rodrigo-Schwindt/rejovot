<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewsletterCampaignMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $asunto,
        public string $cuerpo,
    ) {
    }

    public function build(): self
    {
        return $this->subject($this->asunto)
            ->view('emails.newsletter-campaign');
    }
}
