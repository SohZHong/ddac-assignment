<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Blog;
use App\Models\QuizResponse;
use App\Models\ConsultationReport;
use App\UserRole;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    public function index(): Response|RedirectResponse
    {
        $user = Auth::user();
        
        if ($user->isPublicUser()) {
            return $this->publicUserDashboard($user);
        } elseif ($user->isHealthcareProfessional()) {
            return redirect()->route('healthcare.index');
        } elseif ($user->isHealthCampaignManager()) {
            return redirect()->route('campaigns.index');
        } elseif ($user->isSystemAdmin()) {
            return redirect()->route('admin.index');
        }
        
        return $this->publicUserDashboard($user);
    }
    
    private function publicUserDashboard($user): Response
    {
        $currentDate = now();
        
        $upcomingAppointments = $user->bookings()
            ->with(['schedule.healthcare'])
            ->where('start_time', '>', $currentDate)
            ->where('status', Booking::CONFIRMED)
            ->orderBy('start_time', 'asc')
            ->limit(3)
            ->get();
        
        $totalBookings = $user->bookings()->count();
        
        $confirmedBookings = $user->bookings()
            ->where('status', Booking::CONFIRMED)
            ->count();
        
        $pendingBookings = $user->bookings()
            ->where('status', Booking::PENDING)
            ->count();
        
        $completedBookings = $user->bookings()
            ->where('status', Booking::CONFIRMED)
            ->where('start_time', '<', $currentDate)
            ->count();
        
        $consultationReports = $user->consultationReports()
            ->with(['doctor'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        $totalReports = $user->consultationReports()->count();
        
        $quizResponsesCount = QuizResponse::whereHas('booking', function ($query) use ($user) {
            $query->where('patient_id', $user->id);
        })->count();
        
        $recentBlogs = Blog::where('status', Blog::STATUS_PUBLISHED)
            ->with(['author'])
            ->orderBy('published_at', 'desc')
            ->limit(3)
            ->get();
        
        $healthScore = $this->calculateHealthScore($user);
        $latestAssessment = $this->getLatestAssessment($user);

        return Inertia::render('Dashboard', [
            'user' => $user->only(['id', 'name', 'email', 'role']),
            'stats' => [
                'totalBookings' => $totalBookings,
                'confirmedBookings' => $confirmedBookings,
                'pendingBookings' => $pendingBookings,
                'completedBookings' => $completedBookings,
                'totalReports' => $totalReports,
                'quizResponsesCount' => $quizResponsesCount,
                'healthScore' => $healthScore,
                'latestRiskLevel' => $latestAssessment['risk_level'] ?? null,
                'lastAssessmentDate' => $latestAssessment['assessment_date'] ?? null,
                'assessmentCount' => $latestAssessment['total_assessments'] ?? 0,
            ],
            'upcomingAppointments' => $upcomingAppointments->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'start_time' => $booking->start_time,
                    'end_time' => $booking->end_time,
                    'status' => $booking->status,
                    'healthcare' => [
                        'name' => $booking->schedule->healthcare->name,
                        'email' => $booking->schedule->healthcare->email,
                    ],
                ];
            }),
            'recentReports' => $consultationReports->map(function ($report) {
                $disk = config('filesystems.default');
                return [
                    'id' => $report->id,
                    'title' => $report->title ?? 'Consultation Report',
                    'created_at' => $report->created_at,
                    'doctor_name' => $report->uploadedBy->name ?? 'Unknown Doctor',
                    'file_path' => $report->file_path ? Storage::disk($disk)->url($report->file_path) : null,
                ];
            }),
            'recentBlogs' => $recentBlogs->map(function ($blog) {
                return [
                    'id' => $blog->id,
                    'title' => $blog->title,
                    'excerpt' => $this->generateExcerpt($blog->content),
                    'published_at' => $blog->published_at,
                    'author_name' => $blog->author->name,
                    'reading_time' => $this->calculateReadingTime($blog->content),
                ];
            }),
        ]);
    }
    
    private function calculateHealthScore($user): int
    {
        $assessedBookings = $user->bookings()
            ->where('status', Booking::CONFIRMED)
            ->where('start_time', '<', now())
            ->whereNotNull('risk_level')
            ->whereNotNull('healthcare_comments')
            ->orderBy('start_time', 'desc')
            ->get();
        
        if ($assessedBookings->isEmpty()) {
            return 70;
        }
        
        $latestAssessment = $assessedBookings->first();
        $baseScore = $this->getScoreFromRiskLevel($latestAssessment->risk_level);
        
        $trendAdjustment = $this->calculateRiskTrend($assessedBookings);
        
        $reliabilityBonus = min($assessedBookings->count() * 2, 10);
        
        $finalScore = $baseScore + $trendAdjustment + $reliabilityBonus;
        
        return max(10, min(100, $finalScore));
    }
    
    private function getScoreFromRiskLevel($riskLevel): int
    {
        return match($riskLevel) {
            Booking::LOW => 85,
            Booking::MID => 60,
            Booking::HIGH => 30,
            default => 70,
        };
    }
    
    private function calculateRiskTrend($assessments): int
    {
        if ($assessments->count() < 2) {
            return 0;
        }
        
        $recent = $assessments->take(3)->pluck('risk_level')->toArray();
        $older = $assessments->skip(3)->take(3)->pluck('risk_level')->toArray();
        
        if (empty($older)) {
            return 0;
        }
        
        $recentAvg = array_sum($recent) / count($recent);
        $olderAvg = array_sum($older) / count($older);
        
        $trendDiff = $olderAvg - $recentAvg;
        
        return (int) ($trendDiff * 10);
    }
    
    private function getLatestAssessment($user): array
    {
        $totalAssessments = $user->bookings()
            ->where('status', Booking::CONFIRMED)
            ->where('start_time', '<', now())
            ->whereNotNull('risk_level')
            ->whereNotNull('healthcare_comments')
            ->count();
            
        $latestAssessment = $user->bookings()
            ->where('status', Booking::CONFIRMED)
            ->where('start_time', '<', now())
            ->whereNotNull('risk_level')
            ->whereNotNull('healthcare_comments')
            ->orderBy('start_time', 'desc')
            ->first();
        
        if (!$latestAssessment) {
            return [
                'risk_level' => null,
                'assessment_date' => null,
                'total_assessments' => 0,
            ];
        }
        
        return [
            'risk_level' => $latestAssessment->risk_level,
            'assessment_date' => $latestAssessment->start_time,
            'total_assessments' => $totalAssessments,
        ];
    }
    
    private function calculateReadingTime($content): int
    {
        $wordCount = str_word_count(strip_tags($content));
        $wordsPerMinute = 200;
        return max(1, ceil($wordCount / $wordsPerMinute));
    }
    
    private function generateExcerpt($content, $length = 150): string
    {
        $content = strip_tags($content);
        if (strlen($content) <= $length) {
            return $content;
        }
        
        $excerpt = substr($content, 0, $length);
        $lastSpace = strrpos($excerpt, ' ');
        
        if ($lastSpace !== false) {
            $excerpt = substr($excerpt, 0, $lastSpace);
        }
        
        return $excerpt . '...';
    }
}
