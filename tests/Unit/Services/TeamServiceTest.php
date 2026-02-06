<?php

namespace Tests\Unit\Services;

use App\Services\TeamService;
use Tests\TestCase;

class TeamServiceTest extends TestCase
{
    protected TeamService $teamService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teamService = new TeamService;
    }

    public function test_mask_email_masks_local_part(): void
    {
        $result = $this->teamService->maskEmail('john.doe@example.com');

        $this->assertStringStartsWith('jo', $result);
        $this->assertStringContainsString('@', $result);
        $this->assertStringEndsWith('.com', $result);
    }

    public function test_mask_email_masks_domain(): void
    {
        $result = $this->teamService->maskEmail('user@company.org');

        $this->assertStringContainsString('@***.org', $result);
    }

    public function test_mask_email_handles_short_local_part(): void
    {
        $result = $this->teamService->maskEmail('ab@example.com');

        $this->assertStringStartsWith('ab@', $result);
    }

    public function test_mask_email_handles_single_char_local_part(): void
    {
        $result = $this->teamService->maskEmail('a@example.com');

        $this->assertStringStartsWith('a@', $result);
    }

    public function test_mask_email_handles_invalid_email(): void
    {
        $result = $this->teamService->maskEmail('invalid-email');

        $this->assertEquals('***@***.***', $result);
    }

    public function test_mask_email_handles_email_without_at_symbol(): void
    {
        $result = $this->teamService->maskEmail('noemail');

        $this->assertEquals('***@***.***', $result);
    }

    public function test_mask_email_preserves_tld(): void
    {
        $emails = [
            'user@test.com' => '.com',
            'user@test.org' => '.org',
            'user@test.co.uk' => '.uk',
            'user@test.io' => '.io',
        ];

        foreach ($emails as $email => $expectedTld) {
            $result = $this->teamService->maskEmail($email);
            $this->assertStringEndsWith($expectedTld, $result, "Failed for email: {$email}");
        }
    }

    public function test_mask_email_limits_asterisks_in_local_part(): void
    {
        $result = $this->teamService->maskEmail('verylongemailaddress@example.com');

        // Should show first 2 chars + max 5 asterisks
        $localPart = explode('@', $result)[0];
        $this->assertLessThanOrEqual(7, strlen($localPart));
    }
}
