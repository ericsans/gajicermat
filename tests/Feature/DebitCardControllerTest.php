<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var \App\Models\User $user
     */
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        DebitCard::factory()->times(5)->create(
            ['user_id' => 1, 'disabled_at' => null]
        );

        $this->get('/api/debit-cards')
             ->assertStatus(200)
             ->decodeResponseJson()->assertCount(5);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        User::factory()->create(['id' => 999]);
        DebitCard::factory()->create(
            ['user_id' => 999, 'disabled_at' => null]
        );

        $this->get('/api/debit-cards')
             ->assertStatus(200)
             ->decodeResponseJson()->assertCount(0);
    }

    public function testCustomerCanCreateADebitCard()
    {
        $validRequest = [
            'type' => 'Master Card',
        ];

        $this->postJson('/api/debit-cards', $validRequest)
             ->assertStatus(201);
        $this->assertDatabaseHas('debit_cards', $validRequest);
    }

    public function testCustomerCannotCreateADebitCardWithWrongValidation()
    {
        DebitCard::factory()->create(
            ['user_id' => 1, 'disabled_at' => null]
        );

        $invalidRequest = [
            'wrong_body' => 'Master Card'
        ];

        $this->postJson('/api/debit-cards', $invalidRequest)
             ->assertStatus(422);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        DebitCard::factory()->create(
            ['user_id' => 1, 'disabled_at' => null]
        );

        $this->get('/api/debit-cards/1')
             ->assertStatus(200);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        $this->get('/api/debit-cards/invalid')
             ->assertStatus(404);
    }

    public function testCustomerCanActivateADebitCard()
    {
        DebitCard::factory()->create(
            ['user_id' => 1, 'disabled_at' => null]
        );

        $validRequest = [
            'is_active' => true
        ];

        $this->putJson('/api/debit-cards/1', $validRequest)
             ->assertStatus(200);
        $this->assertDatabaseHas('debit_cards', [
            'user_id' => 1
        ]);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        $validRequest = [
            'is_active' => true
        ];

        $this->putJson('/api/debit-cards/invalid', $validRequest)
             ->assertStatus(404);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        DebitCard::factory()->create(
            ['user_id' => 1, 'disabled_at' => null]
        );

        $invalidRequest = [
            'invalid_body' => true
        ];

        $this->putJson('/api/debit-cards/1', $invalidRequest)
             ->assertStatus(422);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        DebitCard::factory()->create(
            ['user_id' => 1, 'disabled_at' => null]
        );

        $this->delete('/api/debit-cards/1')
             ->assertStatus(204);
        $this->assertDatabaseMissing('debit_cards', [
            'id' => 1,
            'user_id' => 1,
            'delete_at' => now()->format('Y-m-d')
        ]);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        DebitCardTransaction::factory()->create();
        
        $this->delete('/api/debit-cards/1')
             ->assertStatus(403);
    }
}
