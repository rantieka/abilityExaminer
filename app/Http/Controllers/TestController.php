<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Application;
use Illuminate\Support\Facades\Log;

class TestController extends Controller
{
  public function verifyToken($token)
  {
      $application = Application::where('test_token', $token)->first();

      if (!$application) {
          return redirect()->route('home')->with('error', 'Token tes tidak valid.');
      }

      if ($application->token_expires_at && now()->greaterThan($application->token_expires_at)) {
          return redirect()->route('home')->with('error', 'Token tes sudah kadaluarsa. Silakan hubungi HR.');
      }
      
      if ($application->test_score !== null) {
           return redirect()->route('home')->with('info', 'Anda sudah menyelesaikan tes ini.');
      }

      // Log the user in for the test session
      session(['applicant_id' => $application->id]);
      
      return redirect()->route('test.welcome', $application->id);
  }

  public function show(Application $application)
  {
    // Add Authentication Check
    if (session('applicant_id') != $application->id) {
       // Check if we are in dev/preview mode or if it's a legacy link?
       // For now, redirect to a generic error or login page (though login page might be deprecated)
       return redirect()->route('home')->with('error', 'Sesi tidak valid atau kadaluarsa. Silakan gunakan link dari email Anda kembali.');
    }

    // check if applicant already took the test
    if ($application->test_score !== null) {
      return redirect()->route('home')->with('info', 'Anda sudah menyelesaikan tes ini.');
    }

    $jobVacancy = $application->jobVacancy;
    
    // Check if Part 1 is completed
    if ($application->part1_completed_at) {
      // Show Part 2 (Technical/Case Study)
      if (!$application->part2_started_at) {
          $application->update(['part2_started_at' => now()]);
      }
      
      $timeLimit = 30 * 60; // 30 minutes in seconds;
      
      $startTime = \Carbon\Carbon::parse($application->part2_started_at);
      $now = now();
      $elapsed = $now->diffInSeconds($startTime, true); // true = absolute
      
      // If start time is somehow in future (timezone diff?), treat elapsed as 0
      if ($startTime > $now) {
          $elapsed = 0;
      }
      
      $remaining = max(0, $timeLimit - $elapsed);
      
      Log::info("Part 2 Timer Refined: Start=" . $startTime . ", Now=" . $now . ", Elapsed=$elapsed, Remaining=$remaining");

      $questions = $jobVacancy->questions()
          ->where('is_active', true)
          ->where('section', 'technical')
          ->get();
      return view('test.part2', compact('application', 'questions', 'remaining'));
    } else {
      // Check if user has seen welcome page (using session)
      if (!session('test_started_' . $application->id)) {
        return redirect()->route('test.welcome', $application->id);
      }
      
      // Show Part 1 (Knowledge & Foundation)
      if (!$application->part1_started_at) {
          $application->update(['part1_started_at' => now()]);
      }

      $timeLimit = 30 * 60; // 30 minutes in seconds;
      
      $startTime = \Carbon\Carbon::parse($application->part1_started_at);
      $now = now();
      $elapsed = $now->diffInSeconds($startTime, true); // true = absolute
      
      // If start time is somehow in future (timezone diff?), treat elapsed as 0
      if ($startTime > $now) {
          $elapsed = 0;
      }
      
      $remaining = max(0, $timeLimit - $elapsed);
      
      Log::info("Part 1 Timer Refined: Start=" . $startTime . ", Now=" . $now . ", Elapsed=$elapsed, Remaining=$remaining");

      $questions = $jobVacancy->questions()
          ->where('is_active', true)
          ->where('section', 'knowledge')
          ->get();
      return view('test.part1', compact('application', 'questions', 'remaining'));
    }
  }

  public function welcome(Application $application)
  {
    if (session('applicant_id') != $application->id) {
      return redirect()->route('home')->with('error', 'Silakan gunakan link tes yang dikirimkan ke email Anda.');
    }

    if ($application->test_score !== null) {
      return redirect()->route('home')->with('info', 'Anda sudah menyelesaikan tes ini.');
    }

    return view('test.welcome', compact('application'));
  }

  public function startTest(Application $application)
  {
    if (session('applicant_id') != $application->id) {
      return redirect()->route('home')->with('error', 'Sesi Anda tidak valid. Silakan gunakan link dari email Anda kembali.');
    }

    // Mark that user has started the test
    session(['test_started_' . $application->id => true]);
    
    if (!$application->part1_started_at) {
        $application->update(['part1_started_at' => now()]);
    }
    
    return redirect()->route('test.show', $application->id);
  }

  public function submitPart1(Request $request, Application $application)
  {
    if (session('applicant_id') != $application->id) {
       return redirect()->route('home')->with('error', 'Sesi Anda tidak valid. Silakan gunakan link dari email Anda kembali.');
    }

    if ($application->test_score !== null) {
      return redirect()->route('home')->with('info', 'Anda sudah menyelesaikan tes ini.');
    }

    $answers = $request->input('answers', []);
    
    $application->update([
      'part1_answers' => $answers,
      'part1_completed_at' => now()
    ]);
    
    return redirect()->route('test.instruction', $application->id);
  }

  public function instruction(Application $application)
  {
    if (session('applicant_id') != $application->id) {
       return redirect()->route('home')->with('error', 'Sesi Anda tidak valid. Silakan gunakan link dari email Anda kembali.');
    }

    if ($application->test_score !== null) {
      return redirect()->route('home')->with('info', 'Anda sudah menyelesaikan tes ini.');
    }

    if (!$application->part1_completed_at) {
      return redirect()->route('test.show', $application->id);
    }

    return view('test.instruction', compact('application'));
  }

  public function submit(Request $request, Application $application)
  {
    if (session('applicant_id') != $application->id) {
       return redirect()->route('home')->with('error', 'Sesi Anda tidak valid. Silakan gunakan link dari email Anda kembali.');
    }

    if ($application->test_score !== null) {
      return redirect()->route('home')->with('info', 'Anda sudah menyelesaikan tes ini.');
    }

    $jobVacancy = $application->jobVacancy;
    $questions = $jobVacancy->questions()->where('is_active', true)->get();
    
    // Get Part 2 answers from current request
    $part2Answers = $request->input('answers', []);
    
    // Get Part 1 answers from database
    $part1Answers = $application->part1_answers ?? [];
    
    // Merge both parts (use + based union to preserve numeric keys)
    $allAnswers = $part1Answers + $part2Answers;
    
    $totalPossiblePoints = 0;
    $earnedPoints = 0;

    foreach ($questions as $question) {
      // Tentukan bobot berdasarkan difficulty
      $weight = 5; // Default medium
      if ($question->difficulty === 'easy') $weight = 2;
      if ($question->difficulty === 'hard') $weight = 10;

      $totalPossiblePoints += $weight;

      $userAnswer = $allAnswers[$question->id] ?? null;
      if ($userAnswer === $question->correct_answer) {
        $earnedPoints += $weight;
      }
    }

    $score = $totalPossiblePoints > 0 ? round(($earnedPoints / $totalPossiblePoints) * 100) : 0;

    $application->update([
        'test_score' => $score,
        'part2_answers' => $part2Answers,
        'test_completed_at' => now()
    ]);

    // Don't show score to user
    return view('test.result');
  }
}
