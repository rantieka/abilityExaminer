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
    'rejection_reason',
    'rejected_by',
    'rejected_at',
    'required_count',
    'published_until',
    'archived_at',
    'is_fulltime',
    'is_wfo',
  ];

  protected $casts = [
    'is_published' => 'boolean',
    'is_fulltime' => 'boolean',
    'is_wfo' => 'boolean',
    'rejected_at' => 'datetime',
    'published_until' => 'date',
    'archived_at' => 'datetime',
  ];

  public function createdBy()
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function rejectedBy()
  {
    return $this->belongsTo(User::class, 'rejected_by');
  }

  public function questions()
  {
    return $this->hasMany(Question::class);
  }
}
