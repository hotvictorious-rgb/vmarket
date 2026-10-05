<?php

namespace Tests\Regression;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/** [AI] Executes the historical entry point, never the destructive fixture body. */
class V1LegacyProofQuarantineTest extends TestCase
{
    public function testDefaultAndClaimedDisposableTargetsCannotBootstrapOrMutateFixtures(): void
    {
        $script = __DIR__ . '/e2e_marketplace_lifecycle_proof.php';
        $fixture = new \PDO('sqlite::memory:');
        $fixture->exec('CREATE TABLE financial_fixture (id INTEGER, balance TEXT)');
        $fixture->exec("INSERT INTO financial_fixture VALUES (4001, '19500.00')");
        $before = hash('sha256', json_encode($fixture->query('SELECT * FROM financial_fixture ORDER BY id')->fetchAll(\PDO::FETCH_ASSOC)));

        foreach ([
            ['VMARKET_DISPOSABLE_VERIFY' => '0', 'DB_DATABASE' => 'vmarket'],
            ['VMARKET_DISPOSABLE_VERIFY' => '1', 'DB_DATABASE' => 'vmarket'],
            ['VMARKET_DISPOSABLE_VERIFY' => '1', 'VMARKET_DISPOSABLE_DB' => 'vmarket_v1_verify_claimed', 'DB_DATABASE' => 'vmarket_v1_verify_claimed'],
        ] as $environment) {
            // An impossible driver also proves refusal happens before a DB connection.
            $process = new Process([PHP_BINARY, $script, '--approved-disposable'], dirname($script), $environment + [
                'APP_ENV' => 'testing', 'DB_CONNECTION' => 'invalid_quarantine_probe', 'DB_HOST' => '127.0.0.1',
            ]);
            $process->setTimeout(15);
            $process->run();
            $this->assertSame(78, $process->getExitCode(), $process->getErrorOutput());
            $this->assertSame('', $process->getOutput());
            $this->assertStringContainsString('No application bootstrap or database access occurred', $process->getErrorOutput());
            $this->assertStringNotContainsString('SQLSTATE', $process->getErrorOutput());
            $this->assertSame($before, hash('sha256', json_encode($fixture->query('SELECT * FROM financial_fixture ORDER BY id')->fetchAll(\PDO::FETCH_ASSOC))));
        }
    }
}
