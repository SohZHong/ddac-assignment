<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\IncidentReport;
use App\Models\Blog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SystemActivityController extends Controller
{
    public function index(): Response
    {
        $logs = AdminLog::with('user')->latest()->limit(50)->get()->map(function($log) {
            $metadata = is_array($log->metadata) ? $log->metadata : [];
            $target = $metadata['target_name'] ?? ($log->target_type ? $log->target_type.'#'.$log->target_id : null);

            return [
                'id' => $log->id,
                'user' => $log->user?->name,
                'action' => $log->action,
                'target' => $target,
                'metadata' => $metadata,
                'ip' => $log->ip_address,
                'created_at' => $log->created_at->diffForHumans(),
            ];
        });

        // Ensure at least one incident exists to "function" out of the box
        if (IncidentReport::count() === 0) {
            IncidentReport::create([
                'type' => 'content_abuse',
                'title' => 'Welcome seed report',
                'description' => 'Reported spam content example',
                'reported_by' => optional(Auth::user())->id,
                'status' => 'open',
                'context' => ['example' => true],
            ]);
        }
        $incidents = IncidentReport::latest()->limit(20)->get();

        $content = Blog::latest()->limit(10)->get(['id','title','status','published_at']);

        // Seed dummy if empty
        if ($logs->isEmpty()) {
            $logs = collect([
                ['id' => 1, 'user' => 'System', 'action' => 'seed.logs', 'target' => null, 'metadata' => ['info' => 'dummy'], 'ip' => '127.0.0.1', 'created_at' => now()->diffForHumans()],
            ]);
        }
        // do not seed incidents in-memory; they are created in DB above if empty

        if ($content->isEmpty()) {
            $content = collect([
                ['id' => 1, 'title' => 'Welcome to CardioWise', 'status' => 1, 'published_at' => now()->toDateTimeString()],
            ]);
        }

        return Inertia::render('Admin/SystemActivity', [
            'logs' => $logs,
            'incidents' => $incidents,
            'content' => $content,
        ]);
    }

    public function resolveIncident(IncidentReport $incident): RedirectResponse
    {
        $incident->update(['status' => 'resolved']);

        // Log admin action
        AdminLog::create([
            'user_id' => optional(Auth::user())->id,
            'action' => 'incident.resolved',
            'target_type' => IncidentReport::class,
            'target_id' => $incident->id,
            'metadata' => ['incident_id' => $incident->id, 'type' => $incident->type],
            'ip_address' => request()->ip(),
        ]);
        return back()->with('success', 'Incident resolved');
    }

    public function storeIncident(): RedirectResponse
    {
        request()->validate([
            'type' => 'required|string',
            'description' => 'required|string',
        ]);

        IncidentReport::create([
            'type' => request('type'),
            'title' => request('title') ?: null,
            'description' => request('description'),
            'reported_by' => optional(Auth::user())->id,
            'status' => 'open',
            'context' => request('context', []),
        ]);

        $incident = IncidentReport::latest('id')->first();
        AdminLog::create([
            'user_id' => optional(Auth::user())->id,
            'action' => 'incident.created',
            'target_type' => IncidentReport::class,
            'target_id' => $incident?->id,
            'metadata' => ['type' => $incident?->type, 'title' => $incident?->title],
            'ip_address' => request()->ip(),
        ]);

        return back()->with('success', 'Incident reported');
    }

    public function reopenIncident(IncidentReport $incident): RedirectResponse
    {
        $incident->update(['status' => 'open']);

        AdminLog::create([
            'user_id' => optional(Auth::user())->id,
            'action' => 'incident.reopened',
            'target_type' => IncidentReport::class,
            'target_id' => $incident->id,
            'metadata' => ['incident_id' => $incident->id, 'type' => $incident->type, 'title' => $incident->title],
            'ip_address' => request()->ip(),
        ]);
        return back()->with('success', 'Incident reopened');
    }
}


