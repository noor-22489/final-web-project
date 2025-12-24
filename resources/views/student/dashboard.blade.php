@extends('layouts.app')

@section('content')
<div class="lms-hero lms-hero-half d-flex align-items-center p-4 p-md-5 mb-4 reveal is-visible" style="background-image: url('/images/lms/hero-study.jpg');">
    <div class="w-100 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
            <div class="text-white-50 small">Student</div>
            <h2 class="text-white mb-1">Dashboard</h2>
            <div class="text-white-50">Welcome back, <strong id="userName">Student</strong>.</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="#enrolled-courses" class="btn btn-light hover-lift"><i class="bi bi-book me-2"></i>Enrolled Courses</a>
            <a href="{{ route('courses.index') }}" class="btn btn-outline-light hover-lift"><i class="bi bi-search me-2"></i>Browse Catalog</a>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6 reveal">
        <a href="#enrolled-courses" class="text-decoration-none text-reset">
            <div class="card border-0 shadow-sm hover-lift h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3">
                        <div class="fs-3 text-primary"><i class="bi bi-play-circle"></i></div>
                        <div>
                            <div class="fw-semibold">Learn & attempt quizzes</div>
                            <div class="text-muted">Open a course to view materials and attempt quizzes with results review.</div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-6 reveal">
        <a href="#enrolled-courses" class="text-decoration-none text-reset">
            <div class="card border-0 shadow-sm hover-lift h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3">
                        <div class="fs-3 text-success"><i class="bi bi-upload"></i></div>
                        <div>
                            <div class="fw-semibold">Submit assignments</div>
                            <div class="text-muted">Upload submissions and check marks/feedback after grading.</div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="d-flex align-items-center justify-content-between mt-4 mb-2 reveal" id="enrolled-courses">
    <div>
        <div class="fw-bold">Enrolled Courses</div>
        <div class="text-muted small">Your teacher-managed enrollments appear here.</div>
    </div>
</div>

<div id="enrolledCoursesEmpty" class="alert alert-secondary d-none reveal">
    <div class="fw-semibold">You are not enrolled in any courses yet.</div>
    <div class="small">Logged in as: <span id="studentEmail">--</span>. Ask your teacher to enroll this email.</div>
</div>

<div id="enrolledCoursesError" class="alert alert-warning d-none reveal"></div>

<div class="row g-3" id="enrolledCoursesGrid"></div>

<div class="d-flex align-items-center justify-content-between mt-4 mb-2 reveal" id="my-certificates">
    <div>
        <div class="fw-bold">My Certificates</div>
        <div class="text-muted small">Certificates appear after your teacher finalizes quiz results.</div>
    </div>
</div>

<div id="certificatesEmpty" class="alert alert-secondary d-none reveal">
    <div class="fw-semibold">No certificates issued yet.</div>
    <div class="small">If you topped a quiz, ask your teacher to finalize results.</div>
</div>

<div id="certificatesError" class="alert alert-warning d-none reveal"></div>

<div class="card border-0 shadow-sm reveal d-none" id="certificatesCard">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Quiz</th>
                        <th style="width: 120px;">Rank</th>
                        <th style="width: 180px;">Issued</th>
                        <th style="width: 160px;"></th>
                    </tr>
                </thead>
                <tbody id="certificatesTbody"></tbody>
            </table>
        </div>
    </div>
</div>

<div class="alert alert-info mt-3 reveal">
    <div class="fw-semibold">Enrollment note</div>
    <div class="small">Enrollment is managed by your teacher. If you don’t see a course, ask your teacher to enroll you.</div>
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
        if (user.role !== 'student') {
            window.location.href = user.role === 'admin' ? '/admin/dashboard' : '/teacher/dashboard';
            return;
        }
        if (user.name) {
            document.getElementById('userName').textContent = user.name;
        }

        if (user.email && document.getElementById('studentEmail')) {
            document.getElementById('studentEmail').textContent = user.email;
        }

        const grid = document.getElementById('enrolledCoursesGrid');
        const empty = document.getElementById('enrolledCoursesEmpty');
        const err = document.getElementById('enrolledCoursesError');

        const certEmpty = document.getElementById('certificatesEmpty');
        const certErr = document.getElementById('certificatesError');
        const certCard = document.getElementById('certificatesCard');
        const certTbody = document.getElementById('certificatesTbody');

        apiFetch('/api/student/my-courses')
            .then(r => r.json())
            .then(data => {
                if (!data || data.status !== true) {
                    throw new Error(data?.message || 'Failed to load courses');
                }

                const enrollments = Array.isArray(data.data) ? data.data : [];
                if (!enrollments.length) {
                    empty.classList.remove('d-none');
                    return;
                }

                grid.innerHTML = enrollments.map(en => {
                    const course = en.course || {};
                    const teacherName = course.teacher?.name || 'Teacher';
                    const title = course.title || 'Untitled Course';
                    const desc = (course.description || '').toString();
                    const safeDesc = desc.length > 140 ? (desc.slice(0, 140) + '…') : desc;
                    const href = `/course/${course.id}/view`;

                    return `
                        <div class="col-md-6 col-lg-4">
                            <a href="${href}" class="text-decoration-none text-reset">
                                <div class="card h-100 border-0 shadow-sm hover-lift">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start justify-content-between gap-3">
                                            <div>
                                                <div class="fw-bold">${title}</div>
                                                <div class="text-muted small">Teacher: ${teacherName}</div>
                                            </div>
                                            <div class="text-muted"><i class="bi bi-arrow-right"></i></div>
                                        </div>
                                        <div class="text-muted small mt-2">${safeDesc || 'Open to view content, quizzes, assignments, and announcements.'}</div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    `;
                }).join('');
            })
            .catch(e => {
                err.textContent = 'Could not load enrolled courses. Please re-login and try again.';
                err.classList.remove('d-none');
                console.error(e);
            });

        function downloadCertificateForQuiz(quizId) {
            apiFetch(`/api/student/quizzes/${quizId}/certificate/download`)
                .then(async (r) => {
                    if (!r.ok) {
                        const text = await r.text();
                        throw new Error(text || 'Failed to download certificate');
                    }
                    return r.blob();
                })
                .then((blob) => {
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `certificate_quiz_${quizId}.pdf`;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    URL.revokeObjectURL(url);
                })
                .catch((e) => {
                    alert('Certificate not available yet. Make sure results are finalized.');
                    console.error(e);
                });
        }
        window.downloadCertificateForQuiz = downloadCertificateForQuiz;

        apiFetch('/api/student/certificates')
            .then(r => r.json())
            .then(data => {
                if (!data || data.status !== true) {
                    throw new Error(data?.message || 'Failed to load certificates');
                }

                const certs = Array.isArray(data.data) ? data.data : [];
                if (!certs.length) {
                    certEmpty.classList.remove('d-none');
                    return;
                }

                certTbody.innerHTML = certs.map(c => {
                    const title = c.quiz_title || `Quiz #${c.quiz_id}`;
                    const issued = c.issued_at ? new Date(c.issued_at).toLocaleString() : '--';
                    const rank = (typeof c.rank === 'number' && c.rank > 0) ? `#${c.rank}` : '--';

                    return `
                        <tr>
                            <td>
                                <div class="fw-semibold">${title}</div>
                                <div class="text-muted small">Quiz ID: ${c.quiz_id}</div>
                            </td>
                            <td><span class="badge text-bg-success">${rank}</span></td>
                            <td class="text-muted small">${issued}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-primary" onclick="downloadCertificateForQuiz(${c.quiz_id})">
                                    <i class="bi bi-download me-1"></i>Download
                                </button>
                            </td>
                        </tr>
                    `;
                }).join('');

                certCard.classList.remove('d-none');
            })
            .catch(e => {
                certErr.textContent = 'Could not load certificates. Please re-login and try again.';
                certErr.classList.remove('d-none');
                console.error(e);
            });
    });
</script>
@endsection
