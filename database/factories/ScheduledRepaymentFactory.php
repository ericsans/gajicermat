<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\ScheduledRepayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduledRepaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ScheduledRepayment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        $amount = $this->faker->randomNumber();
        
        return [
            'loan_id' => fn () => Loan::factory()->create(),
            'amount' => $amount,
            'outstanding_amount' => $amount,
            'currency_code' => Loan::CURRENCY_VND,
            'due_date' => $this->faker->dateTimeBetween('+1 month', '+3 year'),
            'status' => ScheduledRepayment::STATUS_DUE,
        ];
    }
}
