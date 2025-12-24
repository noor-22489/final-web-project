@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="mb-0 border-bottom pb-2 text-primary" id="courseTitle">Loading Course...</h2>
      <a href="{{ route('student.my_courses') }}" class="btn btn-outline-secondary">← Back</a>
    </div>

    <div id="courseMeta" class="alert alert-info py-2 px-3 d-none"></div>

    <ul class="nav nav-tabs mb-3" id="courseTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabContent" type="button" role="tab">Course Content</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabQuizzes" type="button" role="tab">Quizzes</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabAssignments" type="button" role="tab">Assignments</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabAnnouncements" type="button" role="tab">Announcements</button>
      </li>
    </ul>

    <div class="tab-content">
      <div class="tab-pane fade show active" id="tabContent" role="tabpanel">
        <div id="sectionsContent"></div>
      </div>
      <div class="tab-pane fade" id="tabQuizzes" role="tabpanel">
        <div id="quizzesContent"></div>
      </div>
      <div class="tab-pane fade" id="tabAssignments" role="tabpanel">
        <div id="assignmentsContent"></div>
      </div>
      <div class="tab-pane fade" id="tabAnnouncements" role="tabpanel">
        <div id="announcementsContent"></div>
      </div>
    </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
  const COURSE_ID = {{ (int)$id }};

  function escapeHtml(str) {
    return String(str ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function formatDate(dt) {
    if (!dt) return '';
    try { return new Date(dt).toLocaleString(); } catch { return dt; }
  }

  function renderSections(sections, teacherName) {
    const el = document.getElementById('sectionsContent');
    if (!sections || !sections.length) {
      el.innerHTML = `<div class="alert alert-secondary">No sections/materials yet.</div>`;
      return;
    }

    el.innerHTML = sections.map((s) => {
      const materials = (s.materials || []);
      const items = materials.length ? materials.map(m => {
        const uploadedAt = formatDate(m.created_at);
        const typeBadge = `<span class="badge bg-info text-dark text-uppercase">${escapeHtml(m.type)}</span>`;
        let action = '';

        if (m.file_path) {
          action = `<a class="btn btn-sm btn-outline-primary" href="${m.file_path}" target="_blank" download>Download</a>`;
        } else if (m.type === 'link' && m.content) {
          action = `<a class="btn btn-sm btn-outline-primary" href="${m.content}" target="_blank">Open Link</a>`;
        } else if (m.type === 'video' && m.content) {
          action = `<a class="btn btn-sm btn-outline-primary" href="${m.content}" target="_blank">Watch</a>`;
        } else {
          action = `<span class="text-muted small">No file</span>`;
        }

        return `
          <div class="border rounded p-3 mb-2">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <div class="d-flex gap-2 align-items-center">
                  <strong>${escapeHtml(m.title)}</strong>
                  ${typeBadge}
                </div>
                ${m.description ? `<div class="text-muted small mt-1">${escapeHtml(m.description)}</div>` : ''}
                <div class="text-muted small mt-1">Uploaded: ${uploadedAt} | Teacher: ${escapeHtml(teacherName)}</div>
              </div>
              <div>${action}</div>
            </div>
          </div>
        `;
      }).join('') : `<div class="text-muted">No materials in this section.</div>`;

      return `
        <div class="card mb-3 shadow-sm">
          <div class="card-header"><strong>${escapeHtml(s.title)}</strong></div>
          <div class="card-body">${items}</div>
        </div>
      `;
    }).join('');
  }

  function renderQuizzes(sections) {
    const el = document.getElementById('quizzesContent');
    const quizzes = (sections || []).flatMap(s => (s.quizzes || []).map(q => ({ section: s, quiz: q })));
    if (!quizzes.length) {
      el.innerHTML = `<div class="alert alert-secondary">No quizzes available yet.</div>`;
      return;
    }

    el.innerHTML = quizzes.map(({ section, quiz }) => {
      const deadline = quiz.deadline_at ? formatDate(quiz.deadline_at) : 'No deadline';
      const duration = quiz.duration_minutes ? `${quiz.duration_minutes} min` : 'No time limit';
      const attempts = quiz.my_attempts_count ?? 0;
      const marks = quiz.my_last_attempt?.marks ?? quiz.my_last_attempt?.score ?? null;
      const marksText = marks === null ? '' : `<span class="badge bg-success">Last Marks: ${marks}</span>`;

      return `
        <div class="card mb-3 shadow-sm">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <h5 class="mb-1">${escapeHtml(quiz.title)}</h5>
                <div class="text-muted small">Section: ${escapeHtml(section.title)}</div>
                <div class="text-muted small">Duration: ${escapeHtml(duration)} | Deadline: ${escapeHtml(deadline)}</div>
                <div class="text-muted small">Attempts: ${attempts}${quiz.max_attempts ? ` / ${quiz.max_attempts}` : ''}</div>
                <div class="mt-2">${marksText}</div>
              </div>
              <div>
                <div class="d-flex flex-column gap-2">
                  <a class="btn btn-primary btn-sm" href="/student/quiz/${quiz.id}/attempt?courseId=${COURSE_ID}">Attempt Quiz</a>
                  <a class="btn btn-outline-secondary btn-sm" href="/student/quiz/${quiz.id}/leaderboard?courseId=${COURSE_ID}">Leaderboard</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      `;
    }).join('');
  }

  function renderAssignments(sections) {
    const el = document.getElementById('assignmentsContent');
    const assignments = (sections || []).flatMap(s => (s.assignments || []).map(a => ({ section: s, assignment: a })));
    if (!assignments.length) {
      el.innerHTML = `<div class="alert alert-secondary">No assignments yet.</div>`;
      return;
    }

    el.innerHTML = assignments.map(({ section, assignment }) => {
      const due = assignment.due_at ? formatDate(assignment.due_at) : 'No deadline';
      const submission = assignment.my_submission;
      
      // Dynamic badges based on submission status
      const status = submission
        ? (submission.is_late ? '<span class="badge bg-warning text-dark">Submitted (Late)</span>' : '<span class="badge bg-success">Submitted</span>')
        : '<span class="badge bg-secondary">Pending</span>';
      
      const marks = submission?.marks !== null && submission?.marks !== undefined ? `<span class="badge bg-info text-dark">Marks: ${submission.marks}</span>` : '';
      const feedback = submission?.feedback ? `<div class="text-muted small mt-1"><strong>Feedback:</strong> ${escapeHtml(submission.feedback)}</div>` : '';
      const download = submission?.file_path ? `<a class="btn btn-sm btn-outline-secondary" href="${submission.file_path}" target="_blank" download>My File</a>` : '';

      return `
        <div class="card mb-3 shadow-sm" data-assignment-id="${assignment.id}">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <h5 class="mb-1">${escapeHtml(assignment.title)}</h5>
                <div class="text-muted small">Section: ${escapeHtml(section.title)} | Due: ${escapeHtml(due)}</div>
                ${assignment.description ? `<div class="mt-2">${escapeHtml(assignment.description)}</div>` : ''}
                <div class="mt-2 d-flex gap-2 align-items-center">${status} ${marks}</div>
                ${feedback}
              </div>
              <div class="text-end">
                <input type="file" class="form-control form-control-sm mb-2" data-field="file">
                <button class="btn btn-sm btn-primary" type="button" data-action="submit">Submit</button>
                <div class="mt-2">${download}</div>
              </div>
            </div>
            <div class="alert d-none mt-3" role="alert" data-field="msg"></div>
          </div>
        </div>
      `;
    }).join('');

    // Shared event listener for all submit buttons
    el.addEventListener('click', function (e) {
      const btn = e.target.closest('button[data-action="submit"]');
      if (!btn) return;
      
      const card = btn.closest('[data-assignment-id]');
      const assignmentId = card.getAttribute('data-assignment-id');
      const fileInput = card.querySelector('input[type="file"][data-field="file"]');
      const msg = card.querySelector('[data-field="msg"]');

      if (!fileInput.files || !fileInput.files[0]) {
        msg.className = 'alert alert-danger mt-3';
        msg.textContent = 'Please choose a file.';
        msg.classList.remove('d-none');
        return;
      }

      // UI state for upload
      btn.disabled = true;
      btn.textContent = 'Uploading...';

      const formData = new FormData();
      formData.append('file', fileInput.files[0]);

      apiFetch(`/api/student/assignments/${assignmentId}/submit`, {
        method: 'POST',
        body: formData
      })
      .then(r => r.json().then(data => ({ status: r.status, body: data })))
      .then(({ status, body }) => {
        btn.disabled = false;
        btn.textContent = 'Submit';

        if ((status === 201 || status === 200) && body.status === true) {
          // Requirement: Show late warning if applicable
          msg.className = body.is_late ? 'alert alert-warning mt-3' : 'alert alert-success mt-3';
          msg.textContent = body.message;
          msg.classList.remove('d-none');
          
          // Refresh course overview to update the "My File" link and badges
          setTimeout(() => { loadOverview(); }, 1500);
        } else {
          msg.className = 'alert alert-danger mt-3';
          msg.textContent = body.message || 'Submission failed';
          msg.classList.remove('d-none');
        }
      })
      .catch(() => {
        btn.disabled = false;
        btn.textContent = 'Submit';
        msg.className = 'alert alert-danger mt-3';
        msg.textContent = 'Network error. Please try again.';
        msg.classList.remove('d-none');
      });
    }); 
  }

  function renderAnnouncements(course) {
    const el = document.getElementById('announcementsContent');
    const announcements = course.announcements || [];
    if (!announcements.length) {
      el.innerHTML = `<div class="alert alert-secondary">No announcements yet.</div>`;
      return;
    }

    el.innerHTML = announcements.map(a => {
      return `
        <div class="card mb-3 shadow-sm">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h5 class="mb-1">${escapeHtml(a.title)}</h5>
                <div class="text-muted small">By ${escapeHtml(a.creator?.name || '')} • ${formatDate(a.created_at)}</div>
              </div>
            </div>
            <div class="mt-2">${escapeHtml(a.body)}</div>
          </div>
        </div>
      `;
    }).join('');
  }

  function loadOverview() {
    apiFetch(`/api/student/courses/${COURSE_ID}/overview`)
      .then(r => r.json())
      .then(data => {
        if (!data.status || !data.data?.course) {
          document.getElementById('courseTitle').textContent = 'Course Not Found';
          return;
        }

        const course = data.data.course;
        document.getElementById('courseTitle').textContent = course.title;

        const teacherName = course.teacher?.name || '';
        const code = course.code || '';
        const ch = (course.credit_hours ?? '') !== '' ? `${course.credit_hours} credit hours` : '';
        const sem = course.semester || '';
        const metaParts = [
          teacherName ? `Teacher: ${escapeHtml(teacherName)}` : null,
          code ? `Code: ${escapeHtml(code)}` : null,
          ch ? escapeHtml(ch) : null,
          sem ? `Semester: ${escapeHtml(sem)}` : null,
        ].filter(Boolean);

        const meta = document.getElementById('courseMeta');
        meta.innerHTML = metaParts.join(' | ');
        meta.classList.remove('d-none');

        renderSections(course.sections || [], teacherName);
        renderQuizzes(course.sections || []);
        renderAssignments(course.sections || []);
        renderAnnouncements(course);
      })
      .catch(() => {
        document.getElementById('courseTitle').textContent = 'Error Loading Course';
      });
  }

  document.addEventListener('DOMContentLoaded', function () {
    const user = getUserData();
    if (!getAuthToken() || !user) {
      window.location.href = '/login';
      return;
    }
    if (user.role !== 'student') {
      window.location.href = user.role === 'admin' ? '/admin/dashboard' : '/teacher/dashboard';
      return;
    }
    loadOverview();
  });
</script>
@endsection