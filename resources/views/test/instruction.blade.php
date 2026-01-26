@extends('layouts.test')

@section('content')
<div class="container py-5 mt-5">
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="card shadow-lg border-0">
        <div class="card-header bg-success text-white text-center py-4">
          <h4 class="mb-0">🎉 Part 1 Selesai!</h4>
        </div>
        <div class="card-body p-5 text-center">
          <div class="mb-4">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
          </div>
          
          <h5 class="mb-3">Selamat! Anda telah menyelesaikan Part 1: Knowledge & Foundation</h5>
          
          <div class="alert alert-info text-start my-4">
            <h6 class="alert-heading"><strong>📋 Instruksi Part 2:</strong></h6>
            <ul class="mb-0">
              <li><strong>Jenis Soal:</strong> Technical & Case Study</li>
              <li><strong>Waktu:</strong> 30 menit</li>
              <li><strong>Fokus:</strong> 
                @if(str_contains(strtolower($application->jobVacancy->title), 'developer') || 
                    str_contains(strtolower($application->jobVacancy->title), 'programmer') ||
                    str_contains(strtolower($application->jobVacancy->title), 'engineer'))
                  Code Analysis, Architecture, Debugging, Best Practices
                @else
                  Workplace Scenarios, Decision Making, Ethical Dilemmas, Strategic Thinking
                @endif
              </li>
              <li><strong>Catatan:</strong> Setelah submit Part 2, tes akan selesai dan skor akan dihitung otomatis</li>
            </ul>
          </div>

          <div class="my-4">
            <p class="text-muted">Pastikan Anda siap sebelum memulai Part 2. Timer akan dimulai segera setelah Anda klik tombol di bawah.</p>
          </div>

          <a href="{{ route('test.show', $application->id) }}" class="btn btn-success btn-lg px-5 py-3">
            <i class="bi bi-play-circle-fill me-2"></i>Mulai Part 2
          </a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
