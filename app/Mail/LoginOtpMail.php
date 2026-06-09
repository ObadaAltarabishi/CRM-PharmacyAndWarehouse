<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoginOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $actorLabel
    ) {
    }

    public function build(): static
    {
        return $this
            ->subject('Your Pharmacy Management login code')
            ->html(
                '<p>Hello,</p>'
                . '<p>Your ' . e($this->actorLabel) . ' login verification code is:</p>'
                . '<h2 style="letter-spacing: 4px;">' . e($this->code) . '</h2>'
                . '<p>This code expires in 5 minutes.</p>'
                . '<p>If you did not request this code, you can ignore this email.</p>'
            );
    }
}
