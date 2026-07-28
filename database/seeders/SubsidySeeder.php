<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\Child;
use Illuminate\Database\Seeder;

class SubsidySeeder extends Seeder
{
    /**
     * Demo subsidy data: two programs, a day-based agreement for Aaron Kaur
     * with two payments (one short-paid), and ledger entries for Noah.
     */
    public function run(): void
    {
        $center = Center::where('name', 'Childcare Centre Inc.')->firstOrFail();

        $benefit = $center->subsidyPrograms()->firstOrCreate(
            ['name' => 'Provincial Child Care Benefit'],
            ['provider' => 'Province of BC', 'details' => 'Income-tested provincial subsidy.'],
        );
        $center->subsidyPrograms()->firstOrCreate(
            ['name' => 'Community Support Fund'],
            ['provider' => 'Local community foundation'],
        );

        $aaron = Child::where('center_id', $center->id)
            ->where('first_name', 'Aaron')->where('last_name', 'Kaur')->firstOrFail();

        $agreement = $aaron->subsidyAgreements()->firstOrCreate(
            ['subsidy_program_id' => $benefit->id],
            [
                'days_approved' => 5,
                'days_approved_unit' => 'full_days',
                'days_approved_period' => 'week',
                'max_absent_days' => 2,
                'max_absent_period' => 'month',
                'covid_days_count_absent' => true,
                'start_date' => now()->startOfYear()->toDateString(),
                'end_date' => now()->endOfYear()->toDateString(),
                'status' => 'active',
            ],
        );
        $aaron->update(['is_subsidized' => true]);

        foreach ([
            [now()->subMonthNoOverflow()->format('F Y'), 800.00, 800.00, 'Paid in full'],
            [now()->format('F Y'), 800.00, 750.00, 'Followed up with provider'],
        ] as [$period, $estimated, $received, $action]) {
            $agreement->payments()->firstOrCreate(
                ['payment_period' => $period],
                [
                    'description' => 'Monthly subsidy payment',
                    'estimated_amount' => $estimated,
                    'received_amount' => $received,
                    'payment_date' => now()->toDateString(),
                    'action_taken' => $action,
                ],
            );
        }

        $noah = Child::where('center_id', $center->id)->where('last_name', 'Sevilla')->firstOrFail();

        foreach ([
            [now()->startOfMonth()->toDateString(), 'July tuition', 'charge', 1250.00],
            [now()->startOfMonth()->addDays(4)->toDateString(), 'E-transfer payment', 'payment', 1250.00],
            [now()->startOfMonth()->addDays(10)->toDateString(), 'Sibling discount', 'credit', 50.00],
        ] as [$date, $description, $type, $amount]) {
            if ($noah->ledgerEntries()->where('description', $description)->doesntExist()) {
                $noah->ledgerEntries()->create([
                    'entry_date' => $date,
                    'description' => $description,
                    'type' => $type,
                    'amount' => $amount,
                ]);
            }
        }
    }
}
