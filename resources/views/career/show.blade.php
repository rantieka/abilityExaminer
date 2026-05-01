@extends('layouts.landing')
@section('content')

<!-- Hero Section -->
<div class="pt-5">
  <div class="careers-jumbotron p-5 mb-4 rounded-3 position-relative overflow-hidden">
    <!-- Canvas Background -->
    <canvas id="bg-canvas" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 0;"></canvas>

    <!-- Content -->
    <div class="container py-5 text-center position-relative d-flex flex-column justify-content-center"
      style="min-height: 300px; z-index: 10;">
      <h1 class="display-5 fw-bold mb-3 text-main">{{ $job->title }}</h1>
      <p class="mb-0"><a href="{{ route('home') }}" class="text-main text-decoration-none">Home</a>
        <a href="{{ route('career.index') }}" class="text-main text-decoration-none">/ Careers </a>
        <span class="text-subtle">/ {{ $job->title }} </span>
      </p>
    </div>
  </div>
</div>
<div class="container py-5">
  <div class="row">
    <div class="col-md-8">
      <div class="mb-4">
        <h5 class="fw-bold mb-3">Description</h5>
        <p>{!! $job->description !!}</p>
      </div>
      <div class="mb-4">
        <h5 class="fw-bold mb-3">We're looking for</h5>
        <div class="mb-1">{!! $job->qualifications !!}</div>
      </div>

      @if($job->required_skills || $job->preferred_skills || $job->bonus_skills)
      <div class="mb-4">
        <h5 class="fw-bold mb-3">Technical Skills</h5>
        
        @if($job->required_skills)
          <div class="mb-3">
             <div class="skill-label">Main Stack (Required)</div>
             @foreach($job->required_skills as $skill)
               <span class="badge bg-light text-dark border me-1 mb-1 px-3 py-2 fw-medium rounded-pill">{{ $skill }}</span>
             @endforeach
          </div>
        @endif

        @if($job->preferred_skills)
          <div class="mb-3">
             <div class="skill-label">Nice to Have (Preferred)</div>
             @foreach($job->preferred_skills as $skill)
               <span class="badge bg-light text-dark border me-1 mb-1 px-3 py-2 fw-medium rounded-pill">{{ $skill }}</span>
             @endforeach
          </div>
        @endif

        @if($job->bonus_skills)
          <div class="mb-3">
             <div class="skill-label">Bonus / Tools</div>
             @foreach($job->bonus_skills as $skill)
               <span class="badge bg-light text-dark border me-1 mb-1 px-3 py-2 fw-medium rounded-pill">{{ $skill }}</span>
             @endforeach
          </div>
        @endif
      </div>
      @endif
    </div>
    <div class="col-md-4">
      <div class="careers-form mb-4">
        <div class="card-body">
          <h5 class="fw-bold mb-3">Apply</h5>
          <form action="{{ route('career.apply', $job->slug) }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div id="file-error-alert" class="alert alert-danger d-none small py-2">
                File too large! Maximum CV size is 2MB.
            </div>

            <div class="mb-3">
              <label class="form-label">Full Name</label>
              <input type="text" name="full_name" class="form-control" value="{{ old('full_name') }}" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Phone</label>
              <input type="tel" name="phone" class="form-control" maxlength="13" value="{{ old('phone') }}" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Upload CV</label>
              <input type="file" name="cv" id="cv_input" class="form-control mb-2 @error('cv') is-invalid @enderror" accept=".pdf" required>
              @error('cv')
                <div class="text-danger small mb-1">{{ $message }}</div>
              @enderror
              <small class="text-danger fst-italic d-block">*PDF format, Maximum 2MB</small>
            </div>
            <button type="submit" class="btn btn-apply">Send</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@push('scripts')
    @vite(['resources/js/test-background.js', 'resources/js/cv-upload.js'])
@endpush