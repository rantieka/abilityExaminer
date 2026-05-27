<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class C45Predictor
{
  /**
   * Predict applicant suitability based on Weka J48 (C4.5) decision tree rules.
   * 
   * Evaluation Results (10-Fold Cross-Validation):
   * - Accuracy: 86%
   * - Kappa Statistic: 0.7009 (Substantial Agreement)
   * - Recall (ACCEPTED): 75.0%
   * - Recall (REJECTED): 93.3%
   *
   * @param float $aiScore
   * @param float $testScore
   * @return string 'ACCEPTED'|'REJECTED'
   */
  public static function predict(float $aiScore, float $testScore): string {
    // Fetch threshold parameters dynamically from settings (default values are J48 Weka model parameters)
    $aiThreshold = (float) \App\Models\Setting::get('c45_ai_threshold', 57.0);
    $testThreshold = (float) \App\Models\Setting::get('c45_test_threshold', 63.0);

    // Rule 1: AI Score (CV) <= AI Threshold -> REJECTED
    if ($aiScore <= $aiThreshold) {
      return 'REJECTED';
    }

    // Rule 2: AI Score (CV) > AI Threshold and Test Score (Exam) <= Test Threshold -> REJECTED
    if ($testScore <= $testThreshold) {
      return 'REJECTED';
    }

    // Rule 3: AI Score (CV) > AI Threshold and Test Score (Exam) > Test Threshold -> ACCEPTED
    return 'ACCEPTED';
  }

  /**
   * Resolve the model confidence (probability) based on the J48 tree leaf classification.
   *
   * @param float $aiScore
   * @param float $testScore
   * @return float confidence percentage
   */
  public static function getConfidence(float $aiScore, float $testScore): float{
    // Fetch settings parameters dynamically
    $aiThreshold = (float) \App\Models\Setting::get('c45_ai_threshold', 57.0);
    $testThreshold = (float) \App\Models\Setting::get('c45_test_threshold', 63.0);

    Log::info("Debugging C4.5:", [
      'candidate_scores' => [
        'aiScore' => $aiScore,
        'testScore' => $testScore,
      ],
      'active_thresholds' => [
        'aiThreshold' => $aiThreshold,
        'testThreshold' => $testThreshold,
      ]
    ]);
    
    $leaf1Confidence = (float) \App\Models\Setting::get('c45_leaf1_confidence', 88.2);
    $leaf2Confidence = (float) \App\Models\Setting::get('c45_leaf2_confidence', 79.4);
    $leaf3Confidence = (float) \App\Models\Setting::get('c45_leaf3_confidence', 90.6);

    // Leaf 1: AI Score (CV) <= Threshold -> c45_leaf1_confidence
    if ($aiScore <= $aiThreshold) {
      return $leaf1Confidence;
    }

    // Leaf 2: AI Score (CV) > Threshold and Test Score (Exam) <= Threshold -> c45_leaf2_confidence
    if ($testScore <= $testThreshold) {
      return $leaf2Confidence;
    }

    // Leaf 3: AI Score (CV) > Threshold and Test Score (Exam) > Threshold -> c45_leaf3_confidence
    return $leaf3Confidence;
  }
}
