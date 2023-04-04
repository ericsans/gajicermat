<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var \App\Models\User $user
     */
    protected User $user;
    
    /**
     * @var \App\Models\DebitCard $debitCard
     */
    protected DebitCard $debitCard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->debitCard = DebitCard::factory()->create(
            ['user_id' => $this->user->id]
        );
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCardTransactions()
    {
        DebitCardTransaction::factory()->times(5)->create(['debit_card_id' => 1]);
        
        $this->get('/api/debit-card-transactions?debit_card_id=1')
             ->assertStatus(200)
             ->decodeResponseJson()->assertCount(5);
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        User::factory()->create(['id' => 999]);
        DebitCard::factory()->create(
            ['user_id' => 999, 'disabled_at' => null]
        );
        DebitCardTransaction::factory()->times(5)->create(['debit_card_id' => 2]);
        
        $this->get('/api/debit-card-transactions?debit_card_id=2')
             ->assertStatus(403);
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        $validRequest = [
            'debit_card_id' => 1,
            'amount' => 100000,
            'currency_code' => 'IDR',
        ];

        $this->postJson('/api/debit-card-transactions', $validRequest)
             ->assertStatus(201);
        $this->assertDatabaseMissing('debit_cards', $validRequest);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        User::factory()->create(['id' => 999]);
        DebitCard::factory()->create(
            ['user_id' => 999, 'disabled_at' => null]
        );

        $invalidRequest = [
            'debit_card_id' => 2,
            'amount' => 100000,
            'currency_code' => 'IDR',
        ];

        $this->postJson('/api/debit-card-transactions', $invalidRequest)
             ->assertStatus(403);
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        DebitCardTransaction::factory()->times(5)->create(['debit_card_id' => 1]);

        $this->get('/api/debit-card-transactions/1')
             ->assertStatus(200);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        $this->get('/api/debit-card-transactions/invalid')
             ->assertStatus(404);
    }
}
