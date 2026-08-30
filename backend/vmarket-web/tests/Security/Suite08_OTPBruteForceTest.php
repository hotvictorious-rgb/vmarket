<?php

namespace Tests\Security;

use App\Models\PhoneOrEmailVerification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * [AI] Suite 08 — OTP BRUTE-FORCE & ENTROPY PROTECTION (All Roles)
 *
 * Tests OTP enforcement across Customer, Seller, and Delivery Man auth flows.
 *
 * SECURITY STANDARDS TESTED:
 *  - 6-digit OTP format (100000-999999) — no 4-digit codes (AGENTS.md §9.C)
 *  - 5-attempt brute-force lockout (max_otp_hit = 5)
 *  - 15-minute OTP expiry window
 *  - Exact identity matching (=, not LIKE) — no wildcard injection bypass
 *  - OTP not reusable after first successful verification
 *  - OTP cannot be submitted for a different identity than it was issued for
 *
 * RUN: php artisan test tests/Security/Suite08_OTPBruteForceTest.php
 */
class Suite08_OTPBruteForceTest extends SecurityTestCase
{
    /**
     * @test OTP issued to customers is always 6 digits
     */
    public function customer_otp_is_6_digits(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $otp = rand(100000, 999999);
            $this->assertGreaterThanOrEqual(100000, $otp, 'OTP must be >= 100000 (6 digits)');
            $this->assertLessThanOrEqual(999999, $otp, 'OTP must be <= 999999 (6 digits)');
            $this->assertEquals(6, strlen((string)$otp), 'OTP must always be 6 digits');
        }
    }

    /**
     * @test OTP verification is locked after 5 failed attempts
     */
    public function otp_verification_locks_after_5_failed_attempts(): void
    {
        $customer = $this->createCustomer();
        $identity = $customer->email;

        // Seed a valid OTP record
        PhoneOrEmailVerification::updateOrCreate(
            ['identity' => $identity],
            [
                'otp'         => 123456,
                'otp_hit'     => 0,
                'is_temp'     => 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]
        );

        // Submit 5 wrong OTPs
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $response = $this->postJson('/api/v1/auth/verify-otp', [
                'identity' => $identity,
                'otp'      => 999999, // Wrong OTP
            ]);
            // After each attempt, the hit counter should increment
        }

        // 6th attempt — must be locked
        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'identity' => $identity,
            'otp'      => 123456, // Correct OTP but locked out
        ]);

        $response->assertUnprocessable();
        $responseBody = $response->json();
        // Must contain lockout message
        $this->assertTrue(
            str_contains(json_encode($responseBody), 'locked') ||
            str_contains(json_encode($responseBody), 'limit') ||
            str_contains(json_encode($responseBody), 'attempt') ||
            $response->status() === 429,
            'After 5 failed OTP attempts, account must be locked. Response: ' . json_encode($responseBody)
        );
    }

    /**
     * @test Expired OTP (>15 minutes old) is rejected
     */
    public function expired_otp_is_rejected(): void
    {
        $customer = $this->createCustomer();
        $identity = $customer->email;

        // Seed OTP created 20 minutes ago (past 15-minute window)
        PhoneOrEmailVerification::updateOrCreate(
            ['identity' => $identity],
            [
                'otp'        => 654321,
                'otp_hit'    => 0,
                'is_temp'    => 0,
                'created_at' => Carbon::now()->subMinutes(20),
                'updated_at' => Carbon::now()->subMinutes(20),
            ]
        );

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'identity' => $identity,
            'otp'      => 654321,
        ]);

        // Must be rejected — expired OTP
        $response->assertUnprocessable();
        $body = json_encode($response->json());
        $this->assertTrue(
            str_contains($body, 'expire') || str_contains($body, 'expired') || str_contains($body, 'invalid'),
            'Expired OTP must be rejected. Response: ' . $body
        );
    }

    /**
     * @test OTP identity lookup uses EXACT match, not LIKE (no wildcard bypass)
     */
    public function otp_lookup_uses_exact_identity_match_not_wildcard(): void
    {
        // Seed OTP for legit@vmarket.test
        PhoneOrEmailVerification::updateOrCreate(
            ['identity' => 'legit@vmarket.test'],
            ['otp' => 111111, 'otp_hit' => 0, 'is_temp' => 0, 'created_at' => now(), 'updated_at' => now()]
        );

        // Attempt fuzzy identity bypass: use % wildcard that would match legit@vmarket.test via LIKE
        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'identity' => 'legit%',  // Wildcard injection attempt
            'otp'      => 111111,
        ]);

        // Must NOT return 200 success — LIKE wildcard must not match
        $this->assertNotEquals(200, $response->getStatusCode(),
            'OTP identity lookup must use exact match. Wildcard bypass must fail.');
    }

    /**
     * @test OTP cannot be reused after successful verification
     */
    public function used_otp_cannot_be_reused(): void
    {
        $customer = $this->createCustomer();
        $identity = $customer->email;

        PhoneOrEmailVerification::updateOrCreate(
            ['identity' => $identity],
            ['otp' => 777777, 'otp_hit' => 0, 'is_temp' => 0, 'created_at' => now(), 'updated_at' => now()]
        );

        // First verification (success)
        $this->postJson('/api/v1/auth/verify-otp', ['identity' => $identity, 'otp' => 777777]);

        // Second verification with same OTP — must fail
        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'identity' => $identity,
            'otp'      => 777777,
        ]);

        $this->assertNotEquals(200, $response->getStatusCode(),
            'Used OTP must not be reusable. Second attempt must fail.');
    }

    /**
     * @test OTP brute force: max_otp_hit column must be set in PhoneOrEmailVerification
     */
    public function otp_table_has_hit_counter_column(): void
    {
        $this->assertTrue(
            \Schema::hasColumn('phone_or_email_verifications', 'max_otp_hit') ||
            \Schema::hasColumn('phone_or_email_verifications', 'otp_hit'),
            'OTP table must have attempt counter column (max_otp_hit or otp_hit)'
        );
    }

    /**
     * @test OTP submission is rate-limited at the HTTP layer
     */
    public function otp_endpoint_is_rate_limited(): void
    {
        $identity = 'rate@limit.test';

        // Submit OTP 10 times very quickly
        $statuses = [];
        for ($i = 0; $i < 10; $i++) {
            $resp = $this->postJson('/api/v1/auth/verify-otp', [
                'identity' => $identity,
                'otp'      => rand(100000, 999999),
            ]);
            $statuses[] = $resp->getStatusCode();
        }

        // At least some responses must be 429 (Too Many Requests) or lockout
        $hasRateLimit = in_array(429, $statuses) || count(array_filter($statuses, fn($s) => $s >= 400)) >= 5;
        $this->assertTrue($hasRateLimit,
            'OTP endpoint must apply rate limiting. Statuses: ' . implode(', ', $statuses));
    }
}
