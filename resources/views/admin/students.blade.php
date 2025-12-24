@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Manage Students</h2>
        <p class="text-muted mb-0">Add, update, block, or delete student accounts.</p>
    </div>
    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#studentCreateModal">Add / Enroll Student</button>
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
                        <th>Roll #</th>
                        <th>Program</th>
                        <th>Batch</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="studentsTbody">
                    <tr><td colspan="8" class="text-center text-muted">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Student Modal -->
<div class="modal fade" id="studentCreateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add / Enroll Student</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="createMsg" class="alert d-none"></div>
        <form id="createStudentForm">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Name</label>
              <input class="form-control" id="s_name" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" id="s_email" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Password</label>
              <input type="password" class="form-control" id="s_password" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Roll Number</label>
              <input class="form-control" id="s_roll">
            </div>
            <div class="col-md-6">
              <label class="form-label">Program</label>
              <input class="form-control" id="s_program" placeholder="BS/BCS/MS">
            </div>
            <div class="col-md-6">
              <label class="form-label">Batch</label>
              <input class="form-control" id="s_batch" placeholder="2025">
            </div>
          </div>
          <div class="mt-3">
            <button class="btn btn-danger" type="submit">Create Student</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Edit Student Modal -->
<div class="modal fade" id="studentEditModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Update Student</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="editMsg" class="alert d-none"></div>
        <form id="editStudentForm">
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
              <label class="form-label">Roll Number</label>
              <input class="form-control" id="e_roll">
            </div>
            <div class="col-md-6">
              <label class="form-label">Program</label>
              <input class="form-control" id="e_program">
            </div>
            <div class="col-md-6">
              <label class="form-label">Batch</label>
              <input class="form-control" id="e_batch">
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
  const tbody = document.getElementById('studentsTbody');

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

  function renderRow(s) {
    const status = s.is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>';
    return `
      <tr>
        <td>${s.id}</td>
        <td>${escapeHtml(s.name)}</td>
        <td>${escapeHtml(s.email)}</td>
        <td>${escapeHtml(s.roll_number)}</td>
        <td>${escapeHtml(s.program)}</td>
        <td>${escapeHtml(s.batch)}</td>
        <td>${status}</td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-danger me-2" onclick='openEdit(${JSON.stringify(s)})'>Edit</button>
          <button class="btn btn-sm btn-outline-secondary me-2" onclick="deactivateStudent(${s.id})">Block</button>
          <button class="btn btn-sm btn-danger" onclick="deleteStudent(${s.id})">Delete</button>
        </td>
      </tr>
    `;
  }

  function loadStudents() {
    if (!ensureAdmin()) return;
    tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Loading...</td></tr>';

    apiFetch('/api/admin/students')
      .then(r => r.json())
      .then(data => {
        if (!data || data.status !== true) throw new Error('Failed');
        if (!data.data.length) {
          tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No students found.</td></tr>';
          return;
        }
        tbody.innerHTML = data.data.map(renderRow).join('');
      })
      .catch(e => {
        pageError.textContent = 'Could not load students.';
        pageError.classList.remove('d-none');
        console.error(e);
      });
  }

  function openEdit(s) {
    const modal = new bootstrap.Modal(document.getElementById('studentEditModal'));
    document.getElementById('e_id').value = s.id;
    document.getElementById('e_name').value = s.name || '';
    document.getElementById('e_email').value = s.email || '';
    document.getElementById('e_password').value = '';
    document.getElementById('e_roll').value = s.roll_number || '';
    document.getElementById('e_program').value = s.program || '';
    document.getElementById('e_batch').value = s.batch || '';
    document.getElementById('e_active').value = s.is_active ? '1' : '0';
    document.getElementById('editMsg').classList.add('d-none');
    modal.show();
  }

  function deactivateStudent(id) {
    if (!confirm('Block this student? They will not be able to login.')) return;
    apiFetch(`/api/admin/students/${id}/deactivate`, { method: 'POST' })
      .then(r => r.json())
      .then(() => loadStudents());
  }

  function deleteStudent(id) {
    if (!confirm('Delete this student permanently?')) return;
    apiFetch(`/api/admin/students/${id}`, { method: 'DELETE' })
      .then(r => r.json())
      .then(() => loadStudents());
  }

  document.getElementById('createStudentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const msg = document.getElementById('createMsg');
    msg.classList.add('d-none');

    apiFetch('/api/admin/students', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        name: document.getElementById('s_name').value,
        email: document.getElementById('s_email').value,
        password: document.getElementById('s_password').value,
        roll_number: document.getElementById('s_roll').value,
        program: document.getElementById('s_program').value,
        batch: document.getElementById('s_batch').value,
      })
    })
    .then(r => r.json().then(b => ({ ok: r.ok, body: b })))
    .then(({ ok, body }) => {
      if (!ok || body.status !== true) {
        const err = body?.message || 'Failed to create student';
        setAlert(msg, 'danger', err);
        return;
      }
      setAlert(msg, 'success', 'Student created');
      loadStudents();
    })
    .catch(e => {
      setAlert(msg, 'danger', 'Network/API error');
      console.error(e);
    });
  });

  document.getElementById('editStudentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const msg = document.getElementById('editMsg');
    msg.classList.add('d-none');

    const id = document.getElementById('e_id').value;

    apiFetch(`/api/admin/students/${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        name: document.getElementById('e_name').value,
        email: document.getElementById('e_email').value,
        password: document.getElementById('e_password').value || null,
        roll_number: document.getElementById('e_roll').value,
        program: document.getElementById('e_program').value,
        batch: document.getElementById('e_batch').value,
        is_active: document.getElementById('e_active').value === '1',
      })
    })
    .then(r => r.json().then(b => ({ ok: r.ok, body: b })))
    .then(({ ok, body }) => {
      if (!ok || body.status !== true) {
        const err = body?.message || 'Failed to update student';
        setAlert(msg, 'danger', err);
        return;
      }
      setAlert(msg, 'success', 'Student updated');
      loadStudents();
    })
    .catch(e => {
      setAlert(msg, 'danger', 'Network/API error');
      console.error(e);
    });
  });

  document.addEventListener('DOMContentLoaded', loadStudents);
</script>
@endsection
