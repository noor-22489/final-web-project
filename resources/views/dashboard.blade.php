@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card shadow-lg">
            <div class="card-header bg-secondary text-white">
                <h3 class="mb-0">Redirecting...</h3>
            </div>
            <div class="card-body p-4">
                <p class="text-muted mb-0">Taking you to your dashboard based on your role...</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const user = getUserData();
        const token = getAuthToken();

        if (!token || !user || !user.role) {
            window.location.href = '/login';
            return;
        }

        if (user.role === 'admin') {
            window.location.href = '/admin/dashboard';
        } else if (user.role === 'teacher') {
            window.location.href = '/teacher/dashboard';
        } else {
            window.location.href = '/student/dashboard';
        }
    });
</script>
@endsection
