<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Application;
use Illuminate\Support\Facades\Log;

class TestController extends Controller
{
  public function show(Application $application)
  {
    // Add Authentication Check
    if (session('applicant_id') != $application->id) {
      return redirect()->route('test.login')->with('error', 'Silakan login terlebih dahulu untuk mengakses tes ini.');
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
      $elapsed = now()->diffInSeconds($application->part2_started_at);
      $remaining = max(0, $timeLimit - $elapsed);

      Log::info("Part 2 Timer: Start=" . $application->part2_started_at . ", Now=" . now() . ", Elapsed=$elapsed, Remaining=$remaining");

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
      $elapsed = now()->diffInSeconds($application->part1_started_at);
      $remaining = max(0, $timeLimit - $elapsed);
      
      Log::info("Part 1 Timer: Start=" . $application->part1_started_at . ", Now=" . now() . ", Elapsed=$elapsed, Remaining=$remaining");

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
      return redirect()->route('test.login')->with('error', 'Silakan login terlebih dahulu untuk mengakses tes ini.');
    }

    if ($application->test_score !== null) {
      return redirect()->route('home')->with('info', 'Anda sudah menyelesaikan tes ini.');
    }

    return view('test.welcome', compact('application'));
  }

  public function startTest(Application $application)
  {
    if (session('applicant_id') != $application->id) {
      return redirect()->route('test.login')->with('error', 'Silakan login terlebih dahulu untuk mengakses tes ini.');
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
      return redirect()->route('test.login')->with('error', 'Sesi Anda tidak valid. Silakan login kembali.');
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
      return redirect()->route('test.login')->with('error', 'Silakan login terlebih dahulu untuk mengakses tes ini.');
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
      return redirect()->route('test.login')->with('error', 'Sesi Anda tidak valid. Silakan login kembali.');
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
    
    // Merge both parts
    $allAnswers = array_merge($part1Answers, $part2Answers);
    
    $correctCount = 0;
    $totalQuestions = $questions->count();

    foreach ($questions as $question) {
      $userAnswer = $allAnswers[$question->id] ?? null;
      if ($userAnswer === $question->correct_answer) {
        $correctCount++;
      }
    }

    $score = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100) : 0;

    $application->update(['test_score' => $score]);

    // Don't show score to user
    return view('test.result');
  }
}
