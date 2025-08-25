<?php

namespace Tests\Feature\Api;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_check_phone_number_not_registered()
    {
        $response = $this->postJson('/api/auth/check-phone', [
            'phone_number' => '255700000001'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'is_registered' => false,
                    'registration_allowed' => true
                ]
            ]);
    }

    public function test_check_phone_number_already_registered()
    {
        // Create a member
        Member::create([
            'phone_number' => '255700000001',
            'full_name' => 'Test User',
            'pin' => Hash::make('1234'),
            'seller_id' => 'SLR000001',
            'referral_code' => 'REF001'
        ]);

        $response = $this->postJson('/api/auth/check-phone', [
            'phone_number' => '255700000001'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'is_registered' => true,
                    'registration_allowed' => true
                ]
            ]);
    }

    public function test_verify_sponsor_code_valid()
    {
        // Create a sponsor
        Member::create([
            'phone_number' => '255700000002',
            'full_name' => 'Sponsor User',
            'first_name' => 'Sponsor',
            'last_name' => 'User',
            'pin' => Hash::make('1234'),
            'seller_id' => 'SLR000002',
            'referral_code' => 'SPONSOR123',
            'account_status' => 'active',
            'seller_level' => 3
        ]);

        $response = $this->postJson('/api/auth/verify-sponsor', [
            'sponsor_id' => 'SLR000002'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ])
            ->assertJsonStructure([
                'status',
                'data' => [
                    'first_name',
                    'last_name',
                    'seller_id',
                    'level'
                ]
            ]);
    }

    public function test_verify_sponsor_code_invalid()
    {
        $response = $this->postJson('/api/auth/verify-sponsor', [
            'sponsor_id' => 'SLR999999'
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Invalid sponsor ID. Please contact support at 0754 244 888 if you don\'t have a sponsor.'
            ]);
    }

    public function test_register_new_member()
    {
        // Create a sponsor first
        Member::create([
            'phone_number' => '255700000002',
            'full_name' => 'Sponsor User',
            'first_name' => 'Sponsor',
            'last_name' => 'User',
            'pin' => Hash::make('1234'),
            'seller_id' => 'SLR000002',
            'referral_code' => 'SPONSOR123',
            'account_status' => 'active',
            'seller_level' => 3
        ]);

        $response = $this->postJson('/api/auth/register', [
            'first_name' => 'New',
            'last_name' => 'User',
            'phone_number' => '255700000003',
            'pin' => '1234',
            'email' => 'newuser@example.com',
            'shop_name' => 'New Shop',
            'shop_location' => 'Dar es Salaam',
            'sponsor_id' => 'SLR000002',
            'profile_image' => 'profile.jpg'
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Registration successful'
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'member',
                    'access_token',
                    'refresh_token',
                    'token_type'
                ]
            ]);

        // Verify member was created
        $this->assertDatabaseHas('members', [
            'phone_number' => '255700000003',
            'full_name' => 'New User'
        ]);
    }

    public function test_register_with_duplicate_phone_number()
    {
        // Create existing member
        Member::create([
            'phone_number' => '255700000001',
            'full_name' => 'Existing User',
            'first_name' => 'Existing',
            'last_name' => 'User',
            'pin' => Hash::make('1234'),
            'seller_id' => 'SLR000001',
            'referral_code' => 'REF001',
            'account_status' => 'active'
        ]);

        // Create a sponsor
        Member::create([
            'phone_number' => '255700000002',
            'full_name' => 'Sponsor User',
            'first_name' => 'Sponsor',
            'last_name' => 'User',
            'pin' => Hash::make('1234'),
            'seller_id' => 'SLR000002',
            'referral_code' => 'SPONSOR123',
            'account_status' => 'active',
            'seller_level' => 3
        ]);

        $response = $this->postJson('/api/auth/register', [
            'first_name' => 'New',
            'last_name' => 'User',
            'phone_number' => '255700000001',
            'pin' => '1234',
            'email' => 'existing@example.com',
            'shop_name' => 'New Shop',
            'shop_location' => 'Dar es Salaam',
            'sponsor_id' => 'SLR000002',
            'profile_image' => 'profile.jpg'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone_number']);
    }

    public function test_login_with_valid_credentials()
    {
        // Create a member
        $member = Member::create([
            'phone_number' => '255700000001',
            'full_name' => 'Test User',
            'pin' => Hash::make('1234'),
            'seller_id' => 'SLR000001',
            'referral_code' => 'REF001'
        ]);

        $response = $this->postJson('/api/auth/login', [
            'phone_number' => '255700000001',
            'pin' => '1234'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'refresh_token',
                'token_type',
                'member'
            ]);
    }

    public function test_login_with_invalid_credentials()
    {
        // Create a member
        Member::create([
            'phone_number' => '255700000001',
            'full_name' => 'Test User',
            'pin' => Hash::make('1234'),
            'seller_id' => 'SLR000001',
            'referral_code' => 'REF001'
        ]);

        $response = $this->postJson('/api/auth/login', [
            'phone_number' => '255700000001',
            'pin' => 'wrong'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone_number']);
    }

    public function test_logout()
    {
        // Create and authenticate a member
        $member = Member::create([
            'phone_number' => '255700000001',
            'full_name' => 'Test User',
            'pin' => Hash::make('1234'),
            'seller_id' => 'SLR000001',
            'referral_code' => 'REF001'
        ]);

        $token = $member->createToken('auth-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Successfully logged out'
            ]);
    }

    public function test_refresh_token()
    {
        // Create and authenticate a member
        $member = Member::create([
            'phone_number' => '255700000001',
            'full_name' => 'Test User',
            'pin' => Hash::make('1234'),
            'seller_id' => 'SLR000001',
            'referral_code' => 'REF001'
        ]);

        $token = $member->createToken('auth-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'refresh_token',
                'token_type'
            ]);
    }
}