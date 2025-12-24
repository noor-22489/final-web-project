$ErrorActionPreference = 'Stop'

$base = 'http://127.0.0.1:8001'
$ts = Get-Date -Format 'yyyyMMddHHmmss'
$teacherEmail = "teacher_$ts@example.com"
$studentEmail = "student_$ts@example.com"
$pw = 'password123'

Write-Host "[1/8] Creating users: $teacherEmail , $studentEmail"
Invoke-RestMethod -Method Post -Uri "$base/api/auth/register" -ContentType 'application/json' -Body (@{ name = "Teacher $ts"; email = $teacherEmail; password = $pw; role='teacher' } | ConvertTo-Json) | Out-Null
Invoke-RestMethod -Method Post -Uri "$base/api/auth/register" -ContentType 'application/json' -Body (@{ name = "Student $ts"; email = $studentEmail; password = $pw; role='student' } | ConvertTo-Json) | Out-Null

Write-Host "[2/8] Logging in"
$teacherLogin = Invoke-RestMethod -Method Post -Uri "$base/api/auth/login" -ContentType 'application/json' -Body (@{ email = $teacherEmail; password = $pw } | ConvertTo-Json)
$studentLogin = Invoke-RestMethod -Method Post -Uri "$base/api/auth/login" -ContentType 'application/json' -Body (@{ email = $studentEmail; password = $pw } | ConvertTo-Json)

$teacherToken = $teacherLogin.access_token
$studentToken = $studentLogin.access_token

if (-not $teacherToken) { throw 'Teacher login did not return access_token' }
if (-not $studentToken) { throw 'Student login did not return access_token' }

$teacherHeaders = @{ Authorization = "Bearer $teacherToken" }
$studentHeaders = @{ Authorization = "Bearer $studentToken" }

Write-Host "Teacher token: $($teacherToken.Substring(0,16))..."
Write-Host "Student token: $($studentToken.Substring(0,16))..."

Write-Host "[3/8] Creating course"
$courseRes = Invoke-RestMethod -Method Post -Uri "$base/api/teacher/courses" -Headers $teacherHeaders -ContentType 'application/json' -Body (@{ title = "Test Course $ts"; description = 'Smoke test course' } | ConvertTo-Json)
$courseId = $courseRes.data.id
if (-not $courseId) { throw 'Course creation failed (no id)' }
Write-Host "courseId=$courseId"

Write-Host "[4/8] Creating section"
$sectionRes = Invoke-RestMethod -Method Post -Uri "$base/api/teacher/courses/$courseId/sections" -Headers $teacherHeaders -ContentType 'application/json' -Body (@{ title = 'Section 1'; sort_order = 0 } | ConvertTo-Json)
$sectionId = $sectionRes.data.id
if (-not $sectionId) { throw 'Section creation failed (no id)' }
Write-Host "sectionId=$sectionId"

Write-Host "[5/8] Creating quiz (with questions)"
$quizPayload = @{
  title = "Quiz $ts"
  duration_minutes = 1
  negative_mark_per_wrong = 0
  max_attempts = 5
  questions = @(
    @{ question_text = '2 + 2 = ?'; options = @(
        @{ option_text='4'; is_correct=$true },
        @{ option_text='5'; is_correct=$false }
    )},
    @{ question_text = 'Capital of France?'; options = @(
        @{ option_text='Paris'; is_correct=$true },
        @{ option_text='London'; is_correct=$false }
    )}
  )
}
$quizRes = Invoke-RestMethod -Method Post -Uri "$base/api/teacher/sections/$sectionId/quizzes" -Headers $teacherHeaders -ContentType 'application/json' -Body ($quizPayload | ConvertTo-Json -Depth 10)
$quizId = $quizRes.data.id
if (-not $quizId) { throw 'Quiz creation failed (no id)' }
Write-Host "quizId=$quizId"

Write-Host "[6/8] Enrolling student"
Invoke-RestMethod -Method Post -Uri "$base/api/teacher/courses/$courseId/enrollments" -Headers $teacherHeaders -ContentType 'application/json' -Body (@{ student_email = $studentEmail } | ConvertTo-Json) | Out-Null

Write-Host "[7/8] Student submits attempt (with time_taken_seconds)"
$quizShow = Invoke-RestMethod -Method Get -Uri "$base/api/student/quizzes/$quizId" -Headers $studentHeaders
$answers = @()
foreach ($q in $quizShow.data.questions) {
  $firstOpt = $q.options[0]
  $answers += @{ question_id = [int]$q.id; option_id = [int]$firstOpt.id }
}
$submitPayload = @{ answers = $answers; time_taken_seconds = 12 }
$submitRes = Invoke-RestMethod -Method Post -Uri "$base/api/student/quizzes/$quizId/submit" -Headers $studentHeaders -ContentType 'application/json' -Body ($submitPayload | ConvertTo-Json -Depth 10)
$attemptId = $submitRes.attempt_id
Write-Host "attemptId=$attemptId; marks=$($submitRes.results.marks) time=12s"

Write-Host "[8/8] Teacher finalizes; student fetches leaderboard; downloads certificate"
Invoke-RestMethod -Method Post -Uri "$base/api/teacher/quizzes/$quizId/finalize" -Headers $teacherHeaders | Out-Null

$lb = Invoke-RestMethod -Method Get -Uri "$base/api/quizzes/$quizId/leaderboard" -Headers $studentHeaders
Write-Host "leaderboard_entries=$($lb.data.entries.Count) my_rank=$($lb.data.my_rank) my_cert_rank=$($lb.data.my_certificate.rank) finalized_at=$($lb.data.results_finalized_at)"

$tmpPdf = Join-Path $env:TEMP "cert_$ts.pdf"
$resp = Invoke-WebRequest -Method Get -Uri "$base/api/student/quizzes/$quizId/certificate/download" -Headers $studentHeaders -OutFile $tmpPdf
$size = (Get-Item $tmpPdf).Length
Write-Host "certificate_file=$tmpPdf size_bytes=$size content_type=$($resp.Headers['Content-Type'])"
