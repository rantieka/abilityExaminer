@php
  $record = $getRecord();
  $testScore = $record->test_score;

  if ($testScore === null) {
    $content = null;
  } else {
    $jobVacancy = $record->jobVacancy;
    
    if (!$jobVacancy) {
      $content = 'Lowongan Pekerjaan dihapus';
    } else {
      $questions = $jobVacancy->questions()->where('is_active', true)->get();
      $part1Questions = $questions->where('section', 'knowledge');
      $part2Questions = $questions->where('section', 'technical');
      
      // Answers - Merge both parts to ensure answers are found even if sections moved
      $allAnswers = ($record->part1_answers ?? []) + ($record->part2_answers ?? []);
      
      // Helper to calculate score for a set of questions
      $calculate = function($qs, $answers) {
        $total = $qs->count();
        $correct = 0;
        foreach($qs as $q) {
          if (($answers[$q->id] ?? null) === $q->correct_answer) {
            $correct++;
          }
        }
        return ['correct' => $correct, 'total' => $total, 'percentage' => $total > 0 ? round(($correct/$total)*100) : 0];
      };
      
      $details = $record->test_details ?? [];

      // Calculate stats using database if available (historically frozen), otherwise fall back to dynamic calculation
      if (isset($details['part1'])) {
        $p1Stats = [
          'correct' => $details['part1']['correct'],
          'total' => $details['part1']['total'],
          'percentage' => $details['part1']['total'] > 0 ? round(($details['part1']['correct'] / $details['part1']['total']) * 100) : 0
        ];
      } else {
        $p1Stats = $calculate($part1Questions, $allAnswers);
      }

      if (isset($details['part2'])) {
        $p2Stats = [
          'correct' => $details['part2']['correct'],
          'total' => $details['part2']['total'],
          'percentage' => $details['part2']['total'] > 0 ? round(($details['part2']['correct'] / $details['part2']['total']) * 100) : 0
        ];
      } else {
        $p2Stats = $calculate($part2Questions, $allAnswers);
      }

      // Load C4.5 decision from database (Database-first optimization)
      $c45Decision = $record->c45_decision;
      $c45Error = null;

      // Fallback: If c45_decision is null (e.g. old record),
      // calculate it via local PHP predictor and automatically save to database.
      if ($c45Decision === null) {
        try {
          $c45Decision = \App\Services\C45Predictor::predict(
            (float) $record->ai_score,
            (float) $testScore
          );
          // Permanently save to database
          $record->update(['c45_decision' => $c45Decision]);
        } catch (\Throwable $e) {
          \Illuminate\Support\Facades\Log::error("Failed to calculate C4.5 decision in Blade fallback: " . $e->getMessage());
        }
      }

      // C4.5 Decision Prediction Engine
      $aiSummary = null;
    }
  }
@endphp

<div class="space-y-4">
  @if($testScore === null)
    <p class="text-gray-500 italic">Ujian belum diselesaikan.</p>
  @elseif(isset($content) && $content === 'Lowongan Pekerjaan dihapus')
    <p class="text-red-500 italic">Lowongan Pekerjaan yang terkait dengan lamaran ini telah dihapus, rincian detail nilai tidak tersedia.</p>
  @else
    <div style="background-color: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; overflow: hidden; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);">
      <!-- Header Result -->
      <div style="padding: 1.5rem; background: linear-gradient(to right, #f9fafb, #ffffff); border-bottom: 1px solid #f3f4f6;">
        <div style="display: flex; align-items: center; gap: 1rem;">
          <div style="padding: 0.75rem; background-color: #dbeafe; color: #2563eb; border-radius: 9999px; width: 4rem; height: 4rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <!-- Heroicon: academic-cap -->
            <svg style="width: 2rem; height: 2rem;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
             <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.499 5.516 51.63 51.63 0 00-2.658.813m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />
            </svg>
          </div>
          <div>
            <h3 style="font-size: 1.5rem; line-height: 2rem; font-weight: 700; color: #111827; margin: 0;">Skor Ujian: <span style="color: #2563eb;">{{ $testScore }}</span><span style="font-size: 1rem; color: #9ca3af; font-weight: 400;">/100</span></h3>
            <p style="font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem;">Evaluasi berdasarkan total {{ $questions->count() }} pertanyaan</p>
          </div>
          <div style="margin-left: auto; text-align: right;">
           <span style="display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; background-color: {{ $testScore >= 70 ? '#d1fae5' : ($testScore >= 50 ? '#fef3c7' : '#fee2e2') }}; color: {{ $testScore >= 70 ? '#065f46' : ($testScore >= 50 ? '#92400e' : '#991b1b') }};">
              {{ $testScore >= 70 ? 'Lolos' : ($testScore >= 50 ? 'Perlu Peninjauan' : 'Di Bawah Batas') }}
           </span>
           @if(!empty($record->test_completed_at))
             @php
               $completedAt = $record->test_completed_at;
               // Use part1_started_at as start time. If not available, use created_at as fallback
               $startedAt = $record->part1_started_at ?? $record->created_at;
               
               // Calculate duration
               // detailed diff
               // true = absolute (no "before"/"after"), false = not short, 2 = parts
               $duration = $startedAt ? $startedAt->diffForHumans($completedAt, true, false, 2) : '-';
               
               // Translate duration strings if needed, e.g. "minutes" to "menit", "seconds" to "detik"
               $duration = str_replace(
                   ['years', 'year', 'months', 'month', 'weeks', 'week', 'days', 'day', 'hours', 'hour', 'minutes', 'minute', 'seconds', 'second'],
                   ['tahun', 'tahun', 'bulan', 'bulan', 'minggu', 'minggu', 'hari', 'hari', 'jam', 'jam', 'menit', 'menit', 'detik', 'detik'],
                   $duration
               );
             @endphp
             <div style="margin-top: 0.5rem; font-size: 0.75rem; color: #6b7280; text-align: right;">
               <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.25rem;">
                 <svg style="width: 0.875rem; height: 0.875rem;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                 <span>{{ $duration }}</span>
               </div>
               <div style="color: #9ca3af; font-size: 0.7rem;">{{ $completedAt->locale('id')->translatedFormat('d M Y, H:i') }}</div>
             </div>
           @else
             <div style="margin-top: 0.5rem; font-size: 0.75rem; color: #9ca3af; text-align: right;">
               <div>Waktu tidak tercatat</div>
             </div>
           @endif
          </div>
        </div>
      </div>
      
      <!-- Breakdown Grid -->
      <div style="display: grid; grid-row-gap: 1px; background-color: #f3f4f6;">
        <!-- Part 1 & 2 Summary -->
        <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1px;">
            <div style="background-color: white; padding: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                    <div>
                    <h4 style="font-size: 0.875rem; font-weight: 600; color: #374151; text-transform: uppercase; letter-spacing: 0.05em;">Bagian 1: Pengetahuan</h4>
                    <p style="font-size: 0.75rem; color: #9ca3af;">Teori & Konsep</p>
                    </div>
                    <div style="padding: 0.25rem; background-color: #f3f4f6; border-radius: 0.375rem;">
                    <svg style="width: 1.25rem; height: 1.25rem; color: #6b7280;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
                    </div>
                </div>
                <div style="margin-top: 1rem;">
                    <div style="display: flex; align-items: baseline; gap: 0.5rem;">
                    <span style="font-size: 1.875rem; font-weight: 700; color: #111827;">{{ $p1Stats['correct'] }}</span>
                    <span style="font-size: 0.875rem; color: #6b7280;">/ {{ $p1Stats['total'] }} Benar</span>
                    </div>
                    <div style="width: 100%; height: 0.5rem; background-color: #f3f4f6; border-radius: 9999px; margin-top: 0.5rem; overflow: hidden;">
                    <div style="height: 100%; background-color: #3b82f6; width: {{ $p1Stats['percentage'] }}%;"></div>
                    </div>
                </div>
            </div>

            <div style="background-color: white; padding: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                    <div>
                    <h4 style="font-size: 0.875rem; font-weight: 600; color: #374151; text-transform: uppercase; letter-spacing: 0.05em;">Bagian 2: Teknis</h4>
                    <p style="font-size: 0.75rem; color: #9ca3af;">Studi Kasus & Logika</p>
                    </div>
                    <div style="padding: 0.25rem; background-color: #f3f4f6; border-radius: 0.375rem;">
                    <svg style="width: 1.25rem; height: 1.25rem; color: #6b7280;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" /></svg>
                    </div>
                </div>
                <div style="margin-top: 1rem;">
                    <div style="display: flex; align-items: baseline; gap: 0.5rem;">
                    <span style="font-size: 1.875rem; font-weight: 700; color: #111827;">{{ $p2Stats['correct'] }}</span>
                    <span style="font-size: 0.875rem; color: #6b7280;">/ {{ $p2Stats['total'] }} Benar</span>
                    </div>
                    <div style="width: 100%; height: 0.5rem; background-color: #f3f4f6; border-radius: 9999px; margin-top: 0.5rem; overflow: hidden;">
                    <div style="height: 100%; background-color: #3b82f6; width: {{ $p2Stats['percentage'] }}%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Skill Category Breakdown (New Section) -->
        <div style="background-color: #f9fafb; padding: 1rem 1.5rem; border-top: 1px solid #f3f4f6; border-bottom: 1px solid #f3f4f6;">
            <h4 style="font-size: 0.75rem; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.1em;">Performa Kategori Keahlian (Berbobot)</h4>
        </div>

        <div style="background-color: white; padding: 1.5rem;">
            @php
                $details = $record->test_details ?? [
                    'required' => ['percentage' => 0, 'earned' => 0, 'possible' => 0],
                    'preferred' => ['percentage' => 0, 'earned' => 0, 'possible' => 0],
                    'bonus' => ['percentage' => 0, 'earned' => 0, 'possible' => 0]
                ];
            @endphp
            <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1.5rem;">
                <!-- Required -->
                <div>
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                        <div style="width: 0.5rem; height: 0.5rem; border-radius: 9999px; background-color: #ef4444;"></div>
                        <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">Keahlian Wajib</span>
                    </div>
                    <div style="display: flex; align-items: baseline; gap: 0.25rem;">
                        <span style="font-size: 1.25rem; font-weight: 700; color: #111827;">{{ $details['required']['percentage'] }}%</span>
                        <span style="font-size: 0.75rem; color: #9ca3af;">({{ $details['required']['earned'] }}/{{ $details['required']['possible'] }} poin)</span>
                    </div>
                    <div style="width: 100%; height: 0.375rem; background-color: #f3f4f6; border-radius: 9999px; margin-top: 0.5rem; overflow: hidden;">
                        <div style="height: 100%; background-color: #ef4444; width: {{ $details['required']['percentage'] }}%;"></div>
                    </div>
                </div>

                <!-- Preferred -->
                <div>
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                        <div style="width: 0.5rem; height: 0.5rem; border-radius: 9999px; background-color: #f59e0b;"></div>
                        <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">Keahlian yang Diutamakan</span>
                    </div>
                    <div style="display: flex; align-items: baseline; gap: 0.25rem;">
                        <span style="font-size: 1.25rem; font-weight: 700; color: #111827;">{{ $details['preferred']['percentage'] }}%</span>
                        <span style="font-size: 0.75rem; color: #9ca3af;">({{ $details['preferred']['earned'] }}/{{ $details['preferred']['possible'] }} poin)</span>
                    </div>
                    <div style="width: 100%; height: 0.375rem; background-color: #f3f4f6; border-radius: 9999px; margin-top: 0.5rem; overflow: hidden;">
                        <div style="height: 100%; background-color: #f59e0b; width: {{ $details['preferred']['percentage'] }}%;"></div>
                    </div>
                </div>

                <!-- Bonus -->
                <div>
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                        <div style="width: 0.5rem; height: 0.5rem; border-radius: 9999px; background-color: #10b981;"></div>
                        <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">Keahlian Tambahan</span>
                    </div>
                    <div style="display: flex; align-items: baseline; gap: 0.25rem;">
                        <span style="font-size: 1.25rem; font-weight: 700; color: #111827;">{{ $details['bonus']['percentage'] }}%</span>
                        <span style="font-size: 0.75rem; color: #9ca3af;">({{ $details['bonus']['earned'] }}/{{ $details['bonus']['possible'] }} poin)</span>
                    </div>
                    <div style="width: 100%; height: 0.375rem; background-color: #f3f4f6; border-radius: 9999px; margin-top: 0.5rem; overflow: hidden;">
                        <div style="height: 100%; background-color: #10b981; width: {{ $details['bonus']['percentage'] }}%;"></div>
                    </div>
                </div>
            </div>
        </div>
      </div>
    </div>


    <!-- Detailed Breakdown (Collapsible) -->
    <div x-data="{ expanded: false }" style="margin-top: 1.5rem; border: 1px solid #e5e7eb; border-radius: 0.75rem; background-color: white; overflow: hidden;">
      <button @click="expanded = !expanded" style="width: 100%; display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; background-color: #f9fafb; border: none; cursor: pointer; text-align: left; transition: background-color 0.2s;">
        <span style="font-weight: 600; color: #374151; font-size: 0.875rem;">Lihat Rincian Detail Pertanyaan</span>
        <span x-show="!expanded" style="color: #9ca3af;">
          <svg style="width: 1.25rem; height: 1.25rem;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
        </span>
        <span x-show="expanded" style="color: #9ca3af; display: none;">
          <svg style="width: 1.25rem; height: 1.25rem;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
        </span>
      </button>
      
      <div x-show="expanded" x-collapse style="border-top: 1px solid #e5e7eb;">
        <div style="padding: 1.5rem;">
          @if($part1Questions->isEmpty())
            <p style="text-align: center; color: #6b7280; font-style: italic;">Tidak ada pertanyaan yang ditemukan untuk Bagian 1.</p>
          @else
            <div style="display: flex; flex-direction: column; gap: 1rem;">
              @foreach($part1Questions as $index => $question)
                @php
                  $rawUserAnswerKey = $allAnswers[$question->id] ?? null;
                  $rawCorrectKey = $question->correct_answer;
                  
                  // Map to letters if numeric
                  $userAnswerKey = is_numeric($rawUserAnswerKey) ? chr(65 + (int)$rawUserAnswerKey) : ($rawUserAnswerKey ? strtoupper($rawUserAnswerKey) : null);
                  $correctKey = is_numeric($rawCorrectKey) ? chr(65 + (int)$rawCorrectKey) : strtoupper($rawCorrectKey);
                  
                  $isCorrect = $userAnswerKey !== null && $userAnswerKey === $correctKey;
                  
                  // Normalize options to letter keys for consistent lookup
                  $normalizedOptions = [];
                  foreach ($question->options as $k => $v) {
                    $lk = is_numeric($k) ? chr(65 + (int)$k) : strtoupper($k);
                    $normalizedOptions[$lk] = $v;
                  }
                  
                  $userAnswerText = $userAnswerKey ? ($normalizedOptions[$userAnswerKey] ?? 'Tidak Ada Jawaban') : 'Tidak Ada Jawaban';
                  $correctAnswerText = $normalizedOptions[$correctKey] ?? 'Tidak Diketahui';
                @endphp
                <div style="border: 1px solid {{ $isCorrect ? '#bbf7d0' : '#fecaca' }}; border-radius: 0.5rem; padding: 1rem; background-color: {{ $isCorrect ? '#f0fdf4' : '#fef2f2' }};">
                  <div style="display: flex; gap: 1rem; align-items: flex-start;">
                    <div style="flex-shrink: 0; width: 1.75rem; height: 1.75rem; background-color: {{ $isCorrect ? '#22c55e' : '#ef4444' }}; color: white; border-radius: 9999px; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.875rem;">
                       {{ $loop->iteration }}
                    </div>
                    <div style="flex-grow: 1;">
                      <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <span style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; padding: 0.1rem 0.5rem; border-radius: 0.25rem; background-color: {{ $question->skill_category === 'required' ? '#fee2e2' : ($question->skill_category === 'preferred' ? '#fef3c7' : '#d1fae5') }}; color: {{ $question->skill_category === 'required' ? '#b91c1c' : ($question->skill_category === 'preferred' ? '#b45309' : '#047857') }};">
                            {{ $question->skill_category === 'required' ? 'wajib' : ($question->skill_category === 'preferred' ? 'diutamakan' : 'tambahan') }}
                        </span>
                        <span style="font-size: 0.7rem; font-weight: 600; color: #9ca3af;">Kesulitan: {{ match($question->difficulty) { 'easy' => 'Mudah', 'medium' => 'Sedang', 'hard' => 'Sulit', default => ucfirst($question->difficulty) } }}</span>
                      </div>
                      <p style="font-weight: 500; color: #1f2937; margin-bottom: 0.5rem;">
                        {!! preg_replace('/```(?:php)?(.*?)```/s', '<pre style="background-color: #1f2937; color: #f9fafb; padding: 0.75rem; border-radius: 0.375rem; margin-top: 0.5rem; overflow-x: auto; white-space: pre-wrap; word-wrap: break-word; font-family: monospace; font-size: 0.875rem;"><code>$1</code></pre>', e($question->question_text)) !!}
                      </p>
                      
                      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 0.75rem; font-size: 0.875rem;">
                        <!-- User Answer -->
                        <div>
                          <span style="display: block; font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem;">Jawaban Kandidat</span>
                          <div style="display: flex; align-items: center; gap: 0.5rem;">
                            @if($userAnswerKey)
                              <span style="font-weight: 600; color: {{ $isCorrect ? '#166534' : '#991b1b' }};">{{ $userAnswerKey }}.</span>
                              <span style="color: #374151;">{{ $userAnswerText }}</span>
                            @else
                              <span style="color: #9ca3af; font-style: italic;">Dilewati / Tidak Ada Jawaban</span>
                            @endif
                          </div>
                        </div>
                        
                        <!-- Correct Answer -->
                        @if(!$isCorrect)
                        <div style="border-left: 2px solid #e5e7eb; padding-left: 1rem;">
                          <span style="display: block; font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem;">Jawaban Benar</span>
                          <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-weight: 600; color: #166534;">{{ $correctKey }}.</span>
                            <span style="color: #374151;">{{ $correctAnswerText }}</span>
                          </div>
                        </div>
                        @endif
                      </div>
                    </div>
                    <div style="flex-shrink: 0;">
                      @if($isCorrect)
                        <svg style="width: 1.5rem; height: 1.5rem; color: #16a34a;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                      @else
                        <svg style="width: 1.5rem; height: 1.5rem; color: #dc2626;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                      @endif
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          @endif

          @if(!$part2Questions->isEmpty())
            <div style="margin-top: 2rem; border-top: 1px dashed #e5e7eb; padding-top: 1.5rem;">
              <h4 style="font-size: 1rem; font-weight: 700; color: #1f2937; margin-bottom: 1rem;">Bagian 2: Teknis/Studi Kasus</h4>
              <div style="display: flex; flex-direction: column; gap: 1rem;">
                @foreach($part2Questions as $index => $question)
                  @php
                    $rawUserAnswerKey = $allAnswers[$question->id] ?? null;
                    $rawCorrectKey = $question->correct_answer;
                    
                    // Map to letters if numeric
                    $userAnswerKey = is_numeric($rawUserAnswerKey) ? chr(65 + (int)$rawUserAnswerKey) : ($rawUserAnswerKey ? strtoupper($rawUserAnswerKey) : null);
                    $correctKey = is_numeric($rawCorrectKey) ? chr(65 + (int)$rawCorrectKey) : strtoupper($rawCorrectKey);
                    
                    $isCorrect = $userAnswerKey !== null && $userAnswerKey === $correctKey;
                    
                    // Normalize options to letter keys for consistent lookup
                    $normalizedOptions = [];
                    foreach ($question->options as $k => $v) {
                      $lk = is_numeric($k) ? chr(65 + (int)$k) : strtoupper($k);
                      $normalizedOptions[$lk] = $v;
                    }
                    
                    $userAnswerText = $userAnswerKey ? ($normalizedOptions[$userAnswerKey] ?? 'Tidak Ada Jawaban') : 'Tidak Ada Jawaban';
                    $correctAnswerText = $normalizedOptions[$correctKey] ?? 'Tidak Diketahui';
                  @endphp
                  <div style="border: 1px solid {{ $isCorrect ? '#bbf7d0' : '#fecaca' }}; border-radius: 0.5rem; padding: 1rem; background-color: {{ $isCorrect ? '#f0fdf4' : '#fef2f2' }};">
                    <div style="display: flex; gap: 1rem; align-items: flex-start;">
                      <div style="flex-shrink: 0; width: 1.75rem; height: 1.75rem; background-color: {{ $isCorrect ? '#22c55e' : '#ef4444' }}; color: white; border-radius: 9999px; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.875rem;">
                         {{ $loop->iteration }}
                      </div>
                      <div style="flex-grow: 1;">
                        <p style="font-weight: 500; color: #1f2937; margin-bottom: 0.5rem;">
                          {!! preg_replace('/```(?:php)?(.*?)```/s', '<pre style="background-color: #1f2937; color: #f9fafb; padding: 0.75rem; border-radius: 0.375rem; margin-top: 0.5rem; overflow-x: auto; white-space: pre-wrap; word-wrap: break-word; font-family: monospace; font-size: 0.875rem;"><code>$1</code></pre>', e($question->question_text)) !!}
                        </p>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 0.75rem; font-size: 0.875rem;">
                          <!-- User Answer -->
                          <div>
                            <span style="display: block; font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem;">Jawaban Kandidat</span>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                              @if($userAnswerKey)
                                <span style="font-weight: 600; color: {{ $isCorrect ? '#166534' : '#991b1b' }};">{{ $userAnswerKey }}.</span>
                                <span style="color: #374151;">{{ $userAnswerText }}</span>
                              @else
                                <span style="color: #9ca3af; font-style: italic;">Dilewati / Tidak Ada Jawaban</span>
                              @endif
                            </div>
                          </div>
                          
                          <!-- Correct Answer -->
                          @if(!$isCorrect)
                          <div style="border-left: 2px solid #e5e7eb; padding-left: 1rem;">
                            <span style="display: block; font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem;">Jawaban Benar</span>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                              <span style="font-weight: 600; color: #166534;">{{ $correctKey }}.</span>
                              <span style="color: #374151;">{{ $correctAnswerText }}</span>
                            </div>
                          </div>
                          @endif
                        </div>
                      </div>
                      <div style="flex-shrink: 0;">
                        @if($isCorrect)
                          <svg style="width: 1.5rem; height: 1.5rem; color: #16a34a;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        @else
                          <svg style="width: 1.5rem; height: 1.5rem; color: #dc2626;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        @endif
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>
            </div>
          @endif
        </div>
      </div>
    </div>
  @endif
</div>
