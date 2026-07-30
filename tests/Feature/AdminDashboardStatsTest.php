<?php

namespace Tests\Feature;

use App\Enums\EnrollmentStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Term;
use App\Models\TermEnrollment;
use App\Models\User;
use App\Services\AdminDashboardService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;

class AdminDashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_applicant_term_and_graduate_statistics(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = $this->user('09121000001', UserStatus::Approved);

        $this->applicant('09121000002', UserStatus::Pending);
        $this->applicant('09121000003', UserStatus::Verified);
        $this->applicant('09121000004', UserStatus::Verified);
        $this->applicant('09121000005', UserStatus::Approved);
        $this->applicant('09121000006', UserStatus::Blocked);

        $term1 = Term::create(['title' => 'ترم اول']);
        $term2 = Term::create(['title' => 'ترم ۲']);
        $term3 = Term::create(['title' => 'Term 3']);
        $term4 = Term::create(['title' => 'ترم چهارم']);

        $studentA = $this->applicant('09121000007', UserStatus::Approved);
        $studentB = $this->applicant('09121000008', UserStatus::Approved);
        $withdrawnStudent = $this->applicant('09121000009', UserStatus::Approved);
        $graduate = $this->applicant('09121000010', UserStatus::Approved);

        $this->enroll($studentA, $term1, EnrollmentStatus::Active);
        $this->enroll($studentA, $term2, EnrollmentStatus::Active);
        $this->enroll($studentB, $term1, EnrollmentStatus::Completed);
        $this->enroll($studentB, $term3, EnrollmentStatus::Completed);
        $this->enroll($withdrawnStudent, $term1, EnrollmentStatus::Withdrawn);

        $this->enroll($graduate, $term1, EnrollmentStatus::Completed);
        $this->enroll($graduate, $term2, EnrollmentStatus::Completed);
        $this->enroll($graduate, $term3, EnrollmentStatus::Completed);

        // A fourth term must not be merged into any of the three dashboard cards.
        $this->enroll($studentA, $term4, EnrollmentStatus::Active);

        $stats = app(AdminDashboardService::class)->build($admin)['stats'];

        $this->assertSame([
            'total_applicants' => 9,
            'awaiting_evaluation' => 2,
            'term_1_students' => 3,
            'term_2_students' => 2,
            'term_3_students' => 2,
            'graduates' => 1,
        ], Arr::only($stats, [
            'total_applicants',
            'awaiting_evaluation',
            'term_1_students',
            'term_2_students',
            'term_3_students',
            'graduates',
        ]));

        $this->assertArrayHasKey('active_users', $stats);
        $this->assertArrayHasKey('total_users', $stats);
        $this->assertArrayHasKey('courses_created', $stats);
        $this->assertArrayHasKey('courses_sold', $stats);
    }

    private function user(string $phone, UserStatus $status): User
    {
        return User::factory()->create([
            'phone' => $phone,
            'status' => $status->value,
        ]);
    }

    private function applicant(string $phone, UserStatus $status): User
    {
        $user = $this->user($phone, $status);
        $user->assignRole(RoleName::Student->value);

        return $user;
    }

    private function enroll(User $user, Term $term, EnrollmentStatus $status): TermEnrollment
    {
        return TermEnrollment::create([
            'user_id' => $user->id,
            'term_id' => $term->id,
            'status' => $status->value,
            'completed_at' => $status === EnrollmentStatus::Completed ? now() : null,
        ]);
    }
}
