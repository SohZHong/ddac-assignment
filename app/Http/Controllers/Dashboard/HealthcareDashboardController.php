<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Booking;
use App\Models\Blog;
use App\Models\Quiz;
use App\Models\QuizResponse;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;
class HealthcareDashboardController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();

        // Today's bookings
        $todayBookingsCount = $user->healthcareBookings()
            ->whereDate('bookings.start_time', now())
            ->count();

        // Total bookings count
        $totalBookings = $user->healthcareBookings()->count();

        // Number of unique patients (confirmed = 1)
        $confirmedPatients = $user->healthcareBookings()
            ->where('status', Booking::CONFIRMED)
            ->distinct('patient_id')
            ->count('patient_id');

        // Number of pending bookings (status = 0)
        $pendingBookings = $user->healthcareBookings()
            ->where('status', Booking::PENDING)
            ->count();

        // Number of schedules
        $totalSchedules = $user->schedules()->count();

        // Total hours available across schedules
        $totalHoursAvailable = $user->schedules()
            ->selectRaw('SUM(EXTRACT(EPOCH FROM (end_time - start_time))/3600) as hours')
            ->value('hours') ?? 0;

        // Quiz responses
        $quizResponses = QuizResponse::whereIn(
            'quiz_id',
            $user->quizzes()->pluck('id')
        )->count();

        return Inertia::render('Healthcare/Dashboard', [
            'profile' => $user->only(['id', 'name', 'email']),
            'stats' => [
                'todayBookingsCount'=> $todayBookingsCount,
                'totalBookings'     => $totalBookings,
                'confirmedPatients' => $confirmedPatients,
                'pendingBookings'   => $pendingBookings,
                'totalSchedules'    => $totalSchedules,
                'totalHours'        => $totalHoursAvailable,
                'quizResponses'     => $quizResponses,
            ],
        ]);
    }

    public function patient(): Response
    {
        $user = auth()->user();

        $patients = User::whereHas('bookings', function ($query) use ($user) {
            $query->whereHas('schedule', fn($q) => $q->where('healthcare_id', $user->id));
        })
        ->withCount([
            'bookings as bookings_count' => function ($query) use ($user) {
                $query->whereHas('schedule', fn($q) => $q->where('healthcare_id', $user->id));
            },
            'bookings as pending_bookings_count' => function ($query) use ($user) {
                $query->whereHas('schedule', fn($q) => $q->where('healthcare_id', $user->id))
                    ->where('status', Booking::PENDING);
            }
        ])
        ->orderBy('name')
        ->paginate(15)
        ->withQueryString();

        return Inertia::render('Healthcare/Patient/Index', [
            'patients' => $patients,
        ]);
    }

    public function blog(): Response
    {
        $blogs = Blog::where('author_id', auth()->id())
            ->orderByDesc('published_at')
            ->paginate(15)
            ->through(fn ($blog) => [
                'id'            => $blog->id,
                'title'         => $blog->title,
                'slug'          => $blog->slug,
                'cover_image'   => $blog->cover_image,
                'author'        => [
                    'id'   => optional($blog->author)->id   ?? 1,
                    'name' => optional($blog->author)->name ?? 'Anonymous',
                ],
                'published_at'  => $blog->published_at,
                'status'        => $blog->status
            ]);
        return Inertia::render('Healthcare/Blog/Index', [
            'blogs' => $blogs
        ]);
    }

    /**
     * Page for creating a new blog
     */
    public function createBlog(): Response
    {
        return Inertia::render('Healthcare/Blog/Create');
    }

    public function storeBlog(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'cover_image' => 'nullable|image|max:2048',
            'status' => 'in:' . implode(',', [
                Blog::STATUS_DRAFT,
                Blog::STATUS_PUBLISHED,
            ]),
        ]);

        $validated['slug'] = Str::slug($validated['title']);
        $validated['author_id'] = auth()->id();

        if ($request->hasFile('cover_image')) {
            // Store in public folder for now
            $validated['cover_image'] = $request->file('cover_image')->store('blogs', 'public');
        }

        if ($validated['status'] === Blog::STATUS_PUBLISHED) {
            $validated['published_at'] = now();
        }

        Blog::create($validated);

        return redirect()->route('healthcare.blog.index')->with('success', 'Blog created successfully');
    }

    /**
     * Show the form for editing the specified blog.
     */
    public function editBlog(string $id): Response
    {
        $blog = Blog::findOrFail($id);
        // Check authorization
        $this->authorize('update', $blog);

        return Inertia::render('Healthcare/Blog/Edit', [
            'blog' => [
                'id'           => $blog->id,
                'title'        => $blog->title,
                'slug'         => $blog->slug,
                'cover_image'  => $blog->cover_image,
                'content'      => $blog->content,
                'author'       => [
                    'id'   => $blog->author?->id ?? 0,
                    'name' => $blog->author?->name ?? 'Anonymous',
                ],
                'published_at' => $blog->published_at,
                'status'       => $blog->status,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateBlog(Request $request, string $id)
    {
        $blog = Blog::findOrFail($id);

        // Check if user is authorized to update
        $this->authorize('update', $blog);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes',
            'cover_image' => 'nullable|image|max:2048',
            'status' => 'in:' . implode(',', [
                Blog::STATUS_DRAFT,
                Blog::STATUS_PUBLISHED,
            ]),
        ]);

        if (isset($validated['title'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        if ($request->hasFile('cover_image')) {
            // Delete image in public folder (Will be replaced later)
            Storage::disk('public')->delete($blog->cover_image);
            $validated['cover_image'] = $request->file('cover_image')->store('blogs', 'public');
        }

        if (isset($validated['status']) && $validated['status'] === Blog::STATUS_PUBLISHED) {
            $validated['published_at'] = now();
        }

        $blog->update($validated);

        return redirect()->route('healthcare.blog.index')->with('success', 'Blog updated successfully');
    }

    public function schedule(): Response
    {
        $schedules = Schedule::where('healthcare_id', auth()->id())
                ->where('start_time', '>=', now(config('app.timezone')))
                ->orderBy('start_time', 'asc')
                ->get()
                ->map(fn ($schedule) => [
                'id'           => $schedule->id,
                'start'        => $schedule->start_time,
                'end'          => $schedule->end_time,
                'day_of_week'  => $schedule->day_of_week,
            ]);

        return Inertia::render('Healthcare/Schedule/Index', [
            'schedules' => $schedules,
        ]);
    }

    public function appointment(): Response
    {
        $bookings = Booking::with('patient:id,name,email')
            ->orderBy('start_time', 'asc')
            ->paginate(15)
            ->through(fn ($booking) => [
                'id'          => $booking->id,
                'schedule_id' => $booking->schedule_id,
                'patient'     => [
                    'id'    => $booking->patient->id,
                    'name'  => $booking->patient->name,
                    'email' => $booking->patient->email,
                ],
                'start_time'  => $booking->start_time,
                'end_time'    => $booking->end_time,
                'status'      => $booking->status,
                'quizResponse'=> $booking->quizResponse,
            ]);

        return Inertia::render('Healthcare/Booking/Index', [
            'bookings' => $bookings
        ]);
    }

    public function appointmentResponse(string $bookingId, string $quizResponseId): Response
    {
        $quizResponse = QuizResponse::with([
                'booking.patient:id,name,email',
                'quiz' // only quiz meta, not questions here
            ])
            ->where('id', $quizResponseId)
            ->where('booking_id', $bookingId)
            ->firstOrFail();

        // Map answers to a dictionary for easy lookup
        $answersMap = collect($quizResponse->answers ?? [])
            ->mapWithKeys(fn ($answer) => [$answer['question_id'] => $answer['answer']])
            ->toArray();

        // Paginate quiz questions separately
        $questions = $quizResponse->quiz
            ->questions()
            ->select('id', 'quiz_id', 'question_text', 'type', 'options')
            ->paginate(10)
            ->through(function ($q) use ($answersMap) {
                return [
                    'id'            => $q->id,
                    'quiz_id'       => $q->quiz_id,
                    'question_text' => $q->question_text,
                    'type'          => $q->type,
                    'options'       => $q->options,
                    'answer'        => $answersMap[$q->id] ?? null,
                ];
            });

        $responseData = [
            'id'           => $quizResponse->id,
            'quiz_id'      => $quizResponse->quiz_id,
            'booking_id'   => $quizResponse->booking_id,
            'completed_at' => $quizResponse->completed_at,
            'booking'      => [
                'id'       => $quizResponse->booking->id,
                'patient'  => [
                    'id'    => $quizResponse->booking->patient->id,
                    'name'  => $quizResponse->booking->patient->name,
                    'email' => $quizResponse->booking->patient->email,
                ],
                'healthcare_comments' => $quizResponse->booking->healthcare_comments,
                'risk_level'          => $quizResponse->booking->risk_level,
            ],
            'quiz'         => [
                'id'            => $quizResponse->quiz->id,
                'healthcare_id' => $quizResponse->quiz->healthcare_id,
                'title'         => $quizResponse->quiz->title,
                'description'   => $quizResponse->quiz->description,
                'active'        => $quizResponse->quiz->active,
            ],
        ];

        return Inertia::render('Healthcare/Booking/Response', [
            'quizResponse' => $responseData,
            'questions'    => $questions,
        ]);
    }

    /**
     * Submit comment and risk level by healthcare professional
     */
    public function reviewAppointmentResponse(Request $request, string $id)
    {
        $booking = Booking::findOrFail($id);

        $this->authorize('reviewAppointmentResponse', $booking);

        $validated = $request->validate([
            'healthcare_comments' => 'nullable|string',
            'risk_level' => 'nullable|in:' . implode(',', [
                Booking::LOW,
                Booking::MID,
                Booking::HIGH,
            ]),
        ]);

        $booking->update($validated);

        return redirect()->route('healthcare.appointment.index')->with('success', 'Consultant review saved.');
    }

    public function quizzes(): Response
    {
        $quizzes = Quiz::with('questions')
            ->where('healthcare_id', auth()->id())
            ->paginate(15)
            ->through(fn ($quiz) => [
                'id'            => $quiz->id,
                'healthcare_id' => $quiz->healthcare_id,
                'title'         => $quiz->title,
                'description'   => $quiz->description,
                'questions'     => $quiz->questions->map(fn ($q) => [
                    'id'            => $q->id,
                    'quiz_id'       => $q->quiz_id,
                    'question_text' => $q->question_text,
                    'type'          => $q->type,
                    'options'       => $q->options,
            ]),
                'active'        => $quiz->active,
        ]);

       return Inertia::render('Healthcare/Quiz/Index', [
            'quizzes' => $quizzes,
        ]);
    }

    public function quiz(string $id): Response
    {
        $quiz = Quiz::findOrFail($id);

        $this->authorize('healthcareView', [Quiz::class, $quiz]);
        
        $questions = $quiz->questions()
            ->select('id', 'quiz_id', 'question_text', 'type', 'options')
            ->paginate(15);
        
        return Inertia::render('Healthcare/Quiz/Question', [
            'quiz' => [
                'id'            => $quiz->id,
                'healthcare_id' => $quiz->healthcare_id,
                'title'         => $quiz->title,
                'description'   => $quiz->description,
            ],
            'questions'     => $questions,
        ]);
    }
}
