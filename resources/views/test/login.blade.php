@extends('layouts.test')

@section('content')
<div class="container py-5 mt-5">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card shadow-sm border-0">
        <div class="card-body p-4">
          <h4 class="text-center mb-4">Masuk ke Halaman Tes</h4>
          @if(session('error'))
            <div class="alert alert-danger">
              {{ session('error') }}
            </div>
          @endif

          <form action="{{ route('test.authenticate') }}" method="POST">
            @csrf
            <div class="mb-3">
              <label for="email" class="form-label">Email Pelamar</label>
              <input type="email" class="form-control" id="email" name="email" required placeholder="email@contoh.com">
            </div>
            <div class="mb-3">
              <label for="application_id" class="form-label">ID Aplikasi</label>
              <input type="text" class="form-control" id="application_id" name="application_id" required placeholder="Contoh: 29">
              <small class="text-muted">Masukkan ID Aplikasi yang Anda dapatkan setelah melamar.</small>
            </div>
            <div class="d-grid">
              <button type="submit" class="btn btn-primary">Masuk & Mulai Tes</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
