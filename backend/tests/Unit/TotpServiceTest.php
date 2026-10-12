<?php

namespace Tests\Unit;

use App\Services\Security\TotpService;
use PHPUnit\Framework\TestCase;

class TotpServiceTest extends TestCase
{
    public function test_totp_verification_matches_rfc_6238_six_digit_vector(): void
    {
        $service = new TotpService();

        $this->assertTrue($service->verify('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', '287082', 59));
        $this->assertFalse($service->verify('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', '000000', 59));
    }

    public function test_totp_rejects_malformed_codes(): void
    {
        $service = new TotpService();

        $this->assertFalse($service->verify('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', '12345', 59));
        $this->assertFalse($service->verify('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 'abcdef', 59));
    }
}
