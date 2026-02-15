@extends('layouts.test')

@section('content')
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
      <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-4 p-md-5 text-center">
          <div class="mb-4">
            <div class="bg-success-subtle text-success d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 80px; height: 80px;">
              <i class="fa-solid fa-check fs-3"></i>
            </div>
          </div>
          <h4 class="fw-bold mb-3">Bagian 1 Selesai!</h4>
          <p class="text-muted mb-4">Anda telah menyelesaikan sesi Knowledge & Foundation.</p>
          <div class="alert border-0 shadow-sm text-start mb-4 p-4" style="background-color: #f8f9fa;">
            <div class="d-flex align-items-center mb-3">
              <h6 class="fw-bold mb-0 text-dark me-2">Technical & Case Study</h6>
            </div>
            <ul class="list-unstyled mb-0 small text-secondary vstack gap-2">
              <li class="d-flex"><i class="bi bi-clock me-2 text-warning"></i> <strong>Waktu:</strong> &nbsp;30 Menit</li>
              <li class="d-flex"><i class="bi bi-bullseye me-2 text-warning"></i> <strong>Fokus:</strong> &nbsp;
                @if(str_contains(strtolower($application->jobVacancy->title), 'developer') || 
                    str_contains(strtolower($application->jobVacancy->title), 'programmer') ||
                    str_contains(strtolower($application->jobVacancy->title), 'engineer'))
                  Code Analysis, Architecture, Debugging
                @else
                  Scenario, Decision Making, Strategy
                @endif
              </li>
              <li class="d-flex"><i class="bi bi-exclamation-circle me-2 text-warning"></i>Ini adalah tahap terakhir tes.</li>
            </ul>
          </div>

          <div class="d-grid">
            <a href="{{ route('test.show', $application->id) }}" class="btn btn-dark-grey py-3 rounded-3 fw-bold">
              Lanjut ke Bagian 2 <i class="bi bi-arrow-right ms-2"></i>
            </a>
          </div>
          
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
