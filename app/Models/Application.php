<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Application extends Model
{
  use HasFactory;
  protected $fillable = [
    'job_vacancy_id',
    'user_id',
    'full_name',
    'email',
    'phone',
    'cv_path',
    'status',
    'test_token',
    'token_expires_at',
    'ai_score',
    'ai_analysis',
    'test_score',
    'part1_answers',
    'part2_answers', // Added
    'part1_completed_at',
    'test_completed_at',
    'part1_started_at',
    'part2_started_at',
    'email_sent_at',
    'email_type',
    'rejection_reason',
    'test_details',
  ];

  protected $casts = [
    'ai_analysis' => 'array',
    'part1_answers' => 'array',
    'part2_answers' => 'array',
    'part1_started_at' => 'datetime',
    'part2_started_at' => 'datetime',
    'test_completed_at' => 'datetime',
    'token_expires_at' => 'datetime',
    'test_details' => 'array',
  ];

  public function jobVacancy(): BelongsTo
  {
    return $this->belongsTo(JobVacancy::class);
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }
}
