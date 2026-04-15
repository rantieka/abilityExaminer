<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
  protected $fillable = [
    'job_vacancy_id',
    'question_text',
    'options',
    'correct_answer',
    'is_active',
    'section',
    'difficulty',
  ];

  protected $casts = [
    'options' => 'array',
  ];

  public function jobVacancy()
  {
    return $this->belongsTo(JobVacancy::class);
  }
}
