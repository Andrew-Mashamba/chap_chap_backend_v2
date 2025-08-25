<?php

namespace Tests\Feature\Api;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    protected $sponsor;
    protected $member;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');

        // Create sponsor
        $this->sponsor = Member::create([
            'phone_number' => '255700000001',
            'full_name' => 'Sponsor User',
            'pin' => Hash::make('1234'),
            'seller_id' => 'CHAP001',
            'referral_code' => 'SPONSOR123',
            'sponsor_code' => null,
            'seller_level' => 3,
            'wallet_balance' => 1000000
        ]);

        // Create authenticated member under sponsor
        $this->member = Member::create([
            'phone_number' => '255700000002',
            'full_name' => 'Test User',
            'pin' => Hash::make('1234'),
            'seller_id' => 'CHAP002',
            'referral_code' => 'REF002',
            'sponsor_code' => 'SPONSOR123',
            'seller_level' => 1,
            'wallet_balance' => 100000
        ]);

        $this->token = $this->member->createToken('auth-token')->plainTextToken;
    }

    public function test_get_team_structure()
    {
        // Create downline members
        $downline1 = Member::create([
            'phone_number' => '255700000003',
            'full_name' => 'Downline 1',
            'pin' => Hash::make('1234'),
            'seller_id' => 'CHAP003',
            'referral_code' => 'REF003',
            'sponsor_code' => 'REF002', // Sponsored by test user
            'seller_level' => 0
        ]);

        $downline2 = Member::create([
            'phone_number' => '255700000004',
            'full_name' => 'Downline 2',
            'pin' => Hash::make('1234'),
            'seller_id' => 'CHAP004',
            'referral_code' => 'REF004',
            'sponsor_code' => 'REF002', // Sponsored by test user
            'seller_level' => 0
        ]);

        // Create sub-downline
        $subDownline = Member::create([
            'phone_number' => '255700000005',
            'full_name' => 'Sub Downline',
            'pin' => Hash::make('1234'),
            'seller_id' => 'CHAP005',
            'referral_code' => 'REF005',
            'sponsor_code' => 'REF003', // Sponsored by downline1
            'seller_level' => 0
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/team');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ])
            ->assertJsonStructure([
                'status',
                'data' => [
                    'member' => [
                        'id',
                        'full_name',
                        'seller_id',
                        'referral_code',
                        'seller_level',
                        'total_team_size',
                        'direct_referrals'
                    ],
                    'upline' => [
                        'id',
                        'full_name',
                        'seller_id',
                        'referral_code',
                        'seller_level'
                    ],
                    'downline' => [
                        '*' => [
                            'id',
                            'full_name',
                            'seller_id',
                            'referral_code',
                            'seller_level',
                            'join_date',
                            'team_size'
                        ]
                    ]
                ]
            ]);

        // Verify counts
        $data = $response->json('data');
        $this->assertEquals(2, $data['member']['direct_referrals']);
        $this->assertEquals(3, $data['member']['total_team_size']); // 2 direct + 1 sub
        $this->assertCount(2, $data['downline']);
    }

    public function test_get_team_statistics()
    {
        // Create team members with different levels
        Member::create([
            'phone_number' => '255700000003',
            'full_name' => 'Level 2 Member',
            'pin' => Hash::make('1234'),
            'seller_id' => 'CHAP003',
            'referral_code' => 'REF003',
            'sponsor_code' => 'REF002',
            'seller_level' => 2
        ]);

        Member::create([
            'phone_number' => '255700000004',
            'full_name' => 'Level 1 Member',
            'pin' => Hash::make('1234'),
            'seller_id' => 'CHAP004',
            'referral_code' => 'REF004',
            'sponsor_code' => 'REF002',
            'seller_level' => 1
        ]);

        Member::create([
            'phone_number' => '255700000005',
            'full_name' => 'Level 0 Member',
            'pin' => Hash::make('1234'),
            'seller_id' => 'CHAP005',
            'referral_code' => 'REF005',
            'sponsor_code' => 'REF002',
            'seller_level' => 0
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/team/statistics');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ])
            ->assertJsonStructure([
                'status',
                'data' => [
                    'total_team_size',
                    'direct_referrals',
                    'team_by_level' => [
                        '0',
                        '1',
                        '2'
                    ],
                    'active_members',
                    'inactive_members',
                    'total_team_sales',
                    'total_team_commission'
                ]
            ]);

        $data = $response->json('data');
        $this->assertEquals(3, $data['total_team_size']);
        $this->assertEquals(3, $data['direct_referrals']);
        $this->assertEquals(1, $data['team_by_level']['0']);
        $this->assertEquals(1, $data['team_by_level']['1']);
        $this->assertEquals(1, $data['team_by_level']['2']);
    }

    public function test_get_downline_members()
    {
        // Create multiple generations
        $gen1 = Member::create([
            'phone_number' => '255700000003',
            'full_name' => 'Gen 1 Member',
            'pin' => Hash::make('1234'),
            'seller_id' => 'CHAP003',
            'referral_code' => 'REF003',
            'sponsor_code' => 'REF002'
        ]);

        $gen2 = Member::create([
            'phone_number' => '255700000004',
            'full_name' => 'Gen 2 Member',
            'pin' => Hash::make('1234'),
            'seller_id' => 'CHAP004',
            'referral_code' => 'REF004',
            'sponsor_code' => 'REF003'
        ]);

        $gen3 = Member::create([
            'phone_number' => '255700000005',
            'full_name' => 'Gen 3 Member',
            'pin' => Hash::make('1234'),
            'seller_id' => 'CHAP005',
            'referral_code' => 'REF005',
            'sponsor_code' => 'REF004'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/team/downline');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ])
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'full_name',
                        'seller_id',
                        'referral_code',
                        'seller_level',
                        'generation',
                        'direct_sponsor',
                        'join_date'
                    ]
                ]
            ]);

        $data = $response->json('data');
        $this->assertCount(3, $data);
        
        // Check generations
        $gen1Data = collect($data)->firstWhere('id', $gen1->id);
        $gen2Data = collect($data)->firstWhere('id', $gen2->id);
        $gen3Data = collect($data)->firstWhere('id', $gen3->id);
        
        $this->assertEquals(1, $gen1Data['generation']);
        $this->assertEquals(2, $gen2Data['generation']);
        $this->assertEquals(3, $gen3Data['generation']);
    }

    public function test_get_upline_path()
    {
        // Create multi-level upline
        $topSponsor = Member::create([
            'phone_number' => '255700000010',
            'full_name' => 'Top Sponsor',
            'pin' => Hash::make('1234'),
            'seller_id' => 'CHAP010',
            'referral_code' => 'TOP001',
            'sponsor_code' => null
        ]);

        // Update sponsor to have top sponsor
        $this->sponsor->update(['sponsor_code' => 'TOP001']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/team/upline');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ])
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'full_name',
                        'seller_id',
                        'referral_code',
                        'seller_level',
                        'level_from_member'
                    ]
                ]
            ]);

        $data = $response->json('data');
        $this->assertCount(2, $data); // Direct sponsor and top sponsor
        
        // Verify order (direct sponsor first)
        $this->assertEquals($this->sponsor->id, $data[0]['id']);
        $this->assertEquals($topSponsor->id, $data[1]['id']);
        $this->assertEquals(1, $data[0]['level_from_member']);
        $this->assertEquals(2, $data[1]['level_from_member']);
    }

    public function test_search_team_members()
    {
        // Create team members
        Member::create([
            'phone_number' => '255700000003',
            'full_name' => 'John Doe',
            'pin' => Hash::make('1234'),
            'seller_id' => 'CHAP003',
            'referral_code' => 'REF003',
            'sponsor_code' => 'REF002'
        ]);

        Member::create([
            'phone_number' => '255700000004',
            'full_name' => 'Jane Smith',
            'pin' => Hash::make('1234'),
            'seller_id' => 'CHAP004',
            'referral_code' => 'REF004',
            'sponsor_code' => 'REF002'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/team/search?q=john');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ])
            ->assertJsonCount(1, 'data');

        // Search by seller ID
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/team/search?q=CHAP004');

        $response2->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_member_with_no_team()
    {
        // Create isolated member
        $isolatedMember = Member::create([
            'phone_number' => '255700000099',
            'full_name' => 'Isolated User',
            'pin' => Hash::make('1234'),
            'seller_id' => 'CHAP099',
            'referral_code' => 'REF099',
            'sponsor_code' => null
        ]);

        $token = $isolatedMember->createToken('auth-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/team');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'member' => [
                        'total_team_size' => 0,
                        'direct_referrals' => 0
                    ],
                    'upline' => null,
                    'downline' => []
                ]
            ]);
    }

    public function test_team_requires_authentication()
    {
        $response = $this->getJson('/api/team');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }
}