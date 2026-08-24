<?php

namespace App\Http\Controllers;

use App\Models\JobVacancy;
use App\Models\Application;
use App\Models\User;
use Illuminate\Http\Request;
use App\Jobs\ProcessCvScreening;
use Illuminate\Support\Str;

class CareerController extends Controller
{
  public function index()
    {
      $jobs = JobVacancy::where('status', 'approved')
          ->where('is_published', true)
          ->whereNull('archived_at')
          ->where(fn($query) => $query->whereNull('published_until')->orWhere('published_until', '>=', now()))
          ->latest()
          ->get();
      $jobsByDepartment = collect(['Available Positions' => $jobs]);
      return view('career.index', compact('jobsByDepartment'));
    }

    public function show($slug)
    {
      $job = JobVacancy::where('slug', $slug)
          ->where('status', 'approved')
          ->where('is_published', true)
          ->whereNull('archived_at')
          ->where(fn($query) => $query->whereNull('published_until')->orWhere('published_until', '>=', now()))
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

      // Check for existing application for this job
      $existingApplication = Application::where('job_vacancy_id', $job->id)
        ->where(function($query) use ($validated) {
          $query->where('email', $validated['email'])
            ->orWhere('phone', $validated['phone']);
        })->exists();

      if ($existingApplication) {
          return back()->with('error', 'You have already submitted an application for this position.')->withInput();
      }

      // Upload CV
      $cvPath = $request->file('cv')->store('applications/cvs', 'public');
      
      // Find or create User by email
      $user = User::firstOrCreate(
        ['email' => $validated['email']],
        [
          'name' => $validated['full_name'],
          'password' => bcrypt(Str::random(16)),
          'email_verified_at' => now(),
        ]
      );
      
      $application = Application::create([
        'job_vacancy_id' => $job->id,
        'user_id' => $user->id,
        'full_name' => $validated['full_name'],
        'email' => $validated['email'],
        'phone' => $validated['phone'],
        'cv_path' => $cvPath,
      ]);

      // Dispatch job
      ProcessCvScreening::dispatch($application);

      // Send Real-time Notification to Admins
      $admins = \App\Models\User::whereHas('roles', function($q) {
          $q->whereIn('name', ['super_admin', 'admin', 'hr']); // Adjust roles if needed
      })->get();

      // Fallback: If no users have those specific roles, notify the first registered user
      if ($admins->isEmpty()) {
          $admins = \App\Models\User::first() ? collect([\App\Models\User::first()]) : collect();
      }

      \Filament\Notifications\Notification::make()
          ->title('Lamaran Baru Masuk!')
          ->body("{$application->full_name} melamar posisi {$job->title}.")
          ->icon('heroicon-o-document-text')
          ->success()
          ->actions([
              \Filament\Actions\Action::make('view_application')
                  ->label('Lihat')
                  ->button()
                  ->markAsRead()
                  ->url(\App\Filament\Resources\Applications\ApplicationResource::getUrl('view', ['record' => $application->id])),
          ])
          ->sendToDatabase($admins);

      return redirect()->route('career.index')->with('success', 'Application submitted successfully!');
    }
}
