<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_registration_routes_are_not_exposed(): void
    {
        $this->get('/register')->assertNotFound();

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
        ])->assertNotFound();
    }

    public function test_login_screen_does_not_offer_self_registration(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertDontSee('إنشاء حساب جديد')
            ->assertDontSee("route('register')");
    }
}
