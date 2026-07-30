<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Enums\RoleName;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Enums\UserStatus;
use App\Models\Course;
use App\Models\Term;
use App\Models\TermEnrollment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    private const RECENT_LIMIT = 5;

    /**
     * Build the aggregated admin dashboard payload.
     *
     * @return array<string, mixed>
     */
    public function build(User $admin): array
    {
        return [
            'profile' => $this->profile($admin),
            'stats' => $this->stats(),
            'recent_registrations' => $this->recentRegistrations(),
            'recent_advise_requests' => $this->recentTickets(TicketType::Advise),
            'recent_service_requests' => $this->recentTickets(TicketType::Service),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function stats(): array
    {
        $termIds = [1 => [], 2 => [], 3 => []];

        Term::query()
            ->get(['id', 'title'])
            ->each(function (Term $term) use (&$termIds) {
                $number = $this->academicTermNumber($term->title);
                if ($number !== null) {
                    $termIds[$number][] = $term->id;
                }
            });

        return [
            // Self-registered applicants receive the student role and retain
            // it after verification, approval, graduation, or blocking.
            'total_applicants' => User::role(RoleName::Student->value)->count(),
            'awaiting_evaluation' => User::role(RoleName::Student->value)
                ->where('status', UserStatus::Verified->value)
                ->count(),
            'term_1_students' => $this->studentCount($termIds[1]),
            'term_2_students' => $this->studentCount($termIds[2]),
            'term_3_students' => $this->studentCount($termIds[3]),
            'graduates' => $this->graduateCount(),

            // Backward-compatible fields for currently deployed clients.
            'active_users' => User::where('status', UserStatus::Approved->value)->count(),
            'total_users' => User::count(),
            'courses_created' => Course::count(),
            'courses_sold' => TermEnrollment::count(),
        ];
    }

    /**
     * Resolve the academic number from a term title instead of relying on its
     * database ID. Production IDs are not the same as term numbers.
     */
    private function academicTermNumber(string $title): ?int
    {
        $normalized = strtr(mb_strtolower($title), [
            '۱' => '1', '۲' => '2', '۳' => '3',
            '١' => '1', '٢' => '2', '٣' => '3',
        ]);

        $ordinals = [
            1 => ['اول', 'یکم'],
            2 => ['دوم'],
            3 => ['سوم'],
        ];

        foreach ($ordinals as $number => $words) {
            foreach ($words as $word) {
                if (str_contains($normalized, $word)) {
                    return $number;
                }
            }
        }

        if (preg_match('/(?:ترم|term)\s*([123])(?:\D|$)/u', $normalized, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * @param  array<int, int>  $termIds
     */
    private function studentCount(array $termIds): int
    {
        if ($termIds === []) {
            return 0;
        }

        return TermEnrollment::query()
            ->whereIn('term_id', $termIds)
            ->whereIn('status', [
                EnrollmentStatus::Active->value,
                EnrollmentStatus::Completed->value,
            ])
            ->distinct()
            ->count('user_id');
    }

    private function graduateCount(): int
    {
        $requiredTerms = max(1, (int) config('education.terms_required_for_missionary', 3));
        $graduates = TermEnrollment::query()
            ->select('user_id')
            ->where('status', EnrollmentStatus::Completed->value)
            ->groupBy('user_id')
            ->havingRaw('COUNT(DISTINCT term_id) >= ?', [$requiredTerms]);

        return DB::query()->fromSub($graduates, 'graduates')->count();
    }

    /**
     * @return array<string, mixed>
     */
    private function profile(User $admin): array
    {
        $admin->loadMissing(['avatar', 'province', 'city']);

        return [
            'id' => $admin->id,
            'name' => $admin->name,
            'avatar_url' => $admin->avatar?->url(),
            'province' => $admin->province?->name,
            'city' => $admin->city?->name,
            'role' => $admin->getRoleNames()->first(),
            'roles' => $admin->getRoleNames()->values(),
            // The admin dashboard is admin-only, so access is always full.
            'access_level' => 'full',
            'open_tickets' => Ticket::where('status', TicketStatus::Open->value)->count(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentRegistrations(): array
    {
        return User::query()
            ->with('avatar')
            ->orderByDesc('id')
            ->limit(self::RECENT_LIMIT)
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'avatar_url' => $u->avatar?->url(),
                'registered_at' => $u->created_at?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentTickets(TicketType $type): array
    {
        return Ticket::query()
            ->where('type', $type->value)
            ->with('student:id,name')
            ->orderByDesc('id')
            ->limit(self::RECENT_LIMIT)
            ->get()
            ->map(fn (Ticket $t) => [
                'id' => $t->id,
                'student_id' => $t->student_id,
                'student_name' => $t->student?->name,
                'subject' => $t->subject,
                'created_at' => $t->created_at?->toIso8601String(),
            ])
            ->all();
    }
}
