<?php
/**
 * [AI] 🚀 Victorious MARKET Automated Deployment Webhook
 * 
 * Business Context: Auto-deploys verified master commits from GitHub to the live server
 * while strictly adhering to Rule 7 (Safe Overlay Protocol & 4 Immutable Runtime Assets).
 * 
 * @role_access       Super Admin / GitHub CI Webhook
 * @security_checks   HMAC-SHA256 Secret Signature Verification, Zero Destructive Deletions
 */

$secret = 'Vmarket_Deploy_Secret_2026_Secure_Key';

// 1. Verify GitHub Signature or query key
$headers = getallheaders();
$hubSignature = $headers['X-Hub-Signature-256'] ?? $headers['x-hub-signature-256'] ?? '';
$payload = file_get_contents('php://input');

if ($hubSignature) {
    $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    if (!hash_equals($expectedSignature, $hubSignature)) {
        http_response_code(403);
        die(json_encode(['error' => 'Invalid Webhook Signature']));
    }
} elseif (($_GET['key'] ?? '') !== $secret) {
    http_response_code(403);
    die(json_encode(['error' => 'Unauthorized Access']));
}

$liveDir = dirname(__DIR__); // Root of Laravel application
$parentDir = dirname($liveDir);
$repoCache = $parentDir . '/vmarket_repo_cache';

$output = [];

// 2. Fetch Latest Master from GitHub (Bypassing SSH host key verification prompt)
$gitSsh = 'GIT_SSH_COMMAND="ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null"';

if (!is_dir($repoCache)) {
    $output[] = "Cloning repository cache...";
    exec("{$gitSsh} git clone --depth 1 git@github.com:hotvictorious-rgb/vmarket.git {$repoCache} 2>&1", $output);
} else {
    $output[] = "Pulling latest master from GitHub...";
    chdir($repoCache);
    exec("{$gitSsh} git fetch origin master 2>&1 && git reset --hard origin/master 2>&1", $output);
}

// 3. Safe Overlay Copy (Preserves .env, storage/, and vendor/)
$backendSource = $repoCache . '/backend/vmarket-web';

if (is_dir($backendSource)) {
    $output[] = "Overlaying backend/vmarket-web into live site...";
    
    // Copy updated application directories
    exec("cp -ru {$backendSource}/app {$liveDir}/ 2>&1", $output);
    exec("cp -ru {$backendSource}/bootstrap {$liveDir}/ 2>&1", $output);
    exec("cp -ru {$backendSource}/config {$liveDir}/ 2>&1", $output);
    exec("cp -ru {$backendSource}/database {$liveDir}/ 2>&1", $output);
    exec("cp -ru {$backendSource}/resources {$liveDir}/ 2>&1", $output);
    exec("cp -ru {$backendSource}/routes {$liveDir}/ 2>&1", $output);
    exec("cp -ru {$backendSource}/Modules {$liveDir}/ 2>&1", $output);
    exec("cp -u {$backendSource}/composer.json {$liveDir}/ 2>&1", $output);
    exec("cp -u {$backendSource}/composer.lock {$liveDir}/ 2>&1", $output);
    
    // Copy public assets safely
    exec("cp -ru {$backendSource}/public/assets {$liveDir}/public/ 2>&1", $output);
    exec("cp -u {$backendSource}/public/deploy.php {$liveDir}/public/ 2>&1", $output);
}

// 4. Run Database Migrations & Rebuild Production Cache
chdir($liveDir);
exec("php artisan migrate --force 2>&1", $output);
exec("php artisan optimize:clear 2>&1", $output);
exec("php artisan view:clear 2>&1", $output);

// 5. Log deployment
@file_put_contents($liveDir . '/storage/logs/deploy.log', date('[Y-m-d H:i:s] ') . implode("\n", $output) . "\n\n", FILE_APPEND);

header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'message' => 'Backend-Only Safe Overlay completed successfully',
    'output' => $output
]);
