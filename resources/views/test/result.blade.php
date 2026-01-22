@extends('layouts.test')

@section('content')
<div class="container py-5">
  <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card shadow-sm text-center">
          <div class="card-header bg-{{ $score >= 50 ? 'success' : 'warning' }} text-white">
              <h4 class="mb-0">Hasil Tes Kemampuan</h4>
          </div>
          <div class="card-body py-5">
            <h1 class="display-4 font-weight-bold mb-3">{{ $score }}</h1>
            <p class="lead">Anda menjawab benar <strong>{{ $correctCount }}</strong> dari <strong>{{ $totalQuestions }}</strong> soal.</p>
            
            @if($score >= 80)
              <div class="alert alert-success mt-4">
                Sangat Baik! Hasil Anda sangat memuaskan.
              </div>
            @elseif($score >= 50)
              <div class="alert alert-warning mt-4">
                Cukup Baik. Anda lulus passing grade minimum.
              </div>
            @else
              <div class="alert alert-danger mt-4">
                Mohon maaf, hasil Anda belum memenuhi standar minimum.
              </div>
            @endif
            <a href="{{ route('home') }}" class="btn btn-outline-primary mt-4">Kembali ke Beranda</a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
