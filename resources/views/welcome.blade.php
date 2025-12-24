@extends('layouts.app') 

@section('content')
<div class="lms-hero p-4 p-md-5 mb-4 reveal is-visible" style="background-image: url('/images/lms/hero-library.jpg');">
    <div class="py-4 py-md-5">
        @php
            $brandLogoDiskPath = public_path('images/lms/logo.png');
            $brandLogoVersion = file_exists($brandLogoDiskPath) ? filemtime($brandLogoDiskPath) : null;
            $brandLogoUrl = $brandLogoVersion ? (asset('images/lms/logo.png') . '?v=' . $brandLogoVersion) : null;
        @endphp

        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <div class="d-flex align-items-center gap-2 mb-3">
                    @if($brandLogoUrl)
                        <img src="{{ $brandLogoUrl }}" alt="Logo" width="44" height="44" class="rounded" style="object-fit: cover;">
                    @endif
                    <div class="text-white-50 small">{{ config('app.name', 'LMS') }}</div>
                </div>
                <h1 class="display-5 fw-bold text-white mb-2">Learn. Practice. Improve.</h1>
                <p class="fs-5 text-white-50 mb-4">
                    A simple LMS with course content, quizzes, assignments, and announcements — built for a smooth teacher/student workflow.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('login') }}" class="btn btn-light btn-lg hover-lift">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Login
                    </a>
                    <a href="{{ route('register') }}" class="btn btn-outline-light btn-lg hover-lift">
                        <i class="bi bi-person-plus me-2"></i>Register
                    </a>
                    <a href="{{ route('courses.index') }}" class="btn btn-outline-light btn-lg hover-lift">
                        <i class="bi bi-journals me-2"></i>Browse Courses
                    </a>
                </div>
                <div class="text-white-50 small mt-3">
                    Tip: Enrollment is managed by teachers.
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm bg-body-tertiary bg-opacity-75 reveal">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-3">
                            <div class="fs-3 text-primary"><i class="bi bi-mortarboard"></i></div>
                            <div>
                                <div class="fw-semibold">Role-based dashboards</div>
                                <div class="text-muted">Admin, Teacher, Student — each has a focused workspace.</div>
                            </div>
                        </div>
                        <hr>
                        <div class="d-flex align-items-start gap-3">
                            <div class="fs-3 text-success"><i class="bi bi-check2-square"></i></div>
                            <div>
                                <div class="fw-semibold">Quizzes & assignments</div>
                                <div class="text-muted">Attempt quizzes, submit work, and get feedback.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 g-md-4">
    <div class="col-md-4 reveal">
        <div class="card h-100 border-0 shadow-sm hover-lift">
            <img src="/images/lms/hero-campus.jpg" class="card-img-top" alt="Campus" style="max-height: 180px; object-fit: cover;">
            <div class="card-body">
                <h5 class="card-title mb-1">Explore courses</h5>
                <p class="card-text text-muted">See the public catalog and discover what’s available.</p>
                <a href="{{ route('courses.index') }}" class="btn btn-sm btn-outline-secondary">View Catalog</a>
            </div>
        </div>
    </div>
    <div class="col-md-4 reveal">
        <div class="card h-100 border-0 shadow-sm hover-lift">
            <img src="/images/lms/hero-study.jpg" class="card-img-top" alt="Study" style="max-height: 180px; object-fit: cover;">
            <div class="card-body">
                <h5 class="card-title mb-1">Learn in modules</h5>
                <p class="card-text text-muted">Content is organized by sections for a clean experience.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 reveal">
        <div class="card h-100 border-0 shadow-sm hover-lift">
            <img src="/images/lms/hero-graduation.jpg" class="card-img-top" alt="Graduation" style="max-height: 180px; object-fit: cover;">
            <div class="card-body">
                <h5 class="card-title mb-1">Track progress</h5>
                <p class="card-text text-muted">Review attempts, submissions, and feedback in one place.</p>
            </div>
        </div>
    </div>
</div>
@endsection