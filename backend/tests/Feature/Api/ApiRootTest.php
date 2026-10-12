<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class ApiRootTest extends TestCase
{
    public function test_api_v1_root_returns_service_status_json(): void
    {
        $this->getJson('/api/v1')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'service' => 'Wajhatak API',
                'version' => 'v1',
            ]);
    }
}
