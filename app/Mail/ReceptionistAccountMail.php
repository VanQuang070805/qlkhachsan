<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReceptionistAccountMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $fullname,
        public string $username,
        public string $roleLabel = 'Lễ tân'
    ) {}

    public function build()
    {
        return $this->subject('Thông tin tài khoản nội bộ - ' . config('app.name'))
                    ->view('emails.receptionist-account');
    }
}
