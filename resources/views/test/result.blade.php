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
            <h3 class="fw-bold mb-3">Tes Selesai!</h3>
            <p class="text-secondary lead mb-4">Jawaban Anda telah berhasil kami simpan.</p>
            <div class="alert border-0 shadow-sm p-4 mb-4 text-start" style="background-color: #f8f9fa;">
              <p class="mb-0 text-muted small">
                Tim HR kami akan segera mereview hasil tes Anda. Silakan tunggu informasi selanjutnya melalui email.
            </p>
            </div>
            <a href="{{ route('home') }}" class="btn btn-dark-grey py-3 px-5 rounded-3 fw-bold">
              Kembali ke Beranda
            </a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
