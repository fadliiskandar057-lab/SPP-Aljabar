<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_manajemen_cannot_open_treasury_route(): void
    {
        $user = User::create(['name' => 'Admin', 'username' => 'admin-test', 'password' => Hash::make('password'), 'role' => 'admin_tu']);
        $this->actingAs($user)->get('/bendahara/transaksi')->assertForbidden();
    }

    public function test_bendahara_cannot_open_user_management(): void
    {
        $user = User::create(['name' => 'Bendahara', 'username' => 'bendahara-test', 'password' => Hash::make('password'), 'role' => 'bendahara']);
        $this->actingAs($user)->get('/admin/users')->assertForbidden();
    }
}
