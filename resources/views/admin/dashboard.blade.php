@extends('layouts.app')

@section('content')
<div class="lms-hero lms-hero-half d-flex align-items-center p-4 p-md-5 mb-4 reveal is-visible" style="background-image: url('/images/lms/hero-arch.jpg');">
    <div class="w-100 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
            <div class="text-white-50 small">Admin</div>
            <h2 class="text-white mb-1">Dashboard</h2>
            <div class="text-white-50">Welcome back, <strong id="userName">Admin</strong>.</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.teachers.index') }}" class="btn btn-light hover-lift"><i class="bi bi-people me-2"></i>Teachers</a>
            <a href="{{ route('admin.students.index') }}" class="btn btn-light hover-lift"><i class="bi bi-person-badge me-2"></i>Students</a>
            <a href="{{ route('admin.courses.index') }}" class="btn btn-light hover-lift"><i class="bi bi-journal-text me-2"></i>Courses</a>
            <a href="{{ route('admin.rules') }}" class="btn btn-outline-light hover-lift"><i class="bi bi-trophy me-2"></i>Rules</a>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3 reveal">
        <a href="{{ route('admin.students.index') }}" class="text-decoration-none text-reset">
        <div class="card border-0 shadow-sm hover-lift">
            <div class="card-body">
                <div class="text-muted small">Total Students</div>
                <div class="fs-3 fw-bold" id="statStudents">--</div>
            </div>
        </div>
        </a>
    </div>
    <div class="col-md-3 reveal">
        <a href="{{ route('admin.teachers.index') }}" class="text-decoration-none text-reset">
        <div class="card border-0 shadow-sm hover-lift">
            <div class="card-body">
                <div class="text-muted small">Total Teachers</div>
                <div class="fs-3 fw-bold" id="statTeachers">--</div>
            </div>
        </div>
        </a>
    </div>
    <div class="col-md-3 reveal">
        <a href="{{ route('admin.courses.index') }}" class="text-decoration-none text-reset">
        <div class="card border-0 shadow-sm hover-lift">
            <div class="card-body">
                <div class="text-muted small">Total Courses</div>
                <div class="fs-3 fw-bold" id="statCourses">--</div>
            </div>
        </div>
        </a>
    </div>
    <div class="col-md-3 reveal">
        <div class="card border-0 shadow-sm hover-lift">
            <div class="card-body">
                <div class="text-muted small">Quiz Attempts</div>
                <div class="fs-3 fw-bold" id="statAttempts">--</div>
            </div>
        </div>
    </div>
</div>

<div id="statsError" class="alert alert-warning d-none mt-3"></div>

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
        if (user.role !== 'admin') {
            // Send user to their correct dashboard
            window.location.href = user.role === 'teacher' ? '/teacher/dashboard' : '/student/dashboard';
            return;
        }
        if (user.name) {
            document.getElementById('userName').textContent = user.name;
        }

        // Load admin stats
        const err = document.getElementById('statsError');
        apiFetch('/api/admin/stats')
            .then(r => r.json())
            .then(data => {
                if (!data || data.status !== true) {
                    throw new Error(data?.message || 'Failed to load stats');
                }
                document.getElementById('statStudents').textContent = data.data.total_students;
                document.getElementById('statTeachers').textContent = data.data.total_teachers;
                document.getElementById('statCourses').textContent = data.data.total_courses;
                document.getElementById('statAttempts').textContent = data.data.total_quiz_attempts;
            })
            .catch(e => {
                err.textContent = 'Could not load stats. Make sure you are logged in as admin.';
                err.classList.remove('d-none');
                console.error(e);
            });
    });
</script>
@endsection
