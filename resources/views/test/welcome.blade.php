@extends('layouts.test')

@section('content')
<div class="container py-5 mt-5">
  <div class="row justify-content-center">
    <div class="col-lg-10">
      <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 16px;">
        <div class="text-center pt-5 px-4">
          <h5 class="font-weight-bold mb-2">Halo, {{ $application->full_name }}!</h5>
          <span class="text-muted">{{ $application->jobVacancy->title }}</span>
        </div>
        <div class="card-body p-4 p-md-5">
          <!-- Structure Section -->
          <div class="mb-5">
            <div class="row g-4">
              <!-- Part 1 Card -->
              <div class="col-md-6">
                <div class="card h-100 border text-center p-4 card-hover" style="background: #fff;">
                  <div class="mb-3">
                    <span class="badge-part badge-part-1">Bagian 1</span>
                  </div>
                  <h5 class="fw-bold mb-3">Knowledge & Foundation</h5>
                  <p class="text-muted small mb-4">Menguji pemahaman dasar, logika, dan penyelesaian masalah.</p>
                  <div class="d-flex justify-content-center gap-3 text-muted small">
                    <div class="d-flex align-items-center"><i class="bi bi-file-text me-1"></i> 20 Soal</div>
                    <div class="d-flex align-items-center"><i class="bi bi-clock me-1"></i> 30 Menit</div>
                  </div>
                </div>
              </div>
              <!-- Part 2 Card -->
              <div class="col-md-6">
                <div class="card h-100 border text-center p-4 card-hover" style="background: #fff;">
                  <div class="mb-3">
                    <span class="badge-part badge-part-2">Bagian 2</span>
                  </div>
                  <h5 class="fw-bold mb-3">Technical & Case Study</h5>
                  <p class="text-muted small mb-4">
                    @if(str_contains(strtolower($application->jobVacancy->title), 'developer') || 
                        str_contains(strtolower($application->jobVacancy->title), 'programmer') ||
                        str_contains(strtolower($application->jobVacancy->title), 'engineer'))
                      Analisis kode, arsitektur sistem, dan debugging.
                    @else
                      Pengambilan keputusan, studi kasus, dan berpikir strategis.
                    @endif
                  </p>
                  <div class="d-flex justify-content-center gap-3 text-muted small">
                    <div class="d-flex align-items-center"><i class="bi bi-file-text me-1"></i> 20 Soal</div>
                    <div class="d-flex align-items-center"><i class="bi bi-clock me-1"></i> 30 Menit</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- Divider -->
          <hr class="my-5 opacity-10">
          <div class="row g-3">
            <!-- Rules Section -->
            <div class="col-12">
              <h4 class="fw-bold text-dark mb-4">Peraturan Tes</h4>
              <ol class="list-numbered-orange">
                <li>Tes terdiri dari <strong>2 bagian</strong> yang wajib dikerjakan berurutan.</li>
                <li>Setiap bagian memiliki timer otomatis selama <strong>30 menit</strong>.</li>
                <li>Jawaban akan <strong class="text-danger">tersimpan otomatis</strong> saat waktu habis.</li>
                <li>Pastikan koneksi internet Anda <strong>stabil</strong>.</li>
                <li><strong>Tidak ada kesempatan mengulang</strong> setelah submit.</li>
              </ol>
            </div>
            <!-- Tips Section -->
            <div class="col-12">
              <h4 class="fw-bold text-dark mb-4">Tips & Trik</h4>
              <ol class="list-numbered-orange">
                <li>Siapkan waktu min. <strong>60 menit</strong>.</li>
                <li>Cari tempat yang kondusif & tenang.</li>
                <li>Gunakan Laptop/PC untuk kenyamanan.</li>
                <li>Baca soal dengan teliti & tenang.</li>
              </ol>
            </div>
          </div>

          <!-- CTA Section -->
          <div class="pt-4 text-center">
            <form action="{{ route('test.start', $application->id) }}" method="POST">
              @csrf
              <button type="submit" class="btn btn-dark-grey py-3 px-4 rounded-3 fw-bold" onclick="return confirm('Apakah Anda yakin sudah siap memulai tes? Timer akan dimulai segera setelah Anda klik OK.')">
                Mulai Tes Sekarang
                <i class="fa-solid fa-arrow-right ms-2"></i>
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
