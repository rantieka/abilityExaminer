<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaService
{
  protected string $baseUrl;
  protected string $model;

  public function __construct()
  {
    $this->baseUrl = config('services.ollama.base_url', 'http://localhost:11434');
    $this->model = config('services.ollama.model', 'llama3.2:3b');
  }

  /**
  * Send a prompt to Ollama and get the response.
  * Compatible with GroqService interface.
  */
  public function chat(array $messages, float $temperature = 0.1)
  {
    // Convert messages to Ollama format (single prompt)
    $prompt = $this->convertMessagesToPrompt($messages);

    Log::info("Ollama Request - Model: {$this->model}, Prompt length: " . strlen($prompt));

    try {
      $response = Http::timeout(120) // Ollama might be slower
        ->post("{$this->baseUrl}/api/generate", [
          'model' => $this->model,
          'prompt' => $prompt,
          'stream' => false,
          'format' => 'json', // Request JSON output
          'options' => [
            'temperature' => $temperature,
            'num_predict' => 2048,
          ],
        ]);

      if ($response->failed()) {
        throw new \Exception('Ollama API Error: ' . $response->status() . ' - ' . $response->body());
      }

      $data = $response->json();

      if (!isset($data['response'])) {
        throw new \Exception('Invalid Ollama Response Structure: ' . json_encode($data));
      }

      $content = $data['response'];

      // DEBUG: Log raw content
      Log::info("Ollama Content Raw: " . $content);

      // Robust Parsing: Extract JSON object from text
      if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
        $content = $matches[0];
      }

      $decoded = json_decode($content, true);

      if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \Exception("JSON Decode Error: " . json_last_error_msg() . " | Content: " . $content);
      }

      return $decoded;

    } catch (\Exception $e) {
      Log::error("Ollama Error: " . $e->getMessage());
      throw $e;
    }
  }

  /**
  * Convert OpenAI-style messages to single prompt for Ollama
  */
  protected function convertMessagesToPrompt(array $messages): string
  {
    $prompt = '';

    foreach ($messages as $message) {
      $role = $message['role'] ?? 'user';
      $content = $message['content'] ?? '';

      if ($role === 'system') {
        $prompt .= "System: {$content}\n\n";
      } elseif ($role === 'user') {
        $prompt .= "User: {$content}\n\n";
      } elseif ($role === 'assistant') {
        $prompt .= "Assistant: {$content}\n\n";
      }
    }

    $prompt .= "Assistant: ";

    return $prompt;
  }
}
