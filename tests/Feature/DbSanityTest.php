<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class DbSanityTest extends TestCase
{
    /** @test */
    public function using_test_database()
    {
        $db = DB::getDatabaseName();
        $this->assertSame('assessment_test', $db, "Not using test DB: {$db}");
    }
}
