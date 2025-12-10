<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\EnsureCrmAuthenticated;
use Tests\TestCase;

class EnsureCrmAuthenticatedTest extends TestCase
{

    public function test_handle(): void
    {
        $this->markTestIncomplete('Test for handle needs implementation');
    }

    public function test_unauthenticated_response(): void
    {
        $this->markTestIncomplete('Test for unauthenticatedResponse needs implementation');
    }

    public function test_forbidden_response(): void
    {
        $this->markTestIncomplete('Test for forbiddenResponse needs implementation');
    }
}
