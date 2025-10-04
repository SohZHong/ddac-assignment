<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProfessionalApplicationController extends Controller
{
    /**
     * Show the professional application form.
     */
    public function create(): Response
    {
        $user = Auth::user();
        $requestedRoleLabel = null;
        if ($user->requested_role) {
            try {
                $requestedRoleLabel = UserRole::from($user->requested_role)->label();
            } catch (\Throwable $e) {
                $requestedRoleLabel = $user->requested_role;
            }
        }

        return Inertia::render('settings/ProfessionalApplication', [
            'available_roles' => [
                [
                    'value' => UserRole::HEALTHCARE_PROFESSIONAL->value,
                    'label' => UserRole::HEALTHCARE_PROFESSIONAL->label(),
                ],
                [
                    'value' => UserRole::HEALTH_CAMPAIGN_MANAGER->value,
                    'label' => UserRole::HEALTH_CAMPAIGN_MANAGER->label(),
                ],
            ],
            'pending_application' => [
                'is_pending' => $user->isPending(),
                'requested_role' => $user->requested_role,
                'requested_role_label' => $requestedRoleLabel,
            ],
            'application_status' => $user->approval_status,
        ]);
    }

    /**
     * Submit a professional application.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'role' => ['required', Rule::in([
                UserRole::HEALTHCARE_PROFESSIONAL->value,
                UserRole::HEALTH_CAMPAIGN_MANAGER->value,
            ])],
            'credentials' => ['required', 'array'],
            'credentials.*.type' => 'required|string|max:255',
            'credentials.*.number' => 'required|string|max:255',
            'credentials.*.issuer' => 'required|string|max:255',
            'credentials.*.issue_date' => 'required|date',
            'credentials.*.expiry_date' => 'nullable|date|after:credentials.*.issue_date',
            'credentials.*.document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'credentials.*.additional_info' => 'nullable|string|max:1000',
        ]);

        /** @var User $user */
        $user = Auth::user();

        // Store requested role (do not change actual role yet), set pending status
        $user->update([
            'requested_role' => $request->role,
            'approval_status' => 'pending',
            'approved_at' => null,
            'rejection_reason' => null, // Clear any previous rejection reason
        ]);

        $disk = config('filesystems.default');
        Log::info('Professional application submission received', [
            'user_id' => $user->id,
            'disk' => $disk,
            'credential_count' => is_array($request->input('credentials')) ? count($request->input('credentials')) : 0,
        ]);

        // Store credentials
        $credentialsInput = $request->input('credentials', []);
        foreach ($credentialsInput as $index => $credential) {
            $file = $request->file("credentials.$index.document");

            if (! $file) {
                Log::warning('Credential document missing in request', [
                    'user_id' => $user->id,
                    'index' => $index,
                ]);
                continue;
            }

            $bucket = config('filesystems.disks.s3.bucket');
            try {
                $path = Storage::disk($disk)->putFile('credentials', $file, ['visibility' => 'public']);
            } catch (\Throwable $e) {
                Log::error('Exception while storing credential document', [
                    'user_id' => $user->id,
                    'index' => $index,
                    'disk' => $disk,
                    'bucket' => $bucket,
                    'error' => $e->getMessage(),
                ]);
                throw ValidationException::withMessages([
                    "credentials.$index.document" => 'Failed to upload document (storage error). Check S3 configuration and permissions.',
                ]);
            }

            if (! $path) {
                Log::error('Failed to store credential document (no path returned)', [
                    'user_id' => $user->id,
                    'index' => $index,
                    'disk' => $disk,
                    'bucket' => $bucket,
                    'has_key' => (bool) env('AWS_ACCESS_KEY_ID'),
                    'has_secret' => (bool) env('AWS_SECRET_ACCESS_KEY'),
                ]);
                throw ValidationException::withMessages([
                    "credentials.$index.document" => 'Failed to upload document. Please verify FILESYSTEM_DISK and AWS_* env vars.',
                ]);
            }
            Log::info('Stored credential document', [
                'user_id' => $user->id,
                'index' => $index,
                'original' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'path' => $path,
            ]);

            $user->professionalCredentials()->create([
                'credential_type' => $credential['type'],
                'credential_number' => $credential['number'],
                'issuing_authority' => $credential['issuer'],
                'issue_date' => $credential['issue_date'],
                'expiry_date' => $credential['expiry_date'] ?? null,
                'document_path' => $path,
                'additional_info' => $credential['additional_info'] ?? null,
            ]);
        }

        return redirect()->route('approval.pending');
    }
}
