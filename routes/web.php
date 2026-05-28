<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\CareerController;

Route::get('/', [LandingController::class, 'index'])->name('home');

Route::get('/test-bootstrap', function () {
  return view('test-bootstrap');
});

// Career
Route::get('/career', [CareerController::class, 'index'])->name('career.index');
Route::get('/career/{slug}', [CareerController::class, 'show'])->name('career.show');
Route::post('/career/{slug}/apply', [CareerController::class, 'apply'])->name('career.apply');

use App\Http\Controllers\TestController;
use App\Http\Controllers\TestAuthController;

// Test Auth
Route::get('/test/login', [TestAuthController::class, 'index'])->name('test.login');
Route::post('/test/login', [TestAuthController::class, 'authenticate'])->name('test.authenticate');

// Test Access via Token
Route::get('/test/access/{token}', [TestController::class, 'verifyToken'])->name('test.access');

// Protected Test Routes (Middleware check in Controller)
Route::get('/test/{application}/welcome', [TestController::class, 'welcome'])->name('test.welcome');
Route::post('/test/{application}/start', [TestController::class, 'startTest'])->name('test.start');
Route::get('/test/{application}', [TestController::class, 'show'])->name('test.show');
Route::post('/test/{application}/part1', [TestController::class, 'submitPart1'])->name('test.submitPart1');
Route::get('/test/{application}/instruction', [TestController::class, 'instruction'])->name('test.instruction');
Route::post('/test/{application}', [TestController::class, 'submit'])->name('test.submit');

// Email Preview Routes (Development Only)
// Email Preview Routes (Accessible in all envs for now)
Route::get('/email-preview/accepted/{application}', function ($id) {
  $application = \App\Models\Application::findOrFail($id);
  return new \App\Mail\ApplicationAccepted($application);
})->name('email.preview.accepted');

Route::get('/email-preview/rejected/{application}', function ($id) {
  $application = \App\Models\Application::findOrFail($id);
  return new \App\Mail\ApplicationRejected($application);
})->name('email.preview.rejected');

Route::get('/email-preview/hired/{application}', function ($id) {
  $application = \App\Models\Application::findOrFail($id);
  return new \App\Mail\SelectionResultHired($application);
})->name('email.preview.hired');

Route::get('/email-preview/selection-rejected/{application}', function ($id) {
  $application = \App\Models\Application::findOrFail($id);
  return new \App\Mail\SelectionResultRejected($application);
})->name('email.preview.selection_rejected');

// UI Preview Routes (Development Only)
Route::get('/test-preview/part1', function () {
  $application = new \App\Models\Application();
  $application->id = 'preview-id';
  $jobVacancy = new \App\Models\JobVacancy(['title' => 'Preview Job Position']);
  $application->setRelation('jobVacancy', $jobVacancy);

  // Dummy Questions for Preview
  $questions = collect([
    (object)[
      'id' => 1,
      'question_text' => 'Contoh Pertanyaan Knowledge 1?',
      'options' => ['A' => 'Pilihan A', 'B' => 'Pilihan B', 'C' => 'Pilihan C', 'D' => 'Pilihan D'],
    ],
    (object)[
      'id' => 2,
      'question_text' => 'Contoh Pertanyaan Knowledge 2?',
      'options' => ['A' => 'Pilihan A', 'B' => 'Pilihan B', 'C' => 'Pilihan C', 'D' => 'Pilihan D'],
    ],
  ]);

  return view('test.part1', compact('application', 'questions'));
});

Route::get('/test-preview/part2', function () {
  $application = new \App\Models\Application();
  $application->id = 'preview-id';
  $jobVacancy = new \App\Models\JobVacancy(['title' => 'Preview Job Position']);
  $application->setRelation('jobVacancy', $jobVacancy);

  // Dummy Questions for Preview
  $questions = collect([
    (object)[
      'id' => 3,
      'question_text' => 'Contoh Pertanyaan Teknis 1?',
      'options' => ['A' => 'Solusi A', 'B' => 'Solusi B', 'C' => 'Solusi C', 'D' => 'Solusi D'],
    ],
  ]);

  return view('test.part2', compact('application', 'questions'));
});

// Temporary Reset Route (Delete after debugging)
Route::get('/test-reset/{id}', function ($id) {
  if(session()->has('applicant_id')) {
    $app = \App\Models\Application::find(session('applicant_id'));
    if($app) {
     $app->update([
       'part1_started_at' => null, 
       'part2_started_at' => null,
       'part1_completed_at' => null,
       'test_score' => null,
       'part1_answers' => null
     ]);
     session()->forget('test_started_' . $app->id);
     return "Reset success! <a href='/test/{$app->id}'>Go back to test</a>";
    }
  }
  return "Application not found or session expired.";
});

Route::get('/test-send-email', function () {
  $to = request('to', 'rantieka67@gmail.com');
  
  try {
    \Illuminate\Support\Facades\Mail::raw('Halo, ini adalah email uji coba dari aplikasi Laravel Ability Examiner untuk memverifikasi pengaturan SMTP.', function ($message) use ($to) {
      $message->to($to)
              ->subject('Uji Coba Pengiriman Email SMTP');
    });
    return "Email berhasil dikirim ke: " . e($to) . ". Silakan cek folder inbox atau spam Anda.";
  } catch (\Exception $e) {
    return "Gagal mengirim email. Error: " . e($e->getMessage());
  }
});

Route::get('/test-send-rejected', function () {
  $to = request('to', 'rantieka67@gmail.com');
  $application = \App\Models\Application::where('email', $to)->first() 
      ?? \App\Models\Application::first();
      
  if (!$application) {
    return "No applications in database to test with.";
  }
  
  try {
    \Illuminate\Support\Facades\Mail::to($to)->send(new \App\Mail\ApplicationRejected($application));
    return "Rejection email successfully sent to: " . e($to) . " (using candidate name: " . e($application->full_name) . ")";
  } catch (\Exception $e) {
    return "Failed to send rejection email. Error: " . e($e->getMessage());
  }
});

Route::get('/test-send-selection-rejected', function () {
  $to = request('to', 'rantieka67@gmail.com');
  $application = \App\Models\Application::where('email', $to)->first() 
      ?? \App\Models\Application::first();
      
  if (!$application) {
    return "No applications in database to test with.";
  }
  
  try {
    \Illuminate\Support\Facades\Mail::to($to)->send(new \App\Mail\SelectionResultRejected($application));
    return "Selection Rejection email successfully sent to: " . e($to) . " (using candidate name: " . e($application->full_name) . ")";
  } catch (\Exception $e) {
    return "Failed to send selection rejection email. Error: " . e($e->getMessage());
  }
});
