<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
  protected string $apiKey;
  protected string $baseUrl;
  protected string $model;

  public function __construct()
  {
    $this->apiKey = config('services.gemini.api_key');
    $this->baseUrl = config('services.gemini.base_url');
    $this->model = config('services.gemini.model');
  }

  /**
   * Send a prompt to Gemini and get the response.
   */
  public function chat(array $messages, float $temperature = 0.5, int $maxTokens = 4096, ?string $model = null)
  {
      $selectedModel = $model ?? $this->model;
      
      // Convert OpenAI-style messages to Gemini-style contents
      // Note: Gemini expects 'user' or 'model' roles. 'system' is handled differently in v1beta.
      // For simplicity, we merge system message into the first user message or use systemInstruction if supported.
      
      $systemInstruction = null;
      $contents = [];

      foreach ($messages as $msg) {
          if ($msg['role'] === 'system') {
              $systemInstruction = ['parts' => [['text' => $msg['content']]]];
          } else {
              $role = ($msg['role'] === 'assistant') ? 'model' : 'user';
              $contents[] = [
                  'role' => $role,
                  'parts' => [['text' => $msg['content']]]
              ];
          }
      }

      $payload = [
          'contents' => $contents,
          'generation_config' => [
              'temperature' => $temperature,
              'max_output_tokens' => $maxTokens,
          ]
      ];

      if ($systemInstruction) {
          $payload['system_instruction'] = $systemInstruction;
      }

      $url = "{$this->baseUrl}/models/{$selectedModel}:generateContent?key={$this->apiKey}";

      $response = Http::withoutVerifying()
        ->timeout(120) // Gemini can be slow for large prompts
        ->post($url, $payload);

      if ($response->failed()) {
        throw new \Exception('Gemini API Error: ' . $response->status() . ' - ' . $response->body());
      }

      $data = $response->json();
      
      if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        throw new \Exception('Invalid Gemini Response Structure: ' . json_encode($data));
      }

      $content = $data['candidates'][0]['content']['parts'][0]['text'];
      
      Log::info("Gemini Content Raw: " . $content);

      // Robust Parsing
      if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
          $content = $matches[0];
      }

      $decoded = json_decode($content, true);

      if (json_last_error() !== JSON_ERROR_NONE) {
          throw new \Exception("JSON Decode Error: " . json_last_error_msg() . " | Content: " . $content);
      }

      return $decoded;
  }
}
