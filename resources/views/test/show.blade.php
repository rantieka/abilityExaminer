@extends('layouts.test')

@section('content')
<div class="container py-5 mt-5">
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Tes Kemampuan - {{ $application->jobVacancy->title }}</h5>
          @if($questions->count() > 0)
            <div id="timer" class="font-weight-bold" style="font-size: 1.2rem;">
              <span id="time">30:00</span>
            </div>
          @endif
        </div>
        <div class="card-body">
          @if($questions->isEmpty())
            <div class="alert alert-info text-center">
              Belum ada soal untuk lowongan ini. Silakan hubungi admin.
              <br>
              <a href="{{ route('home') }}" class="btn btn-dark-grey mt-3 py-2 px-4">Kembali ke Beranda</a>
            </div>
          @else
            <form action="{{ route('test.submit', $application->id) }}" method="POST" id="testForm">
            @csrf
            
            @foreach($questions as $index => $question)
              <div class="mb-4">
                <p class="font-weight-bold">{{ $index + 1 }}. {{ $question->question_text }}</p>
                @foreach($question->options as $key => $option)
                  @php $letter = is_numeric($key) ? chr(65 + (int)$key) : $key; @endphp
                  <div class="form-check">
                    <input class="form-check-input" type="radio" 
                      name="answers[{{ $question->id }}]" 
                      id="q{{ $question->id }}_{{ $letter }}" 
                      value="{{ $letter }}" required>
                    <label class="form-check-label" for="q{{ $question->id }}_{{ $letter }}">
                      {{ $letter }}. {{ $option }}
                    </label>
                  </div>
                @endforeach
              </div>
              <hr>
            @endforeach

            <div class="text-end">
              <button type="submit" class="btn btn-primary btn-lg" onclick="return confirm('Apakah Anda yakin ingin mengumpulkan jawaban?')">Kirim Jawaban</button>
            </div>
            </form>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  // Timer Logic
  function startTimer(duration, display) {
    var timer = duration, minutes, seconds;
    var interval = setInterval(function () {
      minutes = parseInt(timer / 60, 10);
      seconds = parseInt(timer % 60, 10);

      minutes = minutes < 10 ? "0" + minutes : minutes;
      seconds = seconds < 10 ? "0" + seconds : seconds;

      display.textContent = minutes + ":" + seconds;

      if (--timer < 0) {
        clearInterval(interval);
        alert("Waktu habis! Jawaban Anda akan dikirim otomatis.");
        document.getElementById("testForm").submit();
      }
    }, 1000);
  }

  document.addEventListener('DOMContentLoaded', function () {
    var thirtyMinutes = 60 * 30,
      display = document.querySelector('#time');
    startTimer(thirtyMinutes, display);
  });
</script>
@endsection
