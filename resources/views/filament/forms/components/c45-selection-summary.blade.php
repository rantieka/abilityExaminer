@php
  $record = $getRecord();
  $testScore = $record->test_score;
  $c45Decision = $record->c45_decision;

  $aiThreshold = (float) \App\Models\Setting::get('c45_ai_threshold', 57.0);
  $testThreshold = (float) \App\Models\Setting::get('c45_test_threshold', 63.0);
  $confidenceThreshold = (float) \App\Models\Setting::get('c45_confidence_threshold', 80.0);
  
  if ($testScore !== null) {
    // 1. Ensure C4.5 decision is calculated and saved
    if (empty($c45Decision)) {
      try {
        $c45Decision = \App\Services\C45Predictor::predict(
          (float) $record->ai_score,
          (float) $testScore
        );
        $record->update(['c45_decision' => $c45Decision]);
      } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error("C4.5 prediction failed in selection summary: " . $e->getMessage());
      }
    }

    // 2. Ensure AI Explanation, Strengths, and Risks are generated
    $aiAnalysis = $record->ai_analysis ?? [];
    $explanation = $aiAnalysis['c45_explanation'] ?? null;
    $strengths = $aiAnalysis['c45_strengths'] ?? null;
    $keyRisks = $aiAnalysis['c45_key_risks'] ?? null;
    $explanationDecision = $aiAnalysis['c45_explanation_decision'] ?? null;
    $explanationLang = $aiAnalysis['c45_explanation_lang'] ?? null;

    if (empty($explanation) || empty($strengths) || empty($keyRisks) || $explanationDecision !== $c45Decision || $explanationLang !== 'en') {
      try {
        $groq = resolve(\App\Services\GroqService::class);
        $messages = [
          [
            'role' => 'system',
            'content' => 'You are an expert HR assistant. Analyze the candidate details and the C4.5 decision result. Respond with a JSON object containing three keys: "explanation" (a professional 2-3 sentence explanation in English explaining why the candidate was accepted or rejected based on the rules, referencing candidate names and scores naturally), "strengths" (an array of exactly 2 short bullet points highlighting candidate key advantages in English), and "key_risks" (an array of exactly 2 short bullet points highlighting potential risks or concerns in English).'
          ],
          [
            'role' => 'user',
            'content' => "Candidate Name: {$record->full_name}\n" .
                         "AI Score (CV Screening): {$record->ai_score}\n" .
                         "Test Score (Exam): {$testScore}\n" .
                         "C4.5 Decision Result: {$c45Decision}\n\n" .
                         "Classification Rules:\n" .
                         "1. If AI Score <= {$aiThreshold}, then REJECTED.\n" .
                         "2. If AI Score > {$aiThreshold} and Test Score <= {$testThreshold}, then REJECTED.\n" .
                         "3. If AI Score > {$aiThreshold} and Test Score > {$testThreshold}, then ACCEPTED.\n\n" .
                         "Output JSON format strictly matching:\n" .
                         "{\n" .
                         "  \"explanation\": \"your explanation here\",\n" .
                         "  \"strengths\": [\"strength 1\", \"strength 2\"],\n" .
                         "  \"key_risks\": [\"concern 1\", \"concern 2\"]\n" .
                         "}"
          ]
        ];

        $aiResult = $groq->chat($messages, 0.5);
        $explanation = $aiResult['explanation'] ?? null;
        $strengths = $aiResult['strengths'] ?? null;
        $keyRisks = $aiResult['key_risks'] ?? null;

        if ($explanation && is_array($strengths) && is_array($keyRisks)) {
          $aiAnalysis['c45_explanation'] = $explanation;
          $aiAnalysis['c45_strengths'] = $strengths;
          $aiAnalysis['c45_key_risks'] = $keyRisks;
          $aiAnalysis['c45_explanation_decision'] = $c45Decision;
          $aiAnalysis['c45_explanation_lang'] = 'en';
          $record->update(['ai_analysis' => $aiAnalysis]);
        }
      } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error("Groq AI failed to generate selection C4.5 explanation: " . $e->getMessage());
      }
    }

    // Fallbacks if Groq fails
    if (empty($explanation)) {
      if ($c45Decision === 'ACCEPTED') {
        $explanation = "Based on the C4.5 algorithm analysis, candidate {$record->full_name} is recommended for hire (ACCEPTED) because the AI Score (" . ($record->ai_score ?? 0) . "%) exceeds {$aiThreshold} and the Test Score (" . ($testScore ?? 0) . "/100) exceeds {$testThreshold}.";
        $strengths = ["The combination of CV screening score and online exam score meets the qualification threshold.", "Candidate's qualifications are well-suited for the applied experience tier."];
        $keyRisks = ["Requires direct portfolio verification.", "Work experience and background should be validated further."];
      } else {
        $explanation = "Based on the C4.5 algorithm analysis, candidate {$record->full_name} is not recommended (REJECTED) because the combined AI Score and Test Score do not meet the minimum standard qualification criteria.";
        $strengths = ["Possesses basic potential and interest in the role.", "Demonstrates willingness to undergo the evaluation process."];
        $keyRisks = ["Online exam score or CV screening score falls below the required threshold.", "Technical skill alignment requires further review."];
      }
    }
  }
@endphp

@if($testScore === null)
  <div style="background-color: #f9fafb; border-radius: 0.75rem; padding: 1.5rem; border: 1px solid #e5e7eb; text-align: center;">
    <p style="color: #6b7280; font-size: 0.875rem; font-style: italic; margin: 0;">Candidate has not completed the online exam yet.</p>
  </div>
@else
  <div style="background-color: white; border-radius: 0.75rem; padding: 1.5rem; color: #374151; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); border: 1px solid #e5e7eb; position: relative; overflow: hidden;">
    
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; position: relative; z-index: 10;">
      <div style="display: flex; align-items: center; gap: 0.875rem;">
        <div style="padding: 0.625rem; background-color: #dbeafe; border: 1px solid #bfdbfe; color: #2563eb; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
          <!-- Icon Chip/AI -->
          <svg style="width: 1.5rem; height: 1.5rem;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M9 16.25v1.5m-1.5-3h1.5m1.125-11.25h1.5m-1.5 1.5H21a.75.75 0 000-1.5H9.75a.75.75 0 000 1.5zM3 16.25h1.5m-1.5 3h1.5m1.125-11.25h1.5M3 9.75h1.5m1.125 3h1.5M3 13.25h1.5m11.25-11.25h1.5m-1.5 1.5H21a.75.75 0 000-1.5H16.5a.75.75 0 000 1.5zM21 9.75h-1.5m1.5 3h-1.5m1.5 3.5H21a.75.75 0 000-1.5h-1.5a.75.75 0 000 1.5z" />
          </svg>
        </div>
        <div>
          <h4 style="font-size: 1rem; font-weight: 700; color: #111827; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
            C4.5 Decision Prediction Engine
            <span style="font-size: 0.65rem; font-weight: 600; padding: 0.15rem 0.4rem; border-radius: 0.25rem; background-color: #eff6ff; color: #1d4ed8; border: 1px solid #dbeafe; text-transform: uppercase; letter-spacing: 0.05em;">ML Decision</span>
          </h4>
          <p style="font-size: 0.75rem; color: #6b7280; margin: 0.15rem 0 0 0;">Predictive classification calculated locally based on C4.5 model rules</p>
        </div>
      </div>
      
      <div>
        @if($c45Decision !== null)
          @php
            $c45Confidence = \App\Services\C45Predictor::getConfidence((float) $record->ai_score, (float) $testScore);
            $isLowConfidence = $c45Confidence < $confidenceThreshold;
          @endphp
          <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
            <span style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.45rem 1.15rem; border-radius: 9999px; font-size: 0.85rem; font-weight: 700; background-color: {{ $c45Decision === 'ACCEPTED' ? '#d1fae5' : '#fee2e2' }}; color: {{ $c45Decision === 'ACCEPTED' ? '#065f46' : '#991b1b' }}; border: 1px solid {{ $c45Decision === 'ACCEPTED' ? '#a7f3d0' : '#fecaca' }};">
              <span style="width: 0.45rem; height: 0.45rem; border-radius: 9999px; background-color: {{ $c45Decision === 'ACCEPTED' ? '#10b981' : '#ef4444' }};"></span>
              C4.5: {{ $c45Decision }} ({{ $c45Confidence }}% Conf.)
            </span>

            @if($isLowConfidence)
              <span style="display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.35rem 0.85rem; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 700; background-color: #fffbeb; color: #b45309; border: 1px solid #fde68a;">
                <svg style="width: 1rem; height: 1rem; color: #d97706;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                Manual Review Recommended
              </span>
            @else
              <span style="display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.35rem 0.85rem; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 700; background-color: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0;">
                <svg style="width: 1rem; height: 1rem; color: #16a34a;" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                High Confidence
              </span>
            @endif
          </div>
        @else
          <span style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.45rem 1.15rem; border-radius: 9999px; font-size: 0.85rem; font-weight: 700; background-color: #fee2e2; color: #991b1b; border: 1px dashed #fecaca;">
            <span style="width: 0.45rem; height: 0.45rem; border-radius: 9999px; background-color: #ef4444;"></span>
            No Decision
          </span>
        @endif
      </div>
    </div>
    
    <!-- Parameter Grid -->
    <div style="margin-top: 1.25rem; display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.875rem; border-top: 1px solid #f3f4f6; padding-top: 1.25rem; position: relative; z-index: 10;">
      <!-- Feature 1: AI Score -->
      <div style="background-color: #f9fafb; padding: 0.625rem 0.875rem; border-radius: 0.5rem; border: 1px solid #e5e7eb;">
        <span style="font-size: 0.65rem; text-transform: uppercase; color: #6b7280; font-weight: 600; display: block; letter-spacing: 0.05em;">AI CV Match Score</span>
        <span style="font-size: 1.1rem; font-weight: 700; color: #111827; margin-top: 0.15rem; display: block;">{{ $record->ai_score }}%</span>
      </div>
      <!-- Feature 2: Test Score -->
      <div style="background-color: #f9fafb; padding: 0.625rem 0.875rem; border-radius: 0.5rem; border: 1px solid #e5e7eb;">
        <span style="font-size: 0.65rem; text-transform: uppercase; color: #6b7280; font-weight: 600; display: block; letter-spacing: 0.05em;">Online Exam Score</span>
        <span style="font-size: 1.1rem; font-weight: 700; color: #111827; margin-top: 0.15rem; display: block;">{{ $testScore }} <span style="font-size: 0.75rem; color: #6b7280; font-weight: 400;">/100</span></span>
      </div>
    </div>

    <!-- Dynamic AI-Powered Candidate Assessment Summary -->
    <div style="margin-top: 1rem; border-top: 1px dashed #e5e7eb; padding-top: 1rem;">
      <div style="display: flex; align-items: center; gap: 0.45rem; font-weight: 700; color: #1e293b; margin-bottom: 0.5rem; font-size: 0.85rem;">
        <svg style="width: 1.15rem; height: 1.15rem; color: #8b5cf6;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 21l8.982-11.795H13.62l.817-5.096L5.456 16.205h6.177z" />
        </svg>
        AI Candidate Analysis & C4.5 Decision
      </div>
      
      <p style="font-size: 0.825rem; line-height: 1.5; color: #475569; margin: 0 0 0.875rem 0;">
        {{ $explanation }}
      </p>
      
      <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem;">
        <!-- Strengths -->
        <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 0.5rem; padding: 0.75rem 1rem;">
          <span style="font-size: 0.7rem; font-weight: 700; color: #166534; text-transform: uppercase; display: flex; align-items: center; gap: 0.3rem; margin-bottom: 0.35rem; letter-spacing: 0.025em;">
            <span style="width: 0.4rem; height: 0.4rem; border-radius: 9999px; background-color: #22c55e;"></span>
            Key Strengths
          </span>
          <ul style="margin: 0; padding-left: 1rem; font-size: 0.775rem; color: #374151; list-style-type: disc;">
            @foreach($strengths ?? [] as $strength)
              <li style="margin-bottom: 0.25rem; line-height: 1.4;">{{ $strength }}</li>
            @endforeach
          </ul>
        </div>
        
        <!-- Risks -->
        <div style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 0.5rem; padding: 0.75rem 1rem;">
          <span style="font-size: 0.7rem; font-weight: 700; color: #92400e; text-transform: uppercase; display: flex; align-items: center; gap: 0.3rem; margin-bottom: 0.35rem; letter-spacing: 0.025em;">
            <span style="width: 0.4rem; height: 0.4rem; border-radius: 9999px; background-color: #f59e0b;"></span>
            Potential Concerns
          </span>
          <ul style="margin: 0; padding-left: 1rem; font-size: 0.775rem; color: #374151; list-style-type: disc;">
            @foreach($keyRisks ?? [] as $risk)
              <li style="margin-bottom: 0.25rem; line-height: 1.4;">{{ $risk }}</li>
            @endforeach
          </ul>
        </div>
      </div>
    </div>
  </div>
@endif
