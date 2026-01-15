<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobVacancy extends Model
{
  use HasFactory;

  protected $fillable = [
    'title',
    'slug',
    'created_by',
    'description',
    'qualifications',
    'status',
    'is_published',
  ];

  protected $casts = [
    'is_published' => 'boolean',
  ];

  public function createdBy()
  {
    return $this->belongsTo(User::class, 'created_by');
  }
}
