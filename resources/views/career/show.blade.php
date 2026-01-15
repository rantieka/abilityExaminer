@extends('layouts.landing')
@section('content')
<div class="container py-5">
    <h1 class="display-4 fw-bold mb-4">{{ $job->title }}</h1>
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <h3 class="fw-bold mb-3">Job Description</h3>
            <div>{!! $job->description !!}</div>
        </div>
    </div>
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <h3 class="fw-bold mb-3">Qualifications</h3>
            <div>{!! $job->qualifications !!}</div>
        </div>
    </div>
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h3 class="fw-bold mb-3">Apply Now</h3>
            <form action="{{ route('career.apply', $job->slug) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="tel" name="phone" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Upload CV (PDF)</label>
                    <input type="file" name="cv" class="form-control" accept=".pdf" required>
                </div>
                <button type="submit" class="btn btn-primary btn-lg">Submit Application</button>
            </form>
        </div>
    </div>
    <a href="{{ route('career.index') }}" class="btn btn-outline-secondary mt-4">
        ← Back to Job List
    </a>
</div>
@endsection
