@extends('layouts.test')

@section('content')
<div class="container py-5 mt-5">
  <div class="row justify-content-center">
    <div class="col-md-9">
      <div class="card shadow-lg border-0">
        <div class="card-header bg-primary text-white text-center py-4">
          <h3 class="mb-0">📝 Selamat Datang di Tes Kemampuan</h3>
          <p class="mb-0 mt-2">{{ $application->jobVacancy->title }}</p>
        </div>
        <div class="card-body p-5">
          <div class="alert alert-info mb-4">
            <h5 class="alert-heading"><i class="bi bi-info-circle-fill me-2"></i>Informasi Penting</h5>
            <p class="mb-0">Silakan baca instruksi berikut dengan seksama sebelum memulai tes.</p>
          </div>

          <h5 class="mb-3"><strong>📋 Struktur Tes</strong></h5>
          <div class="row mb-4">
            <div class="col-md-6 mb-3">
              <div class="card border-primary">
                <div class="card-body">
                  <h6 class="card-title text-primary"><strong>Part 1: Knowledge & Foundation</strong></h6>
                  <ul class="mb-0">
                    <li><strong>Jumlah Soal:</strong> 20 soal</li>
                    <li><strong>Waktu:</strong> 30 menit</li>
                    <li><strong>Fokus:</strong> Pengetahuan dasar, skenario, problem-solving</li>
                  </ul>
                </div>
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <div class="card border-success">
                <div class="card-body">
                  <h6 class="card-title text-success"><strong>Part 2: Technical & Case Study</strong></h6>
                  <ul class="mb-0">
                    <li><strong>Jumlah Soal:</strong> 20 soal</li>
                    <li><strong>Waktu:</strong> 30 menit</li>
                    <li><strong>Fokus:</strong> 
                      @if(str_contains(strtolower($application->jobVacancy->title), 'developer') || 
                          str_contains(strtolower($application->jobVacancy->title), 'programmer') ||
                          str_contains(strtolower($application->jobVacancy->title), 'engineer'))
                        Code analysis, architecture, debugging
                      @else
                        Decision making, ethical dilemmas, strategic thinking
                      @endif
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>

          <h5 class="mb-3"><strong>⚠️ Peraturan Tes</strong></h5>
          <div class="alert alert-warning">
            <ol class="mb-0">
              <li>Tes terdiri dari <strong>2 bagian (Part 1 dan Part 2)</strong> yang harus dikerjakan secara berurutan</li>
              <li>Setiap bagian memiliki <strong>timer 30 menit</strong> yang akan berjalan otomatis</li>
              <li>Jika waktu habis, jawaban akan <strong>otomatis terkirim</strong></li>
              <li>Pastikan koneksi internet Anda <strong>stabil</strong> selama mengerjakan tes</li>
              <li>Setelah submit Part 1, Anda akan mendapat instruksi sebelum memulai Part 2</li>
              <li><strong>Tidak ada kesempatan mengulang</strong> setelah submit</li>
              <li>Skor akan dihitung otomatis setelah menyelesaikan kedua bagian</li>
            </ol>
          </div>

          <h5 class="mb-3"><strong>💡 Tips</strong></h5>
          <ul>
            <li>Siapkan waktu <strong>minimal 60 menit</strong> tanpa gangguan</li>
            <li>Gunakan <strong>perangkat dengan layar yang nyaman</strong> untuk membaca soal</li>
            <li>Baca setiap soal dengan <strong>teliti</strong> sebelum menjawab</li>
            <li>Kelola waktu dengan baik untuk setiap bagian</li>
          </ul>

          <div class="text-center mt-5">
            <p class="text-muted mb-3">Klik tombol di bawah jika Anda sudah siap memulai tes</p>
            <form action="{{ route('test.start', $application->id) }}" method="POST">
              @csrf
              <button type="submit" class="btn btn-primary btn-lg px-5 py-3" onclick="return confirm('Apakah Anda yakin sudah siap memulai tes? Timer akan dimulai segera setelah Anda klik OK.')">
                <i class="bi bi-play-circle-fill me-2"></i>Mulai Tes Sekarang
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
