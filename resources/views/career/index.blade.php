@extends('layouts.landing')

@section('content')
<!-- Hero Section -->
<div class="py-4">
  <div class="p-5 mb-4 rounded-3 position-relative" style="">
    <div class="position-absolute top-0 start-0 w-100 h-100" style="background-color: rgba(33, 37, 41, 0.7);"></div>
    <div class="container py-5 text-center position-relative text-white">
      <h4 class="fw-bold">Careers</h4>
      <p class="mb-0"><a href="{{ route('home') }}" class="text-white text-decoration-none">Home</a> / Careers</p>
    </div>
  </div>
</div>

<!-- Success Alert -->
@if(session('success'))
  <div class="container">
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <strong>✅ Berhasil!</strong> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  </div>
@endif

<div class="container py-5">
  @if($jobsByDepartment->first()->isEmpty())
    <div class="text-center">
      <h5>Tidak ada lowongan yang tersedia saat ini</h5>
      <p>Silakan periksa kembali nanti atau hubungi kami untuk informasi lebih lanjut</p>
    </div>
  @else
    @foreach($jobsByDepartment as $department => $jobs)
      <div class="mb-5">
        <h3 class="fw-bold mb-4 text-primary">
          <i class="fas fa-diagram-3"></i> {{ $department }}
        </h3>
        <div class="row">
          @foreach($jobs as $job)
            <div class="col-md-6 mb-4">
              <div class="card h-100 shadow-sm border-0 hover-lift">
                <div class="card-body">
                  <h5 class="card-title fw-bold">{{ $job['title'] }}</h5>
                  <div class="mb-3">
                    <span class="badge bg-primary text-white">
                      {{ $job->status }}
                    </span>
                  </div>
                  <p class="card-text text-muted mb-3">
                    {{ \Illuminate\Support\Str::limit($job['description'], 100) }}
                  </p>
                  <a href="{{ route('career.show', $job['slug']) }}" class="btn btn-primary">
                    Lihat Detail & Apply
                  </a>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    @endforeach
  @endif
</div>

<style>
  .hover-lift {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }
  
  .hover-lift:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.15) !important;
  }
</style>
@endsection
