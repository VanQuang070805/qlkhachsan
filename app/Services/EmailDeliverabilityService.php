<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class EmailDeliverabilityService
{
    /**
     * Common domain typos mapped to intended mail providers.
     *
     * @var array<string, string>
     */
    private const DOMAIN_TYPOS = [
        'gmaill.com' => 'gmail.com',
        'gmial.com' => 'gmail.com',
        'gmal.com' => 'gmail.com',
        'gamil.com' => 'gmail.com',
        'gnail.com' => 'gmail.com',
        'gmail.con' => 'gmail.com',
        'gmail.co' => 'gmail.com',
        'gmail.cm' => 'gmail.com',
        'gmail.vn' => 'gmail.com',
        'gmail.com.vn' => 'gmail.com',
        'yaho.com' => 'yahoo.com',
        'yahooo.com' => 'yahoo.com',
        'yahoo.con' => 'yahoo.com',
        'hotmial.com' => 'hotmail.com',
        'hotmal.com' => 'hotmail.com',
        'outlok.com' => 'outlook.com',
        'outllok.com' => 'outlook.com',
        'iclod.com' => 'icloud.com',
    ];

    /**
     * Disposable / temporary email domains not permitted for hotel bookings.
     *
     * @var array<int, string>
     */
    private const DISPOSABLE_DOMAINS = [
        'mailinator.com',
        '10minutemail.com',
        'tempmail.com',
        'temp-mail.org',
        'guerrillamail.com',
        'yopmail.com',
        'trashmail.com',
        'sharklasers.com',
        'dispostable.com',
        'maildrop.cc',
        'fakeinbox.com',
        'getnada.com',
    ];

    /**
     * Validate syntax, Gmail username policy, domain MX records, and live SMTP mailbox existence.
     * Returns null when valid, or a localized Vietnamese error message when invalid/non-existent.
     */
    public function validateRealEmail(string $rawEmail, ?bool $enableNetworkChecks = null): ?string
    {
        $email = mb_strtolower(trim($rawEmail));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || ! str_contains($email, '@')) {
            return 'Email chưa đúng định dạng.';
        }

        [$localPart, $domain] = explode('@', $email, 2);
        $domain = trim($domain);

        if (isset(self::DOMAIN_TYPOS[$domain])) {
            $suggested = self::DOMAIN_TYPOS[$domain];

            return "Tên miền @{$domain} không hợp lệ. Bạn có định nhập @{$suggested} không?";
        }

        if (in_array($domain, self::DISPOSABLE_DOMAINS, true)) {
            return 'Vui lòng sử dụng email cá nhân thật (hệ thống không hỗ trợ email tạm thời).';
        }

        // Enforce Google's official Gmail username rules before network probing
        if (in_array($domain, ['gmail.com', 'googlemail.com'], true)) {
            $baseLocal = explode('+', $localPart, 2)[0];
            if (
                $baseLocal === '' ||
                str_starts_with($baseLocal, '.') ||
                str_ends_with($baseLocal, '.') ||
                str_contains($baseLocal, '..') ||
                ! preg_match('/^[a-z0-9.]+$/', $baseLocal)
            ) {
                return 'Địa chỉ Gmail chứa ký tự không hợp lệ theo quy định của Google.';
            }

            $canonicalLength = strlen(str_replace('.', '', $baseLocal));
            if ($canonicalLength < 6 || $canonicalLength > 30) {
                return 'Tên tài khoản Gmail phải có từ 6 đến 30 ký tự (không tính dấu chấm).';
            }
        }

        $shouldProbeNetwork = $enableNetworkChecks ?? (! app()->runningUnitTests() || (bool) config('services.mail_verify.force_in_tests', false));
        if (! $shouldProbeNetwork) {
            return null;
        }

        return Cache::remember('rosaliza:email_verify:'.sha1($email), now()->addMinutes(10), function () use ($email, $domain): ?string {
            $mxHosts = [];
            $mxWeights = [];
            $hasMx = @getmxrr($domain, $mxHosts, $mxWeights);

            if (! $hasMx || $mxHosts === []) {
                if (! @checkdnsrr($domain, 'A') && ! @checkdnsrr($domain, 'AAAA')) {
                    return 'Tên miền email không tồn tại hoặc không có máy chủ nhận thư.';
                }

                return null;
            }

            array_multisort($mxWeights, SORT_ASC, $mxHosts);

            foreach (array_slice($mxHosts, 0, 2) as $mxHost) {
                $probe = $this->probeSmtpMailbox($mxHost, $email);
                if ($probe === 'not_found') {
                    return 'Địa chỉ email này không tồn tại (hộp thư chưa được đăng ký trên Gmail/máy chủ thư). Vui lòng nhập email thật đang hoạt động.';
                }
                if ($probe === 'exists' || $probe === 'inconclusive') {
                    return null;
                }
            }

            return null;
        });
    }

    /**
     * Perform a fast, non-intrusive SMTP RCPT TO handshake on port 25 (no DATA sent).
     *
     * @return 'exists'|'not_found'|'inconclusive'|'unreachable'
     */
    protected function probeSmtpMailbox(string $mxHost, string $email): string
    {
        $fp = @fsockopen($mxHost, 25, $errno, $errstr, 2.5);
        if (! is_resource($fp)) {
            return 'unreachable';
        }

        stream_set_timeout($fp, 2, 500000);

        try {
            $banner = $this->readSmtpResponse($fp);
            if (! str_starts_with($banner, '220')) {
                return 'inconclusive';
            }

            fwrite($fp, "EHLO rosalizahotel.vn\r\n");
            $ehlo = $this->readSmtpResponse($fp);
            if (! str_starts_with($ehlo, '250')) {
                fwrite($fp, "HELO rosalizahotel.vn\r\n");
                $ehlo = $this->readSmtpResponse($fp);
                if (! str_starts_with($ehlo, '250')) {
                    return 'inconclusive';
                }
            }

            fwrite($fp, "MAIL FROM:<no-reply@rosalizahotel.vn>\r\n");
            $mailFrom = $this->readSmtpResponse($fp);
            if (! str_starts_with($mailFrom, '250')) {
                return 'inconclusive';
            }

            fwrite($fp, "RCPT TO:<{$email}>\r\n");
            $rcpt = $this->readSmtpResponse($fp);
            fwrite($fp, "QUIT\r\n");

            if (str_starts_with($rcpt, '250') || str_starts_with($rcpt, '251')) {
                return 'exists';
            }

            if (preg_match('/^55[0-4]\b/', $rcpt)) {
                $lower = strtolower($rcpt);
                if (
                    str_contains($lower, '5.1.1') ||
                    str_contains($lower, 'does not exist') ||
                    str_contains($lower, 'no such user') ||
                    str_contains($lower, 'user unknown') ||
                    str_contains($lower, 'mailbox unavailable') ||
                    str_contains($lower, 'invalid mailbox') ||
                    str_contains($lower, 'recipient rejected') ||
                    str_contains($lower, 'address rejected') ||
                    str_contains($lower, 'disabled')
                ) {
                    return 'not_found';
                }
            }

            return 'inconclusive';
        } finally {
            @fclose($fp);
        }
    }

    /**
     * @param  resource  $fp
     */
    private function readSmtpResponse($fp): string
    {
        $response = '';
        $maxLines = 16;
        for ($i = 0; $i < $maxLines; $i++) {
            $line = @fgets($fp, 512);
            if ($line === false || $line === '') {
                break;
            }
            $response .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }

        return trim($response);
    }
}
