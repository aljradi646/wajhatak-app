<?php

namespace App\Services\Security;

class TotpService
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecret(int $bytes = 20): string
    {
        return $this->base32Encode(random_bytes($bytes));
    }

    public function provisioningUri(string $email, string $secret): string
    {
        $label = rawurlencode('Wajhatak:'.$email);

        return 'otpauth://totp/'.$label.'?secret='.rawurlencode($secret)
            .'&issuer=Wajhatak&algorithm=SHA1&digits=6&period=30';
    }

    public function verify(string $secret, string $code, ?int $timestamp = null): bool
    {
        $code = trim($code);
        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $counter = intdiv($timestamp ?? time(), 30);
        for ($offset = -1; $offset <= 1; $offset++) {
            if ($counter + $offset < 0) {
                continue;
            }

            if (hash_equals($this->codeAt($secret, $counter + $offset), $code)) {
                return true;
            }
        }

        return false;
    }

    private function codeAt(string $secret, int $counter): string
    {
        $key = $this->base32Decode($secret);
        $high = intdiv($counter, 0x100000000);
        $low = $counter % 0x100000000;
        $hash = hash_hmac('sha1', pack('N2', $high, $low), $key, true);
        $offset = ord($hash[19]) & 0x0f;
        $binary = ((ord($hash[$offset]) & 0x7f) << 24)
            | ((ord($hash[$offset + 1]) & 0xff) << 16)
            | ((ord($hash[$offset + 2]) & 0xff) << 8)
            | (ord($hash[$offset + 3]) & 0xff);

        return str_pad((string) ($binary % 1000000), 6, '0', STR_PAD_LEFT);
    }

    private function base32Encode(string $input): string
    {
        $buffer = 0;
        $bits = 0;
        $output = '';

        foreach (unpack('C*', $input) ?: [] as $byte) {
            $buffer = ($buffer << 8) | $byte;
            $bits += 8;
            while ($bits >= 5) {
                $bits -= 5;
                $output .= self::ALPHABET[($buffer >> $bits) & 31];
            }
        }

        if ($bits > 0) {
            $output .= self::ALPHABET[($buffer << (5 - $bits)) & 31];
        }

        return $output;
    }

    private function base32Decode(string $input): string
    {
        $input = strtoupper(preg_replace('/[^A-Z2-7]/i', '', $input) ?? '');
        $buffer = 0;
        $bits = 0;
        $output = '';

        foreach (str_split($input) as $character) {
            $value = strpos(self::ALPHABET, $character);
            if ($value === false) {
                return '';
            }

            $buffer = ($buffer << 5) | $value;
            $bits += 5;
            if ($bits >= 8) {
                $bits -= 8;
                $output .= chr(($buffer >> $bits) & 0xff);
            }
        }

        return $output;
    }
}
