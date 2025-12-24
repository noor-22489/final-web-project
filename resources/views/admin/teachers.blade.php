@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Manage Teachers</h2>
        <p class="text-muted mb-0">Add, update, deactivate, or delete teacher accounts.</p>
    </div>
    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#teacherCreateModal">Add New Teacher</button>
</div>

<div id="pageError" class="alert alert-warning d-none"></div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Qualification</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="teachersTbody">
                    <tr><td colspan="7" class="text-center text-muted">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Teacher Modal -->
<div class="modal fade" id="teacherCreateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add New Teacher</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="createMsg" class="alert d-none"></div>
        <form id="createTeacherForm">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Name</label>
              <input class="form-control" id="t_name" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" id="t_email" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Password</label>
              <input type="password" class="form-control" id="t_password" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Qualification</label>
              <input class="form-control" id="t_qualification">
            </div>
            <div class="col-md-6">
              <label class="form-label">Department</label>
              <input class="form-control" id="t_department">
            </div>
          </div>
          <div class="mt-3">
            <button class="btn btn-danger" type="submit">Create Teacher</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Edit Teacher Modal -->
<div class="modal fade" id="teacherEditModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Update Teacher</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="editMsg" class="alert d-none"></div>
        <form id="editTeacherForm">
          <input type="hidden" id="e_id">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Name</label>
              <input class="form-control" id="e_name" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" id="e_email" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">New Password (optional)</label>
              <input type="password" class="form-control" id="e_password">
            </div>
            <div class="col-md-6">
              <label class="form-label">Qualification</label>
              <input class="form-control" id="e_qualification">
            </div>
            <div class="col-md-6">
              <label class="form-label">Department</label>
              <input class="form-control" id="e_department">
            </div>
            <div class="col-md-6">
              <label class="form-label">Active</label>
              <select class="form-select" id="e_active">
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
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
  const tbody = document.getElementById('teachersTbody');

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

  function renderRow(t) {
    const status = t.is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>';
    return `
      <tr>
        <td>${t.id}</td>
        <td>${escapeHtml(t.name)}</td>
        <td>${escapeHtml(t.email)}</td>
        <td>${escapeHtml(t.qualification)}</td>
        <td>${escapeHtml(t.department)}</td>
        <td>${status}</td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-danger me-2" onclick='openEdit(${JSON.stringify(t)})'>Edit</button>
          <button class="btn btn-sm btn-outline-secondary me-2" onclick="deactivateTeacher(${t.id})">Deactivate</button>
          <button class="btn btn-sm btn-danger" onclick="deleteTeacher(${t.id})">Delete</button>
        </td>
      </tr>
    `;
  }

  function loadTeachers() {
    if (!ensureAdmin()) return;
    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Loading...</td></tr>';

    apiFetch('/api/admin/teachers')
      .then(r => r.json())
      .then(data => {
        if (!data || data.status !== true) throw new Error('Failed');
        if (!data.data.length) {
          tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No teachers found.</td></tr>';
          return;
        }
        tbody.innerHTML = data.data.map(renderRow).join('');
      })
      .catch(e => {
        pageError.textContent = 'Could not load teachers.';
        pageError.classList.remove('d-none');
        console.error(e);
      });
  }

  function openEdit(t) {
    const modal = new bootstrap.Modal(document.getElementById('teacherEditModal'));
    document.getElementById('e_id').value = t.id;
    document.getElementById('e_name').value = t.name || '';
    document.getElementById('e_email').value = t.email || '';
    document.getElementById('e_password').value = '';
    document.getElementById('e_qualification').value = t.qualification || '';
    document.getElementById('e_department').value = t.department || '';
    document.getElementById('e_active').value = t.is_active ? '1' : '0';
    document.getElementById('editMsg').classList.add('d-none');
    modal.show();
  }

  function deactivateTeacher(id) {
    if (!confirm('Deactivate this teacher? They will not be able to login.')) return;
    apiFetch(`/api/admin/teachers/${id}/deactivate`, { method: 'POST' })
      .then(r => r.json())
      .then(() => loadTeachers());
  }

  function deleteTeacher(id) {
    if (!confirm('Delete this teacher permanently?')) return;
    apiFetch(`/api/admin/teachers/${id}`, { method: 'DELETE' })
      .then(r => r.json())
      .then(() => loadTeachers());
  }

  document.getElementById('createTeacherForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const msg = document.getElementById('createMsg');
    msg.classList.add('d-none');

    apiFetch('/api/admin/teachers', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        name: document.getElementById('t_name').value,
        email: document.getElementById('t_email').value,
        password: document.getElementById('t_password').value,
        qualification: document.getElementById('t_qualification').value,
        department: document.getElementById('t_department').value,
      })
    })
    .then(r => r.json().then(b => ({ ok: r.ok, body: b })))
    .then(({ ok, body }) => {
      if (!ok || body.status !== true) {
        const err = body?.message || 'Failed to create teacher';
        setAlert(msg, 'danger', err);
        return;
      }
      setAlert(msg, 'success', 'Teacher created');
      loadTeachers();
    })
    .catch(e => {
      setAlert(msg, 'danger', 'Network/API error');
      console.error(e);
    });
  });

  document.getElementById('editTeacherForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const msg = document.getElementById('editMsg');
    msg.classList.add('d-none');

    const id = document.getElementById('e_id').value;

    apiFetch(`/api/admin/teachers/${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        name: document.getElementById('e_name').value,
        email: document.getElementById('e_email').value,
        password: document.getElementById('e_password').value || null,
        qualification: document.getElementById('e_qualification').value,
        department: document.getElementById('e_department').value,
        is_active: document.getElementById('e_active').value === '1',
      })
    })
    .then(r => r.json().then(b => ({ ok: r.ok, body: b })))
    .then(({ ok, body }) => {
      if (!ok || body.status !== true) {
        const err = body?.message || 'Failed to update teacher';
        setAlert(msg, 'danger', err);
        return;
      }
      setAlert(msg, 'success', 'Teacher updated');
      loadTeachers();
    })
    .catch(e => {
      setAlert(msg, 'danger', 'Network/API error');
      console.error(e);
    });
  });

  document.addEventListener('DOMContentLoaded', loadTeachers);
</script>
@endsection
