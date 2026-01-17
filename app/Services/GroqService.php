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
  public function chat(array $messages, float $temperature = 0.5)
  {
    try {
      // Bypass SSL verification for local development (Windows/Laragon SSL cert issue)
      // TODO: Remove this in production - SSL should be verified on live servers
      $response = Http::withoutVerifying()
        ->timeout(30)
        ->withHeaders([
          'Authorization' => "Bearer {$this->apiKey}",
          'Content-Type' => 'application/json',
        ])
        ->post("{$this->baseUrl}/chat/completions", [
          'model' => $this->model,
          'messages' => $messages,
          'temperature' => $temperature,
          'max_tokens' => 2048,
        ]);

      if ($response->failed()) {
        Log::error('Groq API Error', [
          'status' => $response->status(),
          'body' => $response->body(),
        ]);
        return null;
      }

      $data = $response->json();
      
      if (!isset($data['choices'][0]['message']['content'])) {
        Log::error('Invalid Groq Response Structure', ['data' => $data]);
        return null;
      }

      return $data['choices'][0]['message']['content'];

    } catch (\Exception $e) {
      Log::error('Groq Service Exception', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);
      return null;
    }
  }
}
