<?php

namespace Database\Factories;

use App\Models\DebitCardTransaction;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Loan::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        $amount = $this->faker->randomNumber();
        
        return [
            'user_id' => fn () => User::factory()->create(),
            'amount' => $amount,
            'terms' => $this->faker->randomElement([3, 6]),
            'outstanding_amount' => $amount,
            'currency_code' => $this->faker->randomElement([Loan::CURRENCY_SGD, Loan::CURRENCY_VND]),
            'processed_at' => $this->faker->dateTimeBetween('+1 month', '+3 year'),
            'status' => $this->faker->randomElement([Loan::STATUS_DUE, Loan::STATUS_REPAID]),
        ];
    }
}
