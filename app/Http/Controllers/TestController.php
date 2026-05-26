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
          ->inRandomOrder($application->id) // Stable random order per applicant
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
          ->inRandomOrder($application->id) // Stable random order per applicant
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

    $breakdown = [
      'required'  => ['earned' => 0, 'possible' => 0, 'percentage' => 0],
      'preferred' => ['earned' => 0, 'possible' => 0, 'percentage' => 0],
      'bonus'     => ['earned' => 0, 'possible' => 0, 'percentage' => 0],
    ];

    foreach ($questions as $question) {
      // Determine weight based on difficulty
      $weight = 5; // Default medium
      if ($question->difficulty === 'easy') $weight = 2;
      if ($question->difficulty === 'hard') $weight = 10;

      $totalPossiblePoints += $weight;
      
      $category = $question->skill_category ?? 'required';
      if (isset($breakdown[$category])) {
          $breakdown[$category]['possible'] += $weight;
      }

      $userAnswer = $allAnswers[$question->id] ?? null;
      
      // Map numeric keys (0,1,2,3) to letters (A,B,C,D) for robust comparison
      $mappedUserAnswer = is_numeric($userAnswer) ? chr(65 + (int)$userAnswer) : $userAnswer;
      $mappedCorrectAnswer = is_numeric($question->correct_answer) ? chr(65 + (int)$question->correct_answer) : $question->correct_answer;

      if ($mappedUserAnswer === $mappedCorrectAnswer) {
        $earnedPoints += $weight;
        if (isset($breakdown[$category])) {
            $breakdown[$category]['earned'] += $weight;
        }
      }
    }

    // Calculate percentages for breakdown
    foreach ($breakdown as $key => $data) {
        $breakdown[$key]['percentage'] = $data['possible'] > 0 ? round(($data['earned'] / $data['possible']) * 100) : 0;
    }

    $score = $totalPossiblePoints > 0 ? round(($earnedPoints / $totalPossiblePoints) * 100) : 0;

    // Fetch live C4.5 prediction on test completion via PHP local C45Predictor (Weka J48 86% Accuracy)
    $c45Decision = \App\Services\C45Predictor::predict(
        (float) $application->ai_score,
        (float) $score
    );

    // Generate AI explanation rationale for Explainable AI (XAI)
    $c45Explanation = null;
    try {
        $aiThreshold = \App\Models\Setting::get('c45_ai_threshold', 57.0);
        $testThreshold = \App\Models\Setting::get('c45_test_threshold', 63.0);

        $groq = resolve(\App\Services\GroqService::class);
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are an expert HR evaluation assistant. Analyze the candidate C4.5 classification decision and explain the result. Respond with JSON format only: {"explanation": "your explanation in Indonesian"}',
            ],
            [
                'role' => 'user',
                'content' => "Candidate Name: {$application->full_name}\n" .
                             "AI Score (CV Screening): {$application->ai_score}\n" .
                             "Test Score (Exam): {$score}\n" .
                             "C4.5 Decision Result: {$c45Decision}\n\n" .
                             "Rules: Write a professional, encouraging, and clear 2-3 sentence explanation in Indonesian explaining why the C4.5 decision is {$c45Decision} berdasarkan aturan Weka berikut:\n" .
                             "1. Jika AI Score <= {$aiThreshold}, maka REJECTED.\n" .
                             "2. Jika AI Score > {$aiThreshold} dan Test Score <= {$testThreshold}, maka REJECTED.\n" .
                             "3. Jika AI Score > {$aiThreshold} dan Test Score > {$testThreshold}, maka ACCEPTED.\n" .
                             "Sebutkan nilai skor pelamar secara alami.",
            ]
        ];
        
        $aiResult = $groq->chat($messages, 0.5);
        $c45Explanation = $aiResult['explanation'] ?? null;
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error("Groq AI failed to generate C4.5 explanation: " . $e->getMessage());
    }

    if (empty($c45Explanation)) {
        $aiThreshold = \App\Models\Setting::get('c45_ai_threshold', 57.0);
        $testThreshold = \App\Models\Setting::get('c45_test_threshold', 63.0);
        
        if ($c45Decision === 'ACCEPTED') {
            $c45Explanation = "Berdasarkan analisis algoritma C4.5 Weka, kandidat {$application->full_name} direkomendasikan untuk diterima (ACCEPTED) karena AI Score (" . ($application->ai_score ?? 0) . ") melebihi {$aiThreshold} dan Test Score ({$score}) melebihi {$testThreshold}.";
        } else {
            $c45Explanation = "Berdasarkan analisis algoritma C4.5 Weka, kandidat {$application->full_name} belum direkomendasikan (REJECTED) karena akumulasi nilai AI Score dan Test Score belum memenuhi batas minimal kelulusan.";
        }
    }

    $aiAnalysis = $application->ai_analysis ?? [];
    $aiAnalysis['c45_explanation'] = $c45Explanation;

    $application->update([
        'test_score' => $score,
        'test_details' => $breakdown,
        'part2_answers' => $part2Answers,
        'test_completed_at' => now(),
        'c45_decision' => $c45Decision,
        'ai_analysis' => $aiAnalysis,
    ]);

    // Don't show score to user
    return view('test.result');
  }
}
