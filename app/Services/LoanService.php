<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\ReceivedRepayment;
use App\Models\ScheduledRepayment;
use App\Models\User;
use Carbon\Carbon;

class LoanService
{
    /**
     * Create a Loan
     *
     * @param  User  $user
     * @param  int  $amount
     * @param  string  $currencyCode
     * @param  int  $terms
     * @param  string  $processedAt
     *
     * @return Loan
     */
    public function createLoan(User $user, int $amount, string $currencyCode, int $terms, string $processedAt): Loan
    {
        $loan = Loan::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'terms' => $terms,
            'outstanding_amount' => $amount,
            'currency_code' => $currencyCode,
            'processed_at' => $processedAt,
            'status' => Loan::STATUS_DUE
        ]);

        for ($iMonth = 1; $iMonth <= $terms; $iMonth++) {
            $repaymentAmount = $iMonth < $terms ? floor($amount / $terms) : ceil($amount / $terms);
            $loan->scheduledRepayments()->create([
                'amount' => $repaymentAmount,
                'outstanding_amount' => $repaymentAmount,
                'currency_code' => $currencyCode,
                'due_date' => Carbon::parse($processedAt)->addMonths($iMonth)->format('Y-m-d'),
                'status' => ScheduledRepayment::STATUS_DUE,
            ]);
        }

        return $loan;
    }

    /**
     * Repay Scheduled Repayments for a Loan
     *
     * @param  Loan  $loan
     * @param  int  $amount
     * @param  string  $currencyCode
     * @param  string  $receivedAt
     *
     * @return ReceivedRepayment
     */
    public function repayLoan(Loan $loan, int $amount, string $currencyCode, string $receivedAt): Loan
    {
        $totalReceivedPayment = $loan->scheduledRepayments()->where('status', ScheduledRepayment::STATUS_REPAID)->sum('amount');
        $outstandingAmount = $loan->amount - ($totalReceivedPayment + $amount);
        $loan->update([
            'outstanding_amount' => $outstandingAmount,
            'currency_code' => $currencyCode,
            'status' => $outstandingAmount > 0 ? Loan::STATUS_DUE : Loan::STATUS_REPAID,
        ]);

        $loan->receivedPayments()->create([
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'received_at' => $receivedAt,
        ]);

        $balance = $amount;
        $scheduledRepayments = $loan->scheduledRepayments()->where('status', ScheduledRepayment::STATUS_DUE)->get();
        foreach ($scheduledRepayments as $scheduledRepayment) { 
            if ($balance > 0) {
                $balance = $balance - $scheduledRepayment->amount;
                $outstandingAmount = $balance > 0 ? 0 : $amount - $scheduledRepayment->amount;

                $scheduledRepayment->update([
                    'outstanding_amount' => $outstandingAmount,
                    'status' => $outstandingAmount === 0 ? ScheduledRepayment::STATUS_REPAID : ScheduledRepayment::STATUS_PARTIAL
                ]);
            }
        }

        $loan = Loan::find($loan->id);
        return $loan;
    }
}
