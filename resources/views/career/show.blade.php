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
        <h5 class="fw-bold mb-3">Qualifications</h5>
        <p>{!! $job->qualifications !!}</p>
      </div>
      <div class="mb-4">
        @if($job->location || \App\Models\Setting::get('office_address'))
          <h5 class="fw-bold mb-3">Office Location</h5>
          <div class="mb-0">
            @if($job->location)
              {!! $job->location !!}
            @else
              {!! nl2br(e(\App\Models\Setting::get('office_address'))) !!}
            @endif
          </div>
        @endif
      </div>
    </div>
    <div class="col-md-4">
      <div class="careers-form mb-4">
        <div class="card-body">
          <h5 class="fw-bold mb-3">Apply</h5>
          <form action="{{ route('career.apply', $job->slug) }}" method="POST" enctype="multipart/form-data">
            @csrf
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
              <input type="file" name="cv" class="form-control mb-2" accept=".pdf" required>
              <small class="text-danger fst-italic">*Only PDF files are allowed</small>
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
    @vite(['resources/js/test-background.js'])
@endpush