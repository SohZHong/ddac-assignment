<?php

namespace App\Http\Controllers\ConsultationReport;

use App\Models\Booking;
use App\Http\Controllers\Controller;
use App\Models\ConsultationReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ConsultationReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:users,id',
            'report'     => 'required|file|mimes:pdf,doc,docx,jpg,png|max:10240', // max 10MB
            'notes'      => 'nullable|string|max:1000', // optional notes
        ]);

        $this->authorize('manage', ConsultationReport::class);

        $disk = config('filesystems.default');

        // Store file
        $path = $request->file('report')->store('consultation_reports', $disk);

        // Create new report
        $report = ConsultationReport::create([
            'user_id'    => $request->patient_id,        
            'uploaded_by'=> auth()->id(),               
            'report_url' => $path,
            'notes'      => $request->notes,       
        ]);

        return response()->json([
            'message' => 'Consultation report uploaded successfully',
            'report'  => $report,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(ConsultationReport $consultationReport)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ConsultationReport $consultationReport)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ConsultationReport $consultationReport)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ConsultationReport $consultationReport)
    {
        //
    }
}
