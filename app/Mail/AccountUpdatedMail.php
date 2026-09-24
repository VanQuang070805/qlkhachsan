<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string  $fullname,
        public string  $username,
        public string  $email,
        public ?string $phone,
        public string  $role,
        public ?string $plainPassword = null  // null nếu không đổi mật khẩu
    ) {}

    public function build()
    {
        return $this->subject('Thông báo cập nhật tài khoản - ' . config('app.name'))
                    ->view('emails.account-updated');
    }
}