@extends('layouts.test')

@section('content')
<div class="container py-3" id="test-container" data-app-id="{{ $application->id }}" data-part="part1" data-remaining="{{ $remaining ?? 0 }}">
    @vite(['resources/js/test-timer.js'])
    <div class="row justify-content-center">
    <div class="col-lg-8">
      <!-- Sticky Info & Timer -->
      <div class="sticky-top bg-white shadow-sm rounded-4 p-3 mb-4 d-flex justify-content-between align-items-center" style="top: 1rem; z-index: 1020; border: 1px solid rgba(0,0,0,0.05);">
        <div>
           <span class="badge badge-yellow rounded-pill px-3 py-2">Bagian 1: Knowledge</span>
        </div>
        @if($questions->count() > 0)
          <div class="d-flex align-items-center text-danger">
             <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-stopwatch me-2" viewBox="0 0 16 16">
                <path d="M8.5 5.6a.5.5 0 1 0-1 0v2.9h-3a.5.5 0 0 0 0 1H8a.5.5 0 0 0 .5-.5V5.6z"/>
                <path d="M6.5 1A.5.5 0 0 1 7 .5h2a.5.5 0 0 1 0 1v.57c1.36.196 2.594.78 3.584 1.64a.715.715 0 0 1 .012-.013l.354-.354-.354-.353a.5.5 0 0 1 .707-.708l1.414 1.415a.5.5 0 1 1-.707.707l-.353-.354-.354.354a.512.512 0 0 1-.013.012A7 7 0 1 1 7 2.071V1.5a.5.5 0 0 1-.5-.5zM8 3a6 6 0 1 0 .001 12A6 6 0 0 0 8 3z"/>
             </svg>
             <span id="time" class="fw-bold fs-5 font-monospace">30:00</span>
          </div>
        @endif
      </div>
      <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-4 p-md-5">
          @if(session('success'))
            <div class="alert alert-success border-0 bg-success-subtle text-success rounded-3 mb-4">{{ session('success') }}</div>
          @endif
          
          @if($questions->isEmpty())
            <div class="text-center py-5">
                <div class="mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="#dee2e6" class="bi bi-journal-x" viewBox="0 0 16 16">
                      <path fill-rule="evenodd" d="M6.146 6.146a.5.5 0 0 1 .708 0L8 7.293l1.146-1.147a.5.5 0 1 1 .708.708L8.707 8l1.147 1.146a.5.5 0 0 1-.708.708L8 8.707 6.854 9.854a.5.5 0 0 1-.708-.708L7.293 8 6.146 6.854a.5.5 0 0 1 0-.708z"/>
                      <path d="M3 0h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-1h1v1a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v1H1V2a2 2 0 0 1 2-2z"/>
                      <path d="M1 5v-.5a.5.5 0 0 1 1 0V5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1zm0 3v-.5a.5.5 0 0 1 1 0V8h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1zm0 3v-.5a.5.5 0 0 1 1 0v.5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1z"/>
                    </svg>
                </div>
                <h4 class="fw-bold text-secondary">Belum ada soal</h4>
                <p class="text-muted mb-4">Silakan hubungi admin untuk informasi lebih lanjut.</p>
                <a href="{{ route('home') }}" class="btn btn-dark-grey mt-3 py-2 px-4">Kembali ke Beranda</a>
            </div>
          @else
            <div class="mb-5 pb-2 border-bottom">
              <h4 class="fw-bold mb-2 text-dark">{{ $application->jobVacancy->title }}</h4>
            </div>
            
            <form action="{{ route('test.submitPart1', $application->id) }}" method="POST" id="testForm">
            @csrf
            
            @foreach($questions as $index => $question)
              <div class="question-block mb-5">
                <div class="d-flex mb-3">
                    <span class="flex-shrink-0 bg-light text-dark fw-bold rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px;">{{ $index + 1 }}</span>
                    <h5 class="fw-bold mb-0 pt-1" style="line-height: 1.6;">
                      {!! preg_replace('/```(?:php)?(.*?)```/s', '<pre class="bg-dark text-light p-3 rounded-3 mt-2 shadow-sm"><code>$1</code></pre>', e($question->question_text)) !!}
                    </h5>
                </div>
                
                <div class="options-list ps-5">
                @foreach($question->options as $key => $option)
                  <div class="mb-2">
                    <input class="btn-check" type="radio" 
                      name="answers[{{ $question->id }}]" 
                      id="q{{ $question->id }}_{{ $key }}" 
                      value="{{ $key }}" required>
                    
                    <label class="btn btn-outline-light text-dark w-100 text-start p-3 rounded-3 d-flex align-items-center border" for="q{{ $question->id }}_{{ $key }}" style="border-color: #dee2e6;">
                        <span class="badge bg-secondary-subtle text-secondary me-3 rounded px-2" style="min-width: 30px;">{{ $key }}</span>
                        <span>{{ $option }}</span>
                    </label>
                  </div>
                @endforeach
                </div>
              </div>
            @endforeach

            <div class="d-flex justify-content-end mt-5 pt-4 border-top">
              <button type="submit" class="btn btn-dark-grey py-2 px-4" onclick="return confirm('Selesai Part 1 dan lanjut ke Part 2?')">
                Lanjut
                <i class="fa-solid fa-arrow-right-long ps-2"></i>
              </button>
            </div>
            </form>
          @endif
        </div>
      </div>
    </div>
</div>

@endsection
