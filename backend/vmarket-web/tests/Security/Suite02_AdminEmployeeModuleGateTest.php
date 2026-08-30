<?php

namespace Tests\Security;

/**
 * [AI] Suite 02 — ADMIN EMPLOYEE MODULE GATE ENFORCEMENT
 *
 * Verifies that Role 2 (Admin Employee) is strictly gated by their module_access JSON.
 * Specifically validates VULN-NEW-001 fix: pos-management routes now have module:pos_management gate.
 *
 * SECURITY STANDARDS TESTED:
 *  - Employees without pos_management grant cannot reach pos-management dashboard (GET)
 *  - Employees without pos_management grant cannot POST to pos-management settings update
 *  - Employees without 3rd_party_setup cannot access payment method config
 *  - Employees WITH correct module CAN access their permitted routes
 *  - Direct URL manipulation is blocked (no bypass via query string injection)
 *
 * RUN: php artisan test tests/Security/Suite02_AdminEmployeeModuleGateTest.php
 */
class Suite02_AdminEmployeeModuleGateTest extends SecurityTestCase
{
    /** @test [VULN-NEW-001] Employee without pos_management cannot view POS dashboard */
    public function employee_without_pos_module_cannot_access_pos_dashboard(): void
    {
        $employee = $this->createAdminEmployee(['order_management', 'product_management']);
        $response = $this->actingAsAdmin($employee)
            ->get(route('admin.pos-management.dashboard'));
        $this->assertSecurityDenied($response, 'VULN-NEW-001: Employee without pos_management must be denied');
    }

    /** @test [VULN-NEW-001] Employee without pos_management cannot POST to settings update */
    public function employee_without_pos_module_cannot_update_pos_pricing(): void
    {
        $employee = $this->createAdminEmployee(['order_management']);
        $response = $this->actingAsAdmin($employee)
            ->post(route('admin.pos-management.settings.update'), [
                'pos_multi_branch_monthly_price' => 0,
                'pos_free_branch_limit' => 999,
                'pos_multi_branch_annual_price' => 0,
                'pos_trial_days' => 0,
                'pos_receipt_footer_text' => 'Hacked',
            ]);
        $this->assertSecurityDenied($response, 'Employee must not overwrite SaaS pricing');
    }

    /** @test Employee without 3rd_party_setup cannot access payment method config */
    public function employee_without_3rd_party_module_cannot_access_payment_config(): void
    {
        $employee = $this->createAdminEmployee(['order_management']);
        $response = $this->actingAsAdmin($employee)
            ->get(route('admin.third-party.payment-method.index'));
        $this->assertSecurityDenied($response, 'Employee must not access payment gateway secrets');
    }

    /** @test Employee WITH pos_management module CAN access POS dashboard */
    public function employee_with_pos_module_can_access_pos_dashboard(): void
    {
        $employee = $this->createAdminEmployee(['pos_management']);
        $response = $this->actingAsAdmin($employee)
            ->get(route('admin.pos-management.dashboard'));
        $this->assertEquals(200, $response->getStatusCode(),
            'Employee WITH pos_management grant must get HTTP 200');
    }

    /** @test Employee WITH 3rd_party_setup CAN access payment method config */
    public function employee_with_3rd_party_module_can_access_payment_config(): void
    {
        $employee = $this->createAdminEmployee(['3rd_party_setup']);
        $response = $this->actingAsAdmin($employee)
            ->get(route('admin.third-party.payment-method.index'));
        $this->assertEquals(200, $response->getStatusCode(),
            'Employee WITH 3rd_party_setup grant must get HTTP 200');
    }

    /** @test Employee cannot access modules NOT in their module_access list */
    public function employee_cannot_access_unauthorized_modules_via_direct_url(): void
    {
        // Employee has ONLY order_management
        $employee = $this->createAdminEmployee(['order_management']);

        $gatedRoutes = [
            route('admin.pos-management.dashboard'),
            route('admin.pos-management.settings'),
            route('admin.third-party.payment-method.index'),
        ];

        foreach ($gatedRoutes as $url) {
            $response = $this->actingAsAdmin($employee)->get($url);
            $this->assertNotEquals(200, $response->getStatusCode(),
                "Employee must not access [{$url}] via direct URL");
        }
    }

    /** @test Module gate cannot be bypassed with query string manipulation */
    public function module_gate_cannot_be_bypassed_via_query_string(): void
    {
        $employee = $this->createAdminEmployee(['order_management']);
        $url = route('admin.pos-management.dashboard') . '?module=pos_management&bypass=1';
        $response = $this->actingAsAdmin($employee)->get($url);
        $this->assertSecurityDenied($response, 'Query string bypass attempt must be denied');
    }

    /** @test Unauthenticated cannot reach any module-gated route */
    public function unauthenticated_cannot_reach_module_gated_routes(): void
    {
        $response = $this->get(route('admin.pos-management.dashboard'));
        $this->assertSecurityDenied($response, 'No session = no access to pos-management');
    }
}
