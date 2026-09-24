<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountLockedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $fullname,
        public bool   $isLocked  // true = bị khóa, false = mở khóa
    ) {}

    public function build()
    {
        $subject = $this->isLocked
            ? 'Tài khoản của bạn đã bị khóa - ' . config('app.name')
            : 'Tài khoản của bạn đã được mở khóa - ' . config('app.name');

        return $this->subject($subject)
                    ->view('emails.account-locked');
    }
}