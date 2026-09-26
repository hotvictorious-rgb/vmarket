<?php

namespace Tests\Feature;

// [AI][VM-TEST-001] Explicit require: vendor/ is a junction to a shared
// checkout, so new test-support classes do not autoload (see
// DumpSchemaTestCase.php header). Test-harness only.
require_once __DIR__ . '/DumpSchemaTestCase.php';

class ExampleTest extends DumpSchemaTestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
