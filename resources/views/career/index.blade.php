@extends('layouts.landing')

@section('content')
<!-- Hero Section -->
<div class="pt-5">
  <div class="careers-jumbotron p-5 mb-4 rounded-3 position-relative overflow-hidden">
    <!-- Geometric Pattern Background -->
    <div class="geometric-pattern">
      <!-- Animated Circles -->
      <div class="geo-circle geo-circle-1"></div>
      <div class="geo-circle geo-circle-2"></div>
      <div class="geo-circle geo-circle-3"></div>

      <!-- Scattered Dots -->
      <div class="geo-dots"></div>

      <!-- Gradient Lines -->
      <div class="geo-line geo-line-1"></div>
      <div class="geo-line geo-line-2"></div>
    </div>

    <!-- Content -->
    <div class="container py-5 text-center position-relative d-flex flex-column justify-content-center"
      style="min-height: 300px; z-index: 10;">
      <h1 class="display-5 fw-bold mb-3 text-main">Careers</h1>
      <p class="mb-0"><a href="{{ route('home') }}" class="text-main text-decoration-none">Home</a>
        <span class="text-subtle">/ Careers </span>
      </p>
    </div>
  </div>
</div>

<div class="container py-5">
  @if($jobsByDepartment->first()->isEmpty())
  <div class="text-center">
    <i class="fa-solid fa-person-walking-luggage display-5 mb-3"></i>
    <p>No job vacancies available at the moment.</p>
  </div>
  @else
  @foreach($jobsByDepartment as $department => $jobs)
  <div class="mb-5">
    <h3 class="text-main fw-bold mb-4 text-center">
      <i class="fas fa-diagram-3"></i> {{ $department }}
    </h3>
    <div class="job-grid">
      @foreach($jobs as $job)
      <div class="job-card">
        <h3 class="job-title">{{ $job['title'] }}</h3>
        <span class="badge rounded-pill text-bg-warning text-white align-self-start mb-2">{{ $job['employment_type']
          }}</span>
        <p class="job-desc">
          {{ \Illuminate\Support\Str::limit($job['description'], 120) }}
        </p>
        <div class="card-footer-custom">
          <a href="{{ route('career.show', $job['slug']) }}" class="btn-apply">
            View Details
          </a>
        </div>
      </div>
      @endforeach
    </div>
  </div>
  @endforeach
  @endif
</div>


@endsection