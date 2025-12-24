@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <h2 class="mb-4 border-bottom pb-2 text-info">Courses I Teach</h2>
    </div>
</div>

<div class="row" id="courseList">
    <div class="col-12 text-center py-5" id="loadingMessage">
        <div class="spinner-border text-info" role="status">
            <span class="visually-hidden">Loading courses...</span>
        </div>
        <p class="mt-2">Fetching courses you have created...</p>
    </div>
    
    <div class="col-12 text-center py-5 d-none" id="noCoursesMessage">
        <p class="fs-4">You haven't created any courses yet. <a href="{{ route('teacher.create_course') }}">Create your first course!</a></p>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const user = getUserData();
        if (!user || user.role !== 'teacher') {
            alert('Access Denied. Teachers only.');
            window.location.href = '/dashboard';
            return;
        }

        const courseList = document.getElementById('courseList');
        const loadingMessage = document.getElementById('loadingMessage');
        const noCoursesMessage = document.getElementById('noCoursesMessage');

        // Function to create an individual course card HTML
        function createTeacherCourseCard(course) {
            const desc = (course.description || '').toString();
            const preview = desc.length > 160 ? (desc.substring(0, 160) + '...') : desc;
            const enrolled = (typeof course.enrollments_count === 'number') ? course.enrollments_count : 0;
            return `
                <div class="col-md-6 mb-4">
                    <div class="card h-100 shadow-sm border-left-info">
                        <div class="card-body">
                            <h5 class="card-title text-info">${course.title}</h5>
                            <h6 class="card-subtitle mb-2 text-muted">Course ID: ${course.id}</h6>
                            <p class="card-text">${preview || '<span class="text-muted">No description</span>'}</p>
                            <div class="small text-muted">Enrolled students: <strong>${enrolled}</strong></div>
                        </div>
                        <div class="card-footer d-flex justify-content-end">
                            {{-- This link now uses the course ID to go to the management page --}}
                            <a href="/teacher/course/${course.id}/manage" class="btn btn-sm btn-warning">Manage Content</a>
                        </div>
                    </div>
                </div>
            `;
        }

        // Fetch teacher's assigned courses via JWT
        apiFetch('/api/teacher/my-courses')
            .then(response => response.json())
            .then(data => {
                loadingMessage.classList.add('d-none');

                if (data.status === true && Array.isArray(data.data) && data.data.length > 0) {
                    data.data.forEach(course => {
                        courseList.insertAdjacentHTML('beforeend', createTeacherCourseCard(course));
                    });
                } else {
                    noCoursesMessage.classList.remove('d-none');
                }
            })
            .catch(error => {
                loadingMessage.innerHTML = `<p class="text-danger">Error loading courses. Please try again.</p>`;
                console.error('Error fetching teacher courses:', error);
            });
    });
</script>
@endsection