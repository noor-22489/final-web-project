@extends('layouts.app')

@section('content')
<div class="lms-hero lms-hero-half d-flex align-items-center p-4 p-md-5 mb-4 reveal is-visible" style="background-image: url('/images/lms/hero-building.jpg');">
    <div class="w-100 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
            <div class="text-white-50 small">Teacher</div>
            <h2 class="text-white mb-1">Dashboard</h2>
            <div class="text-white-50">Welcome back, <strong id="userName">Teacher</strong>.</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('teacher.create_course') }}" class="btn btn-light hover-lift"><i class="bi bi-plus-circle me-2"></i>Create Course</a>
            <a href="{{ route('teacher.my_courses') }}" class="btn btn-outline-light hover-lift"><i class="bi bi-kanban me-2"></i>Manage Courses</a>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6 reveal">
        <a href="{{ route('teacher.my_courses') }}" class="text-decoration-none text-reset">
            <div class="card border-0 shadow-sm hover-lift h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3">
                        <div class="fs-3 text-info"><i class="bi bi-folder-plus"></i></div>
                        <div>
                            <div class="fw-semibold">Build your course</div>
                            <div class="text-muted">Create sections, upload materials, add quizzes, assignments, and announcements.</div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-6 reveal">
        <a href="{{ route('teacher.my_courses') }}" class="text-decoration-none text-reset">
            <div class="card border-0 shadow-sm hover-lift h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3">
                        <div class="fs-3 text-success"><i class="bi bi-people"></i></div>
                        <div>
                            <div class="fw-semibold">Enroll students</div>
                            <div class="text-muted">Use the Enrollments tab inside a course to add students by email.</div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-md-12 reveal">
        <a href="{{ route('teacher.my_courses') }}" class="text-decoration-none text-reset">
            <div class="card border-0 shadow-sm hover-lift">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3">
                        <div class="fs-3 text-warning"><i class="bi bi-clock-history"></i></div>
                        <div>
                            <div class="fw-semibold">Quiz tools</div>
                            <div class="text-muted">Open a course → Quizzes tab to extend quiz deadlines or delete quizzes.</div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="text-muted small mt-3">
    <a href="{{ route('courses.index') }}">Go to Courses</a>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const user = getUserData();
        if (!getAuthToken() || !user) {
            window.location.href = '/login';
            return;
        }
        if (user.role !== 'teacher') {
            window.location.href = user.role === 'admin' ? '/admin/dashboard' : '/student/dashboard';
            return;
        }
        if (user.name) {
            document.getElementById('userName').textContent = user.name;
        }
    });
</script>
@endsection
