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
    $questions = $jobVacancy->questions;

    return view('test.show', compact('application', 'questions'));
  }

  public function submit(Request $request, Application $application)
  {
    // Add Authentication Check
    if (session('applicant_id') != $application->id) {
      return redirect()->route('test.login')->with('error', 'Sesi Anda tidak valid. Silakan login kembali.');
    }

    if ($application->test_score !== null) {
      return redirect()->route('home')->with('info', 'Anda sudah menyelesaikan tes ini.');
    }

    $jobVacancy = $application->jobVacancy;
    $questions = $jobVacancy->questions;
    
    $answers = $request->input('answers', []);
    $correctCount = 0;
    $totalQuestions = $questions->count();

    foreach ($questions as $question) {
      $userAnswer = $answers[$question->id] ?? null;
      if ($userAnswer === $question->correct_answer) {
        $correctCount++;
      }
    }

    $score = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100) : 0;

    $application->update(['test_score' => $score]);

    return view('test.result', compact('score', 'correctCount', 'totalQuestions'));
  }
}
