<?php

namespace Database\Seeders;

use App\Enums\ApplicationStage;
use App\Enums\ApplicationStatus;
use App\Enums\ChildGender;
use App\Enums\RegistrationFormType;
use App\Models\Center;
use App\Models\RegistrationConsent;
use App\Models\RegistrationDocument;
use App\Models\RegistrationPermission;
use Illuminate\Database\Seeder;

class RegistrationSeeder extends Seeder
{
    /**
     * Default form-field config (PRD 5.4.1 groups with their defaults:
     * guardian Email required, emergency Address hidden), package items and
     * a few demo applications across the funnel stages.
     */
    public function run(): void
    {
        $center = Center::where('name', 'Childcare Centre Inc.')->firstOrFail();

        $fields = [
            ['Child information', 'First name', true, false],
            ['Child information', 'Last name', true, false],
            ['Child information', 'Date of birth', true, false],
            ['Child information', 'Gender', false, false],
            ['Parent/Guardian information', 'Name', true, false],
            ['Parent/Guardian information', 'Email', true, false],
            ['Parent/Guardian information', 'Phone', false, false],
            ['Emergency contacts', 'Name and phone', false, false],
            ['Emergency contacts', 'Address', false, true],
            ['Allergy and medical notes', 'Allergy or medical details', false, false],
            ['Enrollment information', 'Classroom', false, false],
            ['Enrollment information', 'Preferred start date', false, false],
            ['Enrollment information', 'Preferred time of day', false, false],
            ['Enrollment information', 'Preferred weekly schedule', false, false],
            ['Enrollment information', 'Subsidy', false, false],
            ['Enrollment information', 'Internal notes', false, false],
        ];

        foreach (RegistrationFormType::cases() as $formType) {
            foreach ($fields as $order => [$group, $label, $required, $hidden]) {
                $center->registrationFormFields()->firstOrCreate(
                    ['form_type' => $formType, 'group' => $group, 'label' => $label],
                    ['is_required' => $required, 'is_hidden' => $hidden, 'sort_order' => $order],
                );
            }
        }

        foreach ([
            [RegistrationPermission::class, 'Photo and video sharing', 'My child may appear in photos and videos shared with other families.'],
            [RegistrationPermission::class, 'Outdoor excursions', 'My child may join supervised neighbourhood walks.'],
            [RegistrationConsent::class, 'Terms of enrollment', 'I agree to the enrollment terms and payment policy of the center.'],
        ] as $i => [$model, $title, $body]) {
            $model::firstOrCreate(
                ['center_id' => $center->id, 'title' => $title],
                ['body' => $body, 'sort_order' => $i + 1],
            );
        }

        RegistrationDocument::firstOrCreate(
            ['center_id' => $center->id, 'title' => 'Immunization record'],
            ['body' => 'Upload your child\'s current immunization record.', 'is_required' => true, 'sort_order' => 1],
        );
        RegistrationDocument::firstOrCreate(
            ['center_id' => $center->id, 'title' => 'Medical consent form'],
            ['body' => 'Signed medical consent form.', 'sort_order' => 2],
        );

        $threeToFive = $center->classrooms()->where('name', 'Three to Five Room')->first();

        $applications = [
            ['Willa', 'Thompson', '2023-02-11', ApplicationStage::Applicant, ApplicationStatus::New, null, null],
            ['Theo', 'Kowalski', '2022-09-30', ApplicationStage::Applicant, ApplicationStatus::New, null, null],
            ['Rafael', 'Santos', '2023-05-19', ApplicationStage::Waitlist, ApplicationStatus::InProgress, 1, null],
            ['Hana', 'Yamamoto', '2024-01-07', ApplicationStage::Waitlist, ApplicationStatus::New, 2, null],
            ['Gabriel', 'Okoro', '2022-06-02', ApplicationStage::Registration, ApplicationStatus::InProgress, null, $threeToFive?->id],
            ['Elodie', 'Yu', '2022-12-24', ApplicationStage::Registration, ApplicationStatus::ReadyToReview, null, $threeToFive?->id],
        ];

        foreach ($applications as [$first, $last, $dob, $stage, $status, $priority, $classroomId]) {
            $application = $center->applications()->firstOrCreate(
                ['child_first_name' => $first, 'child_last_name' => $last],
                [
                    'date_of_birth' => $dob,
                    'gender' => ChildGender::X,
                    'stage' => $stage,
                    'status' => $status,
                    'priority' => $priority,
                    'classroom_id' => $classroomId,
                    'preferred_start_date' => now()->addMonth()->toDateString(),
                    'submitted_at' => now()->subDays(7),
                ],
            );

            if ($application->wasRecentlyCreated) {
                $application->contacts()->create([
                    'type' => 'guardian',
                    'first_name' => 'Parent of',
                    'last_name' => $last,
                    'email' => strtolower($first.'.'.$last).'@example.com',
                    'phone' => '604-555-01'.str_pad((string) $application->id, 2, '0', STR_PAD_LEFT),
                ]);
            }
        }
    }
}
