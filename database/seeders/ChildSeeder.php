<?php

namespace Database\Seeders;

use App\Enums\AllergySeverity;
use App\Enums\ChildGender;
use App\Enums\ChildGuardianType;
use App\Enums\ChildNoteCategory;
use App\Enums\EnrollmentStatus;
use App\Enums\GuardianRegistrationStatus;
use App\Enums\Rotation;
use App\Models\Center;
use App\Models\Child;
use App\Models\Classroom;
use App\Models\Guardian;
use Illuminate\Database\Seeder;

class ChildSeeder extends Seeder
{
    private Center $center;

    /**
     * Seed the wireframe demo children: Noah (me-profile.html), Karson
     * (me-editprofile.html) and the 12 roster kids (me-rosters.html), plus
     * one invented sibling (Maya Kaur) so a guardian is shared across
     * siblings and the child_guardian pivot is exercised.
     */
    public function run(): void
    {
        $this->center = Center::where('name', 'Childcare Centre Inc.')->firstOrFail();
        $rooms = Classroom::where('center_id', $this->center->id)->pluck('id', 'name');
        $infant = $rooms['Infant and Toddler Room'];
        $threeToFive = $rooms['Three to Five Room'];

        // --- Noah Fernando Sevilla (profile page) --------------------------
        $noah = $this->child('Noah Fernando', 'Sevilla', '2022-05-04', ChildGender::Boy);
        $this->enroll($noah, $threeToFive, EnrollmentStatus::Active, '2025-03-10');
        $this->enroll($noah, $infant, EnrollmentStatus::Graduated, '2024-04-01', '2025-03-07');
        $noah->notes()->firstOrCreate(['body' => 'Msp 9696 054 581'], ['category' => ChildNoteCategory::Msp]);

        $yessica = $this->guardian('Yessica', 'Leiva', 'melypichardo333@hotmail.com', '778-989-7721', GuardianRegistrationStatus::Registered);
        $osman = $this->guardian('Osman', '', 'elchiky85@hotmail.com', '778-681-4043', GuardianRegistrationStatus::Registered);
        $gorge = $this->guardian('Gorge', 'pichardo', null, '6048160803');
        $noah->guardians()->syncWithoutDetaching([
            $yessica->id => ['type' => ChildGuardianType::Parent->value, 'priority' => 1],
            $osman->id => ['type' => ChildGuardianType::Parent->value, 'priority' => 2],
            $gorge->id => ['type' => ChildGuardianType::Guardian->value, 'relationship' => 'Grandparent', 'is_emergency' => true],
        ]);

        // --- Karson Houlam Law (edit-profile page) -------------------------
        $karson = $this->child('Karson Houlam', 'Law', '2023-11-13', ChildGender::Boy, [
            'address_line1' => '11188 72Ave',
            'city' => 'Delta',
            'state' => 'BC',
            'country' => 'Canada',
            'zip' => 'V4E 0A5',
        ]);
        $this->enroll($karson, $infant, EnrollmentStatus::Active, '2025-11-01');
        $karson->allergies()->firstOrCreate(['note' => 'Hearing Aids required'], ['severity' => AllergySeverity::Other]);
        $karson->notes()->firstOrCreate(['body' => 'MSP 965 069 1201'], ['category' => ChildNoteCategory::Other]);

        $lucy = $this->guardian('Lucy Lee', 'Peng', 'lucyleep@hotmail.com', '(604) 782-2120', GuardianRegistrationStatus::Registered);
        $ken = $this->guardian('Ken', 'Law', 'chichunglaw@gmail.com', '(778) 903-1288', GuardianRegistrationStatus::Invited);
        $roy = $this->guardian('Roy', 'Fu', null, '604-518-9398');
        $carlos = $this->guardian('Carlos', 'Lee', null, '647-609-9289');
        $karson->guardians()->syncWithoutDetaching([
            $lucy->id => ['type' => ChildGuardianType::Parent->value, 'priority' => 1],
            $ken->id => ['type' => ChildGuardianType::Parent->value, 'priority' => 2],
            $roy->id => ['type' => ChildGuardianType::Guardian->value, 'relationship' => 'Friend', 'is_emergency' => true],
            $carlos->id => ['type' => ChildGuardianType::Guardian->value, 'relationship' => 'Aunt/Uncle', 'is_emergency' => true],
        ]);

        // --- Roster kids (me-rosters.html), all in Three to Five Room ------
        $roster = [
            ['Mason', 'Cole', '2022-05-14', ChildGender::Boy, '2025-01-01', null],
            ['Ella', 'Yuen', '2021-08-21', ChildGender::Girl, '2025-04-14', null],
            ['Caleb', 'Chan', '2022-10-15', ChildGender::Boy, '2026-05-05', null],
            ['Aaron', 'Kaur', '2021-05-27', ChildGender::Boy, '2025-09-08', ['Peanut allergy — EpiPen on file', AllergySeverity::Severe]],
            ['Nina', 'Owusu', '2021-05-10', ChildGender::Girl, '2024-04-01', null],
            ['Oscar', 'Pratt', '2021-05-14', ChildGender::Boy, '2024-10-02', null],
            ['Priya', 'Shah', '2022-05-29', ChildGender::Girl, '2025-08-01', null],
            ['Ryan', 'Tan', '2022-03-24', ChildGender::Boy, '2025-03-01', ['Hearing Aids required', AllergySeverity::Other]],
            ['Sophie', 'Vaughn', '2021-11-12', ChildGender::Girl, '2025-10-01', null],
            ['Tyler', 'Wood', '2022-06-09', ChildGender::Boy, '2025-07-01', null],
            ['Uma', 'Iyer', '2022-10-14', ChildGender::Girl, '2026-05-01', null],
            ['Leo', 'Sanchez', '2022-01-08', ChildGender::Boy, '2024-09-01', null],
        ];

        foreach ($roster as [$first, $last, $dob, $gender, $enrolled, $allergy]) {
            $child = $this->child($first, $last, $dob, $gender);
            $this->enroll($child, $threeToFive, EnrollmentStatus::Active, $enrolled);

            if ($allergy) {
                $child->allergies()->firstOrCreate(['note' => $allergy[0]], ['severity' => $allergy[1]]);
            }
        }

        // --- Siblings sharing one guardian (pivot exercise) ----------------
        $amrit = $this->guardian('Amrit', 'Kaur', 'amrit.kaur@example.com', '604-555-0147');
        $aaron = Child::where('center_id', $this->center->id)->where('last_name', 'Kaur')->where('first_name', 'Aaron')->firstOrFail();
        $maya = $this->child('Maya', 'Kaur', '2024-06-15', ChildGender::Girl);
        $this->enroll($maya, $infant, EnrollmentStatus::Active, '2026-01-05');

        $aaron->guardians()->syncWithoutDetaching([$amrit->id => ['type' => ChildGuardianType::Parent->value, 'priority' => 1]]);
        $maya->guardians()->syncWithoutDetaching([$amrit->id => ['type' => ChildGuardianType::Parent->value, 'priority' => 1]]);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function child(string $first, string $last, string $dob, ChildGender $gender, array $extra = []): Child
    {
        $child = Child::withTrashed()->firstOrCreate(
            ['center_id' => $this->center->id, 'first_name' => $first, 'last_name' => $last],
            ['date_of_birth' => $dob, 'gender' => $gender, 'photo_consent' => true, ...$extra],
        );
        $child->restore();

        return $child;
    }

    private function enroll(Child $child, int $classroomId, EnrollmentStatus $status, string $enrolledOn, ?string $graduatedOn = null): void
    {
        $enrollment = $child->enrollments()->firstOrCreate(
            ['classroom_id' => $classroomId],
            ['status' => $status, 'rotation' => Rotation::Day, 'enrolled_on' => $enrolledOn, 'graduated_on' => $graduatedOn],
        );
        $enrollment->syncDays([1, 2, 3, 4, 5]); // Mon–Fri
    }

    private function guardian(string $first, string $last, ?string $email, ?string $mobile, GuardianRegistrationStatus $status = GuardianRegistrationStatus::NotInvited): Guardian
    {
        return Guardian::firstOrCreate(
            ['center_id' => $this->center->id, 'first_name' => $first, 'last_name' => $last],
            [
                'email' => $email,
                'mobile_phone' => $mobile,
                'registration_status' => $status,
                'invited_at' => $status === GuardianRegistrationStatus::Invited ? now() : null,
            ],
        );
    }
}
