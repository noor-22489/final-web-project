@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <h2 class="mb-4 border-bottom pb-2 text-primary">My Enrolled Courses</h2>
    </div>
</div>

<div class="row" id="myCoursesList">
    <div class="col-12 text-center py-5" id="loadingMessage">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading my courses...</span>
        </div>
        <p class="mt-2">Fetching your enrolled courses...</p>
    </div>
    
    <div class="col-12 text-center py-5 d-none" id="noEnrollmentsMessage">
        <p class="fs-4">You are not currently enrolled in any courses. <a href="{{ route('courses.index') }}">Explore Courses</a> now!</p>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const courseList = document.getElementById('myCoursesList');
        const loadingMessage = document.getElementById('loadingMessage');
        const noEnrollmentsMessage = document.getElementById('noEnrollmentsMessage');

        // Function to create a card for an enrolled course
        function createEnrolledCourseCard(enrollment) {
            const course = enrollment.course;
            return `
                <div class="col-md-6 mb-4">
                    <div class="card h-100 shadow-sm border-left-primary">
                        <div class="card-body">
                            <h5 class="card-title text-success">${course.title}</h5>
                            <h6 class="card-subtitle mb-2 text-muted">Taught By: ${course.teacher.name}</h6>
                            <p class="card-text">${course.description.substring(0, 150)}...</p>
                            <span class="badge bg-info text-dark">Enrolled on: ${new Date(enrollment.created_at).toLocaleDateString()}</span>
                        </div>
                        <div class="card-footer d-flex justify-content-end">
                            <a href="/course/${course.id}/view" class="btn btn-primary btn-sm">View Course Content</a>
                        </div>
                    </div>
                </div>
            `;
        }

        // Fetching the student's enrolled course list
        // We use apiFetch because it requires the JWT token
        apiFetch('/api/student/my-courses')
            .then(response => response.json())
            .then(data => {
                loadingMessage.classList.add('d-none');
                
                if (data.status === true && data.data.length > 0) {
                    data.data.forEach(enrollment => {
                        courseList.insertAdjacentHTML('beforeend', createEnrolledCourseCard(enrollment));
                    });
                } else {
                    noEnrollmentsMessage.classList.remove('d-none');
                }
            })
            .catch(error => {
                // apiFetch handles the redirect on token failure
                if (error !== 'No token found') {
                    loadingMessage.innerHTML = `<p class="text-danger">Error loading your courses.</p>`;
                }
                console.error('Error fetching my courses:', error);
            });
    });
</script>
@endsection