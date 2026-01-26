@extends('layouts.test')

@section('content')
<div class="container py-5">
  <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card shadow-sm text-center">
          <div class="card-header bg-success text-white">
              <h4 class="mb-0">Tes Selesai</h4>
          </div>
          <div class="card-body py-5">
            <h1 class="display-3 text-success mb-3"><i class="bi bi-check-circle-fill"></i></h1>
            <h3 class="font-weight-bold">Terima Kasih!</h3>
            <p class="lead mt-3">Jawaban tes Anda telah berhasil kami terima dan simpan ke dalam sistem.</p>
            <p class="text-muted">Tim HR kami akan segera mereview hasil tes Anda. Silakan tunggu informasi selanjutnya via email atau dashboard.</p>
            <a href="{{ route('home') }}" class="btn btn-outline-primary mt-4">Kembali ke Beranda</a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
