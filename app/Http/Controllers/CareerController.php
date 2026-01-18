<?php

namespace App\Http\Controllers;

use App\Models\JobVacancy;
use App\Models\Application;
use Illuminate\Http\Request;
use App\Jobs\ProcessCvScreening;

class CareerController extends Controller
{
  public function index()
    {
      $jobs = JobVacancy::where('status', 'approved')
          ->where('is_published', true)
          ->latest()
          ->get();
      $jobsByDepartment = collect(['All Positions' => $jobs]);
      return view('career.index', compact('jobsByDepartment'));
    }

    public function show($slug)
    {
      $job = JobVacancy::where('slug', $slug)
          ->where('status', 'approved')
          ->where('is_published', true)
          ->firstOrFail();
      return view('career.show', compact('job'));
    }

    public function apply(Request $request, $slug)
    {
      $job = JobVacancy::where('slug', $slug)->firstOrFail();
      $validated = $request->validate([
          'full_name' => 'required|string|max:255',
          'email' => 'required|email',
          'phone' => 'required|string',
          'cv' => 'required|file|mimes:pdf|max:2048', // Max 2MB
      ]);
      // Upload CV
      $cvPath = $request->file('cv')->store('applications/cvs', 'public');
      
      $application = Application::create([
        'job_vacancy_id' => $job->id,
        'full_name' => $validated['full_name'],
        'email' => $validated['email'],
        'phone' => $validated['phone'],
        'cv_path' => $cvPath,
      ]);

      // Dispatch job
      ProcessCvScreening::dispatch($application);
      return redirect()->route('career.index')->with('success', 'Application submitted successfully!');
    }
}
