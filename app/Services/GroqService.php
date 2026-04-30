<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqService
{
  protected string $apiKey;
  protected string $baseUrl;
  protected string $model;

  public function __construct()
  {
    $this->apiKey = config('services.groq.api_key');
    $this->baseUrl = config('services.groq.base_url');
    $this->model = config('services.groq.model');
  }

  /**
   * Send a prompt to Groq and get the response.
   */
  public function chat(array $messages, float $temperature = 0.5, int $maxTokens = 4096, ?string $model = null)
  {
      // Use override model if provided, otherwise fall back to config default
      $selectedModel = $model ?? $this->model;

      // Bypass SSL verification for local development (Windows/Laragon SSL cert issue)
      // TODO: Remove this in production - SSL should be verified on live servers
      $response = Http::withoutVerifying()
        ->timeout(60)
        ->withHeaders([
          'Authorization' => "Bearer {$this->apiKey}",
          'Content-Type' => 'application/json',
        ])
        ->post("{$this->baseUrl}/chat/completions", [
          'model' => $selectedModel,
          'messages' => $messages,
          'temperature' => $temperature,
          'max_tokens' => $maxTokens,
          'response_format' => ['type' => 'json_object'], // Enforce JSON mode
        ]);

      if ($response->failed()) {
        throw new \Exception('Groq API Error: ' . $response->status() . ' - ' . $response->body());
      }

      $data = $response->json();
      
      if (!isset($data['choices'][0]['message']['content'])) {
        throw new \Exception('Invalid Groq Response Structure: ' . json_encode($data));
      }

      // Decode JSON string to array
      $content = $data['choices'][0]['message']['content'];
      
      // DEBUG: Log raw content to see if it's valid JSON
      Log::info("Groq Content Raw: " . $content);

      // Robust Parsing: Extract JSON object from text if preamble exists
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
