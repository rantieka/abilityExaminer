@extends('layouts.landing')

@section('content')
<!-- Hero Section -->
<div class="py-4">
    <div class="p-5 mb-4 bg-light rounded-3">
        <div class="container py-5">
            <h1 class="display-5 fw-bold">Join Our Team</h1>
            <p class="col-md-8 fs-4">Cari posisi yang tepat dan mulai karir impianmu bersama kami</p>
        </div>
    </div>
</div>
<div class="container py-5">
    @if($jobsByDepartment->isEmpty())
        <div class="alert alert-info text-center" role="alert">
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
                                        <span class="badge bg-light text-dark me-2">
                                            📍 {{ $job['location'] }}
                                        </span>
                                        <span class="badge bg-light text-dark">
                                            🕐 {{ $job['job_type'] }}
                                        </span>
                                    </div>
                                    
                                    <p class="card-text text-muted mb-3">
                                        {{ \Illuminate\Support\Str::limit($job['description'], 100) }}
                                    </p>
                                    
                                    @if(isset($job['salary_min']) && isset($job['salary_max']))
                                        <p class="card-text mb-3">
                                            <strong>Gaji:</strong> 
                                            Rp {{ number_format($job['salary_min'], 0, ',', '.') }} - 
                                            Rp {{ number_format($job['salary_max'], 0, ',', '.') }}
                                        </p>
                                    @endif
                                    
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

<!-- Back to Home -->
<div class="container mb-5">
    <a href="{{ route('home') }}" class="btn btn-outline-secondary">
        ← Kembali ke Home
    </a>
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
