<?php

/**
 * [AI] Victorious MARKET POS (Hysam) Rebranding & Integrity Verification Suite
 * Verifies that all user-facing names, headers, titles, templates, and configs
 * have been rebranded to "Vmarket POS".
 */

class VmarketPOSRebrandingTest
{
    private int $passCount = 0;
    private int $failCount = 0;

    public function run(): void
    {
        echo "========================================================================================\n";
        echo "🔍 EXECUTING VMARKET POS REBRANDING INTEGRITY SCANNER\n";
        echo "========================================================================================\n\n";

        $this->testEnvAndConfigs();
        $this->testFrontendBranding();
        $this->testBladeTemplates();

        echo "\n========================================================================================\n";
        echo "🏆 AUDIT VERDICT: {$this->passCount} PASSED, {$this->failCount} FAILED (0 ERRORS)\n";
        echo "========================================================================================\n";
    }

    private function testEnvAndConfigs(): void
    {
        echo "[1] Verifying .env and Application Configs...\n";
        $env = file_get_contents('hysam/.env');

        $this->assert(".env: APP_NAME is VMarket POS", strpos($env, 'APP_NAME="VMarket POS"') !== false || strpos($env, "APP_NAME='VMarket POS'") !== false);
    }

    private function testFrontendBranding(): void
    {
        echo "\n[2] Verifying Frontend React / TSX Components...\n";
        $appTsx = file_get_contents('hysam/resources/js/App.tsx');
        $storageTs = file_get_contents('hysam/resources/js/lib/storage.ts');
        $appBlade = file_get_contents('hysam/resources/views/app.blade.php');

        $this->assert("App.tsx: Desktop Sidebar has 'VMARKET'", strpos($appTsx, "VMARKET") !== false);
        $this->assert("App.tsx: Desktop Sidebar has 'POS & Retail Suite'", strpos($appTsx, "POS & Retail Suite") !== false);
        $this->assert("App.tsx: Mobile Header has 'VMARKET POS'", strpos($appTsx, "VMARKET POS") !== false);
        $this->assert("storage.ts: Default businessName is 'VMARKET POS'", strpos($storageTs, "businessName: 'VMARKET POS'") !== false);
        $this->assert("app.blade.php: Title contains 'Vmarket POS'", strpos($appBlade, "Vmarket POS") !== false);
    }

    private function testBladeTemplates(): void
    {
        echo "\n[3] Verifying Installer & Print Blade Templates...\n";
        $installerLayout = file_get_contents('hysam/resources/views/installer/layout.blade.php');
        $welcomeBlade = file_get_contents('hysam/resources/views/installer/welcome.blade.php');
        $adminBlade = file_get_contents('hysam/resources/views/installer/admin.blade.php');
        $completeBlade = file_get_contents('hysam/resources/views/installer/complete.blade.php');
        $transactionsBlade = file_get_contents('hysam/resources/views/transactions/index.blade.php');
        $waybillBlade = file_get_contents('hysam/resources/views/stock/waybill.blade.php');

        $this->assert("Installer layout: contains 'Vmarket POS'", strpos($installerLayout, "Vmarket POS") !== false);
        $this->assert("Welcome blade: contains 'Welcome to Vmarket POS'", strpos($welcomeBlade, "Welcome to Vmarket POS") !== false);
        $this->assert("Admin setup blade: contains 'Vmarket POS'", strpos($adminBlade, "Vmarket POS") !== false);
        $this->assert("Complete blade: contains 'Vmarket POS'", strpos($completeBlade, "Vmarket POS") !== false);
        $this->assert("Transaction print receipt: contains 'Vmarket POS ERP'", strpos($transactionsBlade, "Vmarket POS ERP") !== false);
        $this->assert("Waybill print note: contains 'Vmarket POS Anti-Theft'", strpos($waybillBlade, "Vmarket POS Anti-Theft") !== false);
    }

    private function assert(string $testName, bool $condition): void
    {
        if ($condition) {
            echo "  ✅ PASS: {$testName}\n";
            $this->passCount++;
        } else {
            echo "  ❌ FAIL: {$testName}\n";
            $this->failCount++;
        }
    }
}

$test = new VmarketPOSRebrandingTest();
$test->run();
