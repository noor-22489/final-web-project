@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Manage Courses</h2>
        <p class="text-muted mb-0">Create, update, assign teachers, or delete courses.</p>
    </div>
    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#courseCreateModal">Create Course</button>
</div>

<div id="pageError" class="alert alert-warning d-none"></div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Code</th>
                        <th>Credits</th>
                        <th>Semester</th>
                        <th>Teacher</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="coursesTbody">
                    <tr><td colspan="7" class="text-center text-muted">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Course Modal -->
<div class="modal fade" id="courseCreateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create Course</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="createMsg" class="alert d-none"></div>
        <form id="createCourseForm">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Title</label>
              <input class="form-control" id="c_title" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Code (e.g. CS101)</label>
              <input class="form-control" id="c_code">
            </div>
            <div class="col-md-4">
              <label class="form-label">Credit Hours</label>
              <input type="number" class="form-control" id="c_credits" min="0" max="30">
            </div>
            <div class="col-md-4">
              <label class="form-label">Semester</label>
              <input class="form-control" id="c_semester" placeholder="Fall 2025">
            </div>
            <div class="col-md-4">
              <label class="form-label">Teacher ID</label>
              <input type="number" class="form-control" id="c_teacher_id" placeholder="(optional)">
            </div>
            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea class="form-control" id="c_desc" rows="3"></textarea>
            </div>
          </div>
          <div class="mt-3">
            <button class="btn btn-danger" type="submit">Create</button>
          </div>
        </form>
        <div class="small text-muted mt-2">Note: Your DB currently requires a teacher. If you leave Teacher ID empty, the system will assign the first teacher automatically.</div>
      </div>
    </div>
  </div>
</div>

<!-- Edit Course Modal -->
<div class="modal fade" id="courseEditModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Update Course</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="editMsg" class="alert d-none"></div>
        <form id="editCourseForm">
          <input type="hidden" id="e_id">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Title</label>
              <input class="form-control" id="e_title" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Code</label>
              <input class="form-control" id="e_code">
            </div>
            <div class="col-md-4">
              <label class="form-label">Credit Hours</label>
              <input type="number" class="form-control" id="e_credits" min="0" max="30">
            </div>
            <div class="col-md-4">
              <label class="form-label">Semester</label>
              <input class="form-control" id="e_semester">
            </div>
            <div class="col-md-4">
              <label class="form-label">Teacher ID</label>
              <input type="number" class="form-control" id="e_teacher_id">
            </div>
            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea class="form-control" id="e_desc" rows="3"></textarea>
            </div>
          </div>
          <div class="mt-3">
            <button class="btn btn-danger" type="submit">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  const pageError = document.getElementById('pageError');
  const tbody = document.getElementById('coursesTbody');

  function ensureAdmin() {
    const user = getUserData();
    if (!getAuthToken() || !user) { window.location.href = '/login'; return false; }
    if (user.role !== 'admin') { window.location.href = user.role === 'teacher' ? '/teacher/dashboard' : '/student/dashboard'; return false; }
    return true;
  }

  function setAlert(el, type, msg) {
    el.textContent = msg;
    el.className = 'alert alert-' + type;
    el.classList.remove('d-none');
  }

  function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"]+/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s] || s));
  }

  function teacherLabel(c) {
    if (!c.teacher) return 'Unassigned';
    return `${escapeHtml(c.teacher.name)} (#${c.teacher.id})`;
  }

  function renderRow(c) {
    return `
      <tr>
        <td>${c.id}</td>
        <td>${escapeHtml(c.title)}</td>
        <td>${escapeHtml(c.code)}</td>
        <td>${escapeHtml(c.credit_hours)}</td>
        <td>${escapeHtml(c.semester)}</td>
        <td>${teacherLabel(c)}</td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-danger me-2" onclick='openEdit(${JSON.stringify(c)})'>Edit</button>
          <button class="btn btn-sm btn-danger" onclick="deleteCourse(${c.id})">Delete</button>
        </td>
      </tr>
    `;
  }

  function loadCourses() {
    if (!ensureAdmin()) return;
    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Loading...</td></tr>';

    apiFetch('/api/admin/courses')
      .then(r => r.json())
      .then(data => {
        if (!data || data.status !== true) throw new Error('Failed');
        if (!data.data.length) {
          tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No courses found.</td></tr>';
          return;
        }
        tbody.innerHTML = data.data.map(renderRow).join('');
      })
      .catch(e => {
        pageError.textContent = 'Could not load courses.';
        pageError.classList.remove('d-none');
        console.error(e);
      });
  }

  function openEdit(c) {
    const modal = new bootstrap.Modal(document.getElementById('courseEditModal'));
    document.getElementById('e_id').value = c.id;
    document.getElementById('e_title').value = c.title || '';
    document.getElementById('e_code').value = c.code || '';
    document.getElementById('e_credits').value = c.credit_hours ?? '';
    document.getElementById('e_semester').value = c.semester || '';
    document.getElementById('e_teacher_id').value = c.teacher_id || '';
    document.getElementById('e_desc').value = c.description || '';
    document.getElementById('editMsg').classList.add('d-none');
    modal.show();
  }

  function deleteCourse(id) {
    if (!confirm('Delete this course permanently?')) return;
    apiFetch(`/api/admin/courses/${id}`, { method: 'DELETE' })
      .then(r => r.json())
      .then(() => loadCourses());
  }

  document.getElementById('createCourseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const msg = document.getElementById('createMsg');
    msg.classList.add('d-none');

    apiFetch('/api/admin/courses', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        title: document.getElementById('c_title').value,
        code: document.getElementById('c_code').value,
        credit_hours: document.getElementById('c_credits').value ? Number(document.getElementById('c_credits').value) : null,
        semester: document.getElementById('c_semester').value,
        teacher_id: document.getElementById('c_teacher_id').value ? Number(document.getElementById('c_teacher_id').value) : null,
        description: document.getElementById('c_desc').value,
      })
    })
    .then(r => r.json().then(b => ({ ok: r.ok, body: b })))
    .then(({ ok, body }) => {
      if (!ok || body.status !== true) {
        const err = body?.message || 'Failed to create course';
        setAlert(msg, 'danger', err);
        return;
      }
      setAlert(msg, 'success', 'Course created');
      loadCourses();
    })
    .catch(e => {
      setAlert(msg, 'danger', 'Network/API error');
      console.error(e);
    });
  });

  document.getElementById('editCourseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const msg = document.getElementById('editMsg');
    msg.classList.add('d-none');

    const id = document.getElementById('e_id').value;

    apiFetch(`/api/admin/courses/${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        title: document.getElementById('e_title').value,
        code: document.getElementById('e_code').value,
        credit_hours: document.getElementById('e_credits').value ? Number(document.getElementById('e_credits').value) : null,
        semester: document.getElementById('e_semester').value,
        teacher_id: document.getElementById('e_teacher_id').value ? Number(document.getElementById('e_teacher_id').value) : null,
        description: document.getElementById('e_desc').value,
      })
    })
    .then(r => r.json().then(b => ({ ok: r.ok, body: b })))
    .then(({ ok, body }) => {
      if (!ok || body.status !== true) {
        const err = body?.message || 'Failed to update course';
        setAlert(msg, 'danger', err);
        return;
      }
      setAlert(msg, 'success', 'Course updated');
      loadCourses();
    })
    .catch(e => {
      setAlert(msg, 'danger', 'Network/API error');
      console.error(e);
    });
  });

  document.addEventListener('DOMContentLoaded', loadCourses);
</script>
@endsection
