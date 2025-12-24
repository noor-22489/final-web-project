@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <h2 class="mb-4 border-bottom pb-2">
            Manage Questions for Quiz: <span id="quizTitle">Loading Quiz...</span>
        </h2>
        <input type="hidden" id="quizId" value="{{ $quizId }}">

        <div class="d-flex justify-content-between mb-4">
            <a href="{{ route('teacher.my_courses') }}" class="btn btn-outline-secondary">← Back to Courses</a>
            <div id="quizDetails" class="alert alert-info py-2 px-3"></div>
        </div>
        
        <ul class="nav nav-tabs mb-4" id="questionTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="add-tab" data-bs-toggle="tab" data-bs-target="#addQuestion" type="button" role="tab">Add New Question</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="list-tab" data-bs-toggle="tab" data-bs-target="#listQuestions" type="button" role="tab">View/Edit Questions</button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="addQuestion" role="tabpanel" aria-labelledby="add-tab">
                <div class="card shadow-lg mb-4">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">Create Multiple Choice Question</h4>
                    </div>
                    <div class="card-body p-4">
                        <form id="createQuestionForm">
                            <div class="mb-3">
                                <label for="questionText" class="form-label">Question Text</label>
                                <textarea class="form-control" id="questionText" rows="3" required placeholder="e.g., What does CSS stand for?"></textarea>
                            </div>
                            
                            <h5 class="mt-4">Options (Max 4)</h5>
                            <div id="optionsContainer">
                                <div class="input-group mb-2">
                                    <span class="input-group-text">Option 1</span>
                                    <input type="text" class="form-control option-text" required placeholder="Option A">
                                    <div class="input-group-text">
                                        <input class="form-check-input correct-option-radio" type="radio" name="correct_option" value="0" required>
                                        <label class="form-check-label ms-1">Correct</label>
                                    </div>
                                </div>
                                <div class="input-group mb-2">
                                    <span class="input-group-text">Option 2</span>
                                    <input type="text" class="form-control option-text" required placeholder="Option B">
                                    <div class="input-group-text">
                                        <input class="form-check-input correct-option-radio" type="radio" name="correct_option" value="1" required>
                                        <label class="form-check-label ms-1">Correct</label>
                                    </div>
                                </div>
                                <div class="input-group mb-2">
                                    <span class="input-group-text">Option 3</span>
                                    <input type="text" class="form-control option-text" placeholder="Option C (Optional)">
                                    <div class="input-group-text">
                                        <input class="form-check-input correct-option-radio" type="radio" name="correct_option" value="2">
                                        <label class="form-check-label ms-1">Correct</label>
                                    </div>
                                </div>
                                <div class="input-group mb-2">
                                    <span class="input-group-text">Option 4</span>
                                    <input type="text" class="form-control option-text" placeholder="Option D (Optional)">
                                    <div class="input-group-text">
                                        <input class="form-check-input correct-option-radio" type="radio" name="correct_option" value="3">
                                        <label class="form-check-label ms-1">Correct</label>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-success w-100 mt-4">Add Question to Quiz</button>
                        </form>
                        <div id="questionMessage" class="mt-3 alert d-none" role="alert"></div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="listQuestions" role="tabpanel" aria-labelledby="list-tab">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="text-muted">Edit questions and options (max 4).</div>
                    <button class="btn btn-outline-primary btn-sm" type="button" id="refreshQuestionsBtn">Refresh</button>
                </div>
                <div id="questionsList"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const QUIZ_ID = document.getElementById('quizId').value;
    const questionMessageBox = document.getElementById('questionMessage');
    const questionsListEl = document.getElementById('questionsList');

    // --- UTILITY: FETCH QUIZ DETAILS ---
    // We need this to display the quiz title on the page
    function fetchQuizDetails() {
        // API Endpoint: /api/quizzes/{id} (Public route, or your friend needs to expose it)
        apiFetch(`/api/quizzes/${QUIZ_ID}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === true && data.data) {
                    const quiz = data.data;
                    document.getElementById('quizTitle').textContent = quiz.title;
                    document.getElementById('quizDetails').innerHTML = `
                        **Quiz:** ${quiz.title} | 
                        **Duration:** ${(quiz.duration_minutes ?? '')} min
                    `;
                    // Optional: Call function to list existing questions here if implemented
                    // fetchQuestions(); 
                } else {
                    document.getElementById('quizTitle').textContent = "Quiz Not Found";
                }
            })
            .catch(error => {
                console.error('Error fetching quiz details:', error);
                document.getElementById('quizTitle').textContent = "Error Loading Quiz";
            });
    }

    function escapeHtml(str) {
        return String(str ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function renderQuestions(quiz) {
        const questions = quiz?.questions || [];
        if (!questions.length) {
            questionsListEl.innerHTML = `<div class="alert alert-info">No questions added yet.</div>`;
            return;
        }

        questionsListEl.innerHTML = questions.map((q, qIndex) => {
            const options = (q.options || []).slice(0, 4);
            const optionInputs = [];
            for (let i = 0; i < 4; i++) {
                const opt = options[i];
                const text = opt?.option_text ?? '';
                const isCorrect = !!opt?.is_correct;
                optionInputs.push(`
                    <div class="input-group mb-2">
                        <span class="input-group-text">Option ${i + 1}</span>
                        <input type="text" class="form-control" data-opt-index="${i}" value="${escapeHtml(text)}" placeholder="Option ${String.fromCharCode(65 + i)}${i >= 2 ? ' (Optional)' : ''}">
                        <div class="input-group-text">
                            <input class="form-check-input" type="radio" name="correct_${q.id}" value="${i}" ${isCorrect ? 'checked' : ''}>
                            <label class="form-check-label ms-1">Correct</label>
                        </div>
                    </div>
                `);
            }

            return `
                <div class="card shadow-sm mb-3" data-question-id="${q.id}">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div><strong>Q${qIndex + 1}</strong></div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary btn-sm" type="button" data-action="save">Save</button>
                            <button class="btn btn-outline-danger btn-sm" type="button" data-action="delete">Delete</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Question Text</label>
                            <textarea class="form-control" rows="2" data-field="question_text">${escapeHtml(q.question_text)}</textarea>
                        </div>
                        <div>
                            <label class="form-label">Options</label>
                            ${optionInputs.join('')}
                        </div>
                        <div class="alert d-none mt-3" role="alert" data-field="msg"></div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function fetchQuestions() {
        if (!questionsListEl) return;
        questionsListEl.innerHTML = `<div class="alert alert-secondary">Loading questions...</div>`;
        apiFetch(`/api/teacher/quizzes/${QUIZ_ID}/preview`)
            .then(r => r.json())
            .then(data => {
                if (data.status === true && data.data) {
                    renderQuestions(data.data);
                } else {
                    questionsListEl.innerHTML = `<div class="alert alert-danger">Failed to load questions.</div>`;
                }
            })
            .catch(() => {
                questionsListEl.innerHTML = `<div class="alert alert-danger">Failed to load questions.</div>`;
            });
    }

    function showCardMessage(card, type, message) {
        const box = card.querySelector('[data-field="msg"]');
        if (!box) return;
        box.className = `alert mt-3`;
        box.classList.add(type === 'success' ? 'alert-success' : 'alert-danger');
        box.textContent = message;
        box.classList.remove('d-none');
    }

    function collectQuestionPayload(card) {
        const question_text = card.querySelector('[data-field="question_text"]')?.value?.trim();
        const radios = Array.from(card.querySelectorAll(`input[type="radio"][name^="correct_"]`));
        const checked = radios.find(r => r.checked);
        const correctIndex = checked ? parseInt(checked.value, 10) : -1;

        const optionInputs = Array.from(card.querySelectorAll('input[type="text"][data-opt-index]'));
        const options = optionInputs
            .map((input, idx) => ({ idx, text: input.value.trim() }))
            .filter(o => !!o.text)
            .map(o => ({
                option_text: o.text,
                is_correct: o.idx === correctIndex,
            }));

        return { question_text, options };
    }

    // --- HANDLE QUESTION CREATION ---
    document.getElementById('createQuestionForm').addEventListener('submit', function(e) {
        e.preventDefault();
        questionMessageBox.classList.add('d-none');
        
        const question_text = document.getElementById('questionText').value;
        const optionInputs = document.querySelectorAll('.option-text');
        const correctRadioInputs = document.querySelectorAll('.correct-option-radio');

        let options = [];
        let correct_option_index = -1;

        // 1. Gather Options and identify the Correct Index
        optionInputs.forEach((input, index) => {
            const optionText = input.value.trim();
            if (optionText) {
                options.push({ option_text: optionText });
                
                // Check if the corresponding radio button is checked
                if (correctRadioInputs[index].checked) {
                    correct_option_index = index;
                }
            }
        });

        // 2. Validate
        if (options.length < 2) {
            questionMessageBox.textContent = 'Please provide at least two options.';
            questionMessageBox.className = 'mt-3 alert alert-danger';
            questionMessageBox.classList.remove('d-none');
            return;
        }

        if (correct_option_index === -1) {
            questionMessageBox.textContent = 'Please select one correct option.';
            questionMessageBox.className = 'mt-3 alert alert-danger';
            questionMessageBox.classList.remove('d-none');
            return;
        }

        // 3. Prepare Payload
        const payload = {
            quiz_id: QUIZ_ID,
            question_text: question_text,
            type: 'multiple_choice', // Assuming all questions are MC for now
            options: options.map((opt, index) => ({
                option_text: opt.option_text,
                is_correct: index === correct_option_index // Set the correct option flag
            }))
        };

        // 4. API Call
        // API Endpoint: Your friend needs to define this. A common one is:
        // POST /api/teacher/quizzes/{id}/questions
        apiFetch(`/api/teacher/quizzes/${QUIZ_ID}/questions`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(response => response.json().then(data => ({ status: response.status, body: data })))
        .then(({ status, body }) => {
            if (status === 201 && body.status === true) {
                questionMessageBox.textContent = 'Question added successfully!';
                questionMessageBox.className = 'mt-3 alert alert-success';
                questionMessageBox.classList.remove('d-none');
                document.getElementById('createQuestionForm').reset();
                fetchQuestions();
            } else {
                let errorMessage = body.message || 'Question creation failed.';
                // If Laravel returns validation errors
                if (body.errors) {
                     errorMessage += ' (' + Object.values(body.errors).flat().join('; ') + ')';
                }
                questionMessageBox.textContent = errorMessage;
                questionMessageBox.className = 'mt-3 alert alert-danger';
                questionMessageBox.classList.remove('d-none');
            }
        });
    });

    // List tab: fetch on first open
    const listTabBtn = document.getElementById('list-tab');
    if (listTabBtn) {
        listTabBtn.addEventListener('shown.bs.tab', fetchQuestions);
    }

    // Refresh button
    const refreshBtn = document.getElementById('refreshQuestionsBtn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', fetchQuestions);
    }

    // Delegate save/delete actions
    if (questionsListEl) {
        questionsListEl.addEventListener('click', function (e) {
            const btn = e.target.closest('button[data-action]');
            if (!btn) return;

            const card = btn.closest('[data-question-id]');
            if (!card) return;

            const questionId = card.getAttribute('data-question-id');
            const action = btn.getAttribute('data-action');

            if (action === 'delete') {
                if (!confirm('Delete this question?')) return;
                apiFetch(`/api/teacher/questions/${questionId}`, { method: 'DELETE' })
                    .then(r => r.json().then(data => ({ status: r.status, body: data })))
                    .then(({ status, body }) => {
                        if (status === 200 && body.status === true) {
                            fetchQuestions();
                        } else {
                            showCardMessage(card, 'error', body.message || 'Delete failed');
                        }
                    })
                    .catch(() => showCardMessage(card, 'error', 'Delete failed'));
                return;
            }

            if (action === 'save') {
                const payload = collectQuestionPayload(card);

                if (!payload.question_text) {
                    showCardMessage(card, 'error', 'Question text is required');
                    return;
                }
                if (!payload.options || payload.options.length < 2) {
                    showCardMessage(card, 'error', 'Please provide at least two options');
                    return;
                }
                const correctCount = payload.options.filter(o => o.is_correct).length;
                if (correctCount !== 1) {
                    showCardMessage(card, 'error', 'Select exactly one correct option');
                    return;
                }

                apiFetch(`/api/teacher/questions/${questionId}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                })
                .then(r => r.json().then(data => ({ status: r.status, body: data })))
                .then(({ status, body }) => {
                    if (status === 200 && body.status === true) {
                        showCardMessage(card, 'success', 'Saved');
                        fetchQuestions();
                    } else {
                        let errorMessage = body.message || 'Save failed.';
                        if (body.errors) {
                            errorMessage += ' (' + Object.values(body.errors).flat().join('; ') + ')';
                        }
                        showCardMessage(card, 'error', errorMessage);
                    }
                })
                .catch(() => showCardMessage(card, 'error', 'Save failed'));
            }
        });
    }
    
    // Initial load
    document.addEventListener('DOMContentLoaded', fetchQuizDetails);
</script>
@endsection