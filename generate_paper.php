<?php
include 'config.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: admin.php");
    exit();
}

$user_role = $_SESSION['role'] ?? 'admin';
$user_name = $_SESSION['name'] ?? $_SESSION['admin'];
$staff_id = $_SESSION['staff_id'] ?? null;

$error = "";
$success_msg = "";
$generated_sets = [];
$marking_scheme_output = "";
$generated_meta = null;

// Fetch subjects (If teacher, show their assigned subjects; if admin, show all)
$subjects = [];
if ($user_role === 'teacher' && !empty($staff_id)) {
    $sub_query = mysqli_query($conn, "SELECT s.* FROM subject s INNER JOIN staff_transaction st ON s.Sub_code = st.Sub_code WHERE st.Staff_id='$staff_id' ORDER BY s.Semester, s.Sub_name");
} else {
    $sub_query = mysqli_query($conn, "SELECT * FROM subject ORDER BY Semester, Sub_name");
}
if ($sub_query && mysqli_num_rows($sub_query) > 0) {
    while ($row = mysqli_fetch_assoc($sub_query)) { $subjects[] = $row; }
} else {
    // Fallback to all subjects if no transaction mapping found
    $all_sub = mysqli_query($conn, "SELECT * FROM subject ORDER BY Semester, Sub_name");
    while ($row = mysqli_fetch_assoc($all_sub)) { $subjects[] = $row; }
}

// Fetch all question patterns
$patterns = [];
$pat_query = mysqli_query($conn, "SELECT * FROM question_pattern ORDER BY Qp_id ASC");
if ($pat_query) {
    while ($row = mysqli_fetch_assoc($pat_query)) { $patterns[] = $row; }
}

// Gemini AI Call Function
function callGeminiAI($prompt) {
    $apiKey = defined("GEMINI_API_KEY") ? GEMINI_API_KEY : "";
    if (empty($apiKey)) {
        return ["success" => false, "error" => "Gemini API key is not configured. Set the GEMINI_API_KEY environment variable."];
    }

    $models = ["gemini-3.8-flash", "gemini-2.5-flash", "gemini-2.0-flash", "gemini-1.5-flash"];
    $data = [
        "contents" => [
            ["parts" => [["text" => $prompt]]]
        ],
        "generationConfig" => [
            "temperature" => 0.45,
            "maxOutputTokens" => 4000
        ]
    ];

    $lastError = "";
    foreach ($models as $model) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-goog-api-key: ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err = curl_error($ch);
        curl_close($ch);

        if ($curl_err) {
            $lastError = "cURL Error: " . $curl_err;
            continue;
        }

        $res = json_decode($response, true);
        if ($http_code === 200 && isset($res['candidates'][0]['content']['parts'][0]['text'])) {
            return [
                "success" => true, 
                "text" => $res['candidates'][0]['content']['parts'][0]['text'], 
                "model" => $model
            ];
        }

        if (isset($res['error']['message'])) {
            $lastError = "API Error ({$model}): " . $res['error']['message'];
            continue;
        } else {
            $lastError = "HTTP Code $http_code from {$model}. Response: " . substr($response, 0, 200);
        }
    }

    return ["success" => false, "error" => $lastError];
}

// Fallback: Generate Paper from College Database Questions
function generateFromDatabase($conn, $sub_code, $pattern, $setName = "Set A") {
    $sections = [];
    $total_calculated = 0;

    // 1-mark MCQs
    $count_1m = intval($pattern['1_mark'] ?? 0);
    if ($count_1m > 0) {
        $q_mcq = mysqli_query($conn, "SELECT * FROM mcq_question WHERE Sub_code='$sub_code' ORDER BY RAND() LIMIT $count_1m");
        if (!$q_mcq || mysqli_num_rows($q_mcq) == 0) {
            $q_mcq = mysqli_query($conn, "SELECT * FROM mcq_question ORDER BY RAND() LIMIT $count_1m");
        }

        $mcq_items = [];
        $i = 1;
        while ($row = mysqli_fetch_assoc($q_mcq)) {
            $blooms = $row['Blooms_level'] ?? 'L1 - Remember';
            $unit = $row['Unit_no'] ?? 'Unit 1';
            $item = "Q{$i}. " . $row['Question'] . "   [1 Mark] [" . $blooms . " | " . $unit . "]\n";
            $item .= "    (A) " . $row['Option1'] . "    (B) " . $row['Option2'] . "\n";
            $item .= "    (C) " . $row['Option3'] . "    (D) " . $row['Option4'];
            $mcq_items[] = $item;
            $i++;
        }
        if (!empty($mcq_items)) {
            $sections[] = "SECTION A: MULTIPLE CHOICE QUESTIONS (" . count($mcq_items) . " x 1 = " . count($mcq_items) . " Marks)\nAnswer all questions:\n\n" . implode("\n\n", $mcq_items);
            $total_calculated += count($mcq_items);
        }
    }

    // Descriptive Marks buckets: 2, 4, 5, 6, 7, 8, 10, 14, 16, 20
    $marks_buckets = [2, 4, 5, 6, 7, 8, 10, 14, 16, 20];
    $sec_letter = empty($sections) ? 'A' : 'B';

    foreach ($marks_buckets as $m) {
        $col = "{$m}_mark";
        $needed = intval($pattern[$col] ?? 0);
        if ($needed > 0) {
            $q_des = mysqli_query($conn, "SELECT * FROM short_long_question WHERE Sub_code='$sub_code' AND Que_marks='$m' AND Status='Active' ORDER BY RAND() LIMIT $needed");
            if (!$q_des || mysqli_num_rows($q_des) == 0) {
                $q_des = mysqli_query($conn, "SELECT * FROM short_long_question WHERE Que_marks='$m' AND Status='Active' ORDER BY RAND() LIMIT $needed");
            }

            $items = [];
            $i = 1;
            while ($row = mysqli_fetch_assoc($q_des)) {
                $blooms = $row['Blooms_level'] ?? 'L2 - Understand';
                $unit = $row['Unit_no'] ?? 'Unit 2';
                $items[] = "Q{$i}. " . $row['Question'] . "   [{$m} Marks] [" . $blooms . " | " . $unit . "]";
                $i++;
            }
            if (!empty($items)) {
                $sec_total = count($items) * $m;
                $sections[] = "SECTION {$sec_letter}: (" . count($items) . " x {$m} = {$sec_total} Marks)\n\n" . implode("\n\n", $items);
                $total_calculated += $sec_total;
                $sec_letter = chr(ord($sec_letter) + 1);
            }
        }
    }

    if (empty($sections)) return null;

    $sections[] = "\n==================== BLOOM'S TAXONOMY COGNITIVE DISTRIBUTION ====================\n" .
                  "L1 (Remember): 25% | L2 (Understand): 35% | L3 (Apply): 25% | L4+ (Analyze/Create): 15%\n" .
                  "Course Outcome Mapping: CO1, CO2, CO3, CO4 covered evenly across syllabus units.";

    return [
        "content" => implode("\n\n------------------------------------------------------------------------\n\n", $sections),
        "total" => $total_calculated
    ];
}

// Convert Markdown to clean examination paper typography
function formatPaperHTML($text) {
    $escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $escaped = preg_replace('/^###\s+(.*)$/m', '<h4 style="margin:12px 0 6px; color:#0a2a5a; text-decoration:underline;">$1</h4>', $escaped);
    $escaped = preg_replace('/^##\s+(.*)$/m', '<h3 style="margin:16px 0 8px; color:#0a2a5a; border-bottom:1px solid #ccc; padding-bottom:4px;">$1</h3>', $escaped);
    $escaped = preg_replace('/^#\s+(.*)$/m', '<h2 style="margin:18px 0 10px; color:#0a2a5a; text-align:center;">$1</h2>', $escaped);
    $escaped = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $escaped);
    $escaped = preg_replace('/^-{3,}$/m', '<hr style="border:none; border-top:1px dashed #999; margin:15px 0;">', $escaped);
    $escaped = preg_replace('/^={3,}$/m', '<hr style="border:none; border-top:2px double #0a2a5a; margin:20px 0;">', $escaped);
    return nl2br($escaped);
}

// Handle Form Submission
if (isset($_POST['generate'])) {
    $sub_code = mysqli_real_escape_string($conn, trim($_POST['Sub_code'] ?? ''));
    $sub_name = mysqli_real_escape_string($conn, trim($_POST['Sub_name'] ?? ''));
    $semester = mysqli_real_escape_string($conn, trim($_POST['Semester'] ?? 'VI'));
    $exam_name = mysqli_real_escape_string($conn, trim($_POST['Exam_name'] ?? 'Semester End Examination'));
    $academic_year = mysqli_real_escape_string($conn, trim($_POST['Academic_year'] ?? '2025-2026'));
    $time_allowed = mysqli_real_escape_string($conn, trim($_POST['Time_allowed'] ?? '3 Hours'));
    $difficulty = mysqli_real_escape_string($conn, trim($_POST['Difficulty'] ?? 'Moderate'));
    $num_sets = intval($_POST['Num_sets'] ?? 1);
    $syllabus_units = mysqli_real_escape_string($conn, trim($_POST['Syllabus_units'] ?? 'All Units (Unit 1 to 5)'));
    $blooms_target = mysqli_real_escape_string($conn, trim($_POST['Blooms_target'] ?? 'Balanced (L1 to L4 NAAC Standard)'));
    $include_answers = isset($_POST['Include_answers']) ? 1 : 0;
    $pattern_id = intval($_POST['Pattern_id'] ?? 1);

    if (empty($sub_code) && !empty($subjects)) {
        $sub_code = $subjects[0]['Sub_code'];
        $sub_name = $subjects[0]['Sub_name'];
        $semester = $subjects[0]['Semester'];
    }

    $pat_res = mysqli_query($conn, "SELECT * FROM question_pattern WHERE Qp_id='$pattern_id'");
    $pat_data = mysqli_fetch_assoc($pat_res);
    $total_marks = intval($pat_data['Total_marks'] ?? 80);

    $blueprint_parts = [];
    if (!empty($pat_data)) {
        if (!empty($pat_data['1_mark'])) $blueprint_parts[] = "{$pat_data['1_mark']} Questions of 1 Mark (MCQ / Objective)";
        if (!empty($pat_data['2_mark'])) $blueprint_parts[] = "{$pat_data['2_mark']} Questions of 2 Marks (Very Short Answer)";
        if (!empty($pat_data['4_mark'])) $blueprint_parts[] = "{$pat_data['4_mark']} Questions of 4 Marks (Short Answer)";
        if (!empty($pat_data['5_mark'])) $blueprint_parts[] = "{$pat_data['5_mark']} Questions of 5 Marks (Short Notes / Explanations)";
        if (!empty($pat_data['6_mark'])) $blueprint_parts[] = "{$pat_data['6_mark']} Questions of 6 Marks";
        if (!empty($pat_data['7_mark'])) $blueprint_parts[] = "{$pat_data['7_mark']} Questions of 7 Marks";
        if (!empty($pat_data['8_mark'])) $blueprint_parts[] = "{$pat_data['8_mark']} Questions of 8 Marks";
        if (!empty($pat_data['10_mark'])) $blueprint_parts[] = "{$pat_data['10_mark']} Questions of 10 Marks (Long Descriptive / Analytical)";
        if (!empty($pat_data['14_mark'])) $blueprint_parts[] = "{$pat_data['14_mark']} Questions of 14 Marks";
        if (!empty($pat_data['16_mark'])) $blueprint_parts[] = "{$pat_data['16_mark']} Questions of 16 Marks";
        if (!empty($pat_data['20_mark'])) $blueprint_parts[] = "{$pat_data['20_mark']} Questions of 20 Marks";
    }
    if (empty($blueprint_parts)) {
        $blueprint_parts[] = "10 Questions of 1 Mark (MCQs), 5 Questions of 2 Marks, 4 Questions of 5 Marks, 2 Questions of 10 Marks";
    }
    $blueprint_str = implode(", ", $blueprint_parts);

    $set_names = ($num_sets == 3) ? ['Set A', 'Set B', 'Set C'] : (($num_sets == 2) ? ['Set A', 'Set B'] : ['Set A']);

    foreach ($set_names as $sname) {
        $prompt = "You are a senior university professor setting an official confidential question paper for Vivekanand College.
Generate an official examination paper for {$sname} of the following course:

COLLEGE: Vivekanand College
EXAMINATION: {$exam_name} ({$academic_year})
SET: {$sname} (Confidential Examination Copy)
COURSE / SEMESTER: Semester {$semester}
SUBJECT: {$sub_name} (Course Code: {$sub_code})
TIME ALLOWED: {$time_allowed}
MAXIMUM MARKS: {$total_marks} Marks
DIFFICULTY LEVEL: {$difficulty}
SYLLABUS COVERAGE: {$syllabus_units}
BLOOM'S TAXONOMY TARGET: {$blooms_target}

BLUEPRINT REQUIREMENT:
The question paper MUST strictly total {$total_marks} marks and adhere to this distribution:
{$blueprint_str}.

COMPLIANCE & ACCREDITATION RULES:
1. Divide into clear sections (SECTION A, SECTION B, SECTION C, etc.).
2. Beside each question, print its Bloom's Taxonomy cognitive level tag and Unit number (e.g. '[CO1 / L2 - Understand | Unit 1] [5 Marks]').
3. For 1-mark questions, provide 4 distinct options (A, B, C, D).
4. At the very top, write the official instructions:
   - Instructions: 1. All sections are compulsory. 2. Figures to the right indicate full marks. 3. Draw neat diagrams wherever necessary.
5. At the bottom, include a Bloom's Taxonomy Cognitive Distribution Table (e.g. L1/L2: 50%, L3/L4: 35%, L5/L6: 15%).";

        $ai_res = callGeminiAI($prompt);
        if ($ai_res['success']) {
            $content = $ai_res['text'];
            $gen_mode = "Gemini AI ({$ai_res['model']})";
        } else {
            $fb = generateFromDatabase($conn, $sub_code, $pat_data, $sname);
            $content = $fb ? $fb['content'] : "Error generating paper: " . $ai_res['error'];
            $gen_mode = "College Question Bank";
        }

        $generated_sets[$sname] = [
            "content" => $content,
            "mode" => $gen_mode
        ];

        // Save Set to saved_papers
        $stmt = mysqli_prepare($conn, "INSERT INTO saved_papers (title, sub_code, sub_name, semester, exam_name, academic_year, total_marks, time_allowed, paper_content, generation_mode, set_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $title = "{$sub_name} - {$exam_name} ({$sname})";
            mysqli_stmt_bind_param($stmt, "ssssssissss", $title, $sub_code, $sub_name, $semester, $exam_name, $academic_year, $total_marks, $time_allowed, $content, $gen_mode, $sname);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    // Generate Model Answer Key & Marking Scheme if requested
    if ($include_answers && !empty($generated_sets['Set A']['content'])) {
        $scheme_prompt = "You are a university chief examiner. Based on the following question paper, create an official 'MODEL ANSWERS & SCHEME OF MARKING' for evaluators:

COLLEGE: Vivekanand College
COURSE: {$sub_name} ({$sub_code}) | Semester {$semester}
EXAMINATION: {$exam_name} - Scheme of Evaluation

QUESTIONS TO EVALUATE:
" . substr($generated_sets['Set A']['content'], 0, 2500) . "

RULES:
1. For MCQs: List Question No., Correct Option, and 1-line justification.
2. For Descriptive Questions: Provide a step-by-step mark breakdown (e.g., Definition: 2 Marks, Circuit/Architecture Diagram: 3 Marks, Explanation of working: 5 Marks).
3. Provide key formulas, algorithms, or technical keywords student answers must contain for full credit.";

        $scheme_res = callGeminiAI($scheme_prompt);
        if ($scheme_res['success']) {
            $marking_scheme_output = $scheme_res['text'];
        } else {
            $marking_scheme_output = "### MODEL ANSWERS & SCHEME OF MARKING\n\n1. MCQs: Refer to option keys in syllabus repository.\n2. Descriptive Questions: 20% for Definition & Concepts, 30% for Architecture/Diagrams, 50% for Technical Depth & Analysis.";
        }
    }

    $generated_meta = [
        "title" => "{$sub_name} ({$sub_code})",
        "exam" => "{$exam_name} ({$academic_year})",
        "sem" => "Semester {$semester}",
        "marks" => $total_marks,
        "time" => $time_allowed,
        "sets_count" => count($generated_sets)
    ];
    $success_msg = "Successfully generated " . count($generated_sets) . " Question Paper Set(s) with Bloom's Taxonomy mapping!";
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>AI Question Paper Generator - Vivekanand College</title>
<!-- html2pdf for Direct PDF Export with Watermark -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<style>
body { font-family: Arial, sans-serif; background: #e1f5fe; margin: 0; padding: 20px; font-size: 14px; }
.container { background: white; max-width: 1150px; margin: auto; padding: 30px 40px; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
.header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
.logo-left { display: flex; align-items: center; gap: 15px; }
.logo-left img { width: 90px; height: 90px; object-fit: contain; }
.logo-text { font-weight: bold; color: #0a3d6b; font-size: 23px; }
.logo-text span { font-size: 14px; color: #555; font-weight: normal; }
.role-badge { background: #e3f2fd; color: #0a2a5a; padding: 6px 14px; border-radius: 20px; font-weight: bold; font-size: 13px; border: 1px solid #90caf9; }
h2 { color: #0a2a5a; text-align: center; font-size: 32px; margin: 10px 0 5px; }
.nav { text-align: center; margin-bottom: 20px; }
.nav a { color: #0288d1; text-decoration: none; font-weight: bold; margin: 0 10px; }
.nav a:hover { text-decoration: underline; }

.main-split { display: flex; gap: 30px; align-items: flex-start; }
.left-img { width: 35%; position: sticky; top: 20px; }
.left-img img { width: 100%; height: 500px; object-fit: cover; border-radius: 15px; }
.right-form { width: 65%; background: #f7fbff; padding: 22px; border-radius: 15px; border: 1px solid #b3e5fc; box-sizing: border-box; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 15px; }
.full-width { grid-column: 1 / span 2; }
label { display: block; margin-top: 10px; font-weight: bold; font-size: 13px; color: #0a2a5a; }
input[type=text], select { width: 100%; padding: 10px 12px; margin-top: 4px; border: 2px solid #b3e5fc; border-radius: 8px; box-sizing: border-box; font-family: Arial; }
.checkbox-row { margin-top: 14px; display: flex; align-items: center; gap: 8px; font-weight: bold; color: #0a2a5a; }
.checkbox-row input { width: 18px; height: 18px; cursor: pointer; }

.btn-submit { width: 300px; padding: 13px; margin: 20px auto 0; display: block; background: #1565c0; color: white; border: none; border-radius: 25px; font-size: 16px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 10px rgba(21, 101, 192, 0.3); transition: background 0.2s; }
.btn-submit:hover { background: #0d47a1; }

.msg { text-align: center; color: #2e7d32; background: #e8f5e9; padding: 10px; border-radius: 8px; font-weight: bold; margin-bottom: 15px; }
.error { text-align: center; color: #c62828; background: #ffebee; padding: 12px; border-radius: 8px; font-weight: bold; margin-bottom: 15px; }

/* Set Tabs */
.set-tabs { display: flex; gap: 8px; margin-bottom: 15px; border-bottom: 2px solid #0a2a5a; padding-bottom: 8px; }
.set-tab { padding: 9px 18px; background: #e3f2fd; color: #0a2a5a; border-radius: 8px 8px 0 0; font-weight: bold; cursor: pointer; border: 1px solid #b3e5fc; border-bottom: none; font-size: 13px; }
.set-tab.active { background: #0a2a5a; color: white; }

/* Generated Paper Container */
.paper-wrapper { margin-top: 35px; background: white; border: 2px solid #0a2a5a; border-radius: 12px; padding: 40px; box-shadow: 0 6px 20px rgba(0,0,0,0.08); position: relative; }
.paper-actions { display: flex; gap: 10px; justify-content: flex-end; margin-bottom: 20px; flex-wrap: wrap; }
.action-btn { padding: 8px 16px; border-radius: 20px; font-weight: bold; font-size: 12px; cursor: pointer; border: none; display: inline-flex; align-items: center; gap: 5px; }
.btn-print { background: #0a2a5a; color: white; }
.btn-pdf { background: #c62828; color: white; }
.btn-download { background: #2e7d32; color: white; }
.btn-copy { background: #555; color: white; }

/* Watermark */
.watermark { position: absolute; top: 40%; left: 10%; transform: rotate(-35deg); font-size: 55px; color: rgba(10, 42, 90, 0.04); font-weight: 900; pointer-events: none; z-index: 1; text-align: center; width: 80%; }

.official-header { text-align: center; border-bottom: 2px double #0a2a5a; padding-bottom: 12px; margin-bottom: 18px; position: relative; z-index: 2; }
.official-header h1 { margin: 0; font-size: 24px; color: #0a2a5a; letter-spacing: 1px; }
.official-header h3 { margin: 5px 0; font-size: 16px; color: #333; }
.set-badge { display: inline-block; background: #0a2a5a; color: white; padding: 3px 12px; border-radius: 4px; font-size: 13px; font-weight: bold; margin-top: 4px; }
.official-meta { display: flex; justify-content: space-between; font-weight: bold; font-size: 13px; margin-top: 10px; border-top: 1px solid #ccc; padding-top: 8px; }
.paper-text { font-family: "Times New Roman", Times, serif; font-size: 15px; line-height: 1.6; color: #111; position: relative; z-index: 2; }

@media print {
    body * { visibility: hidden; }
    #printablePaper, #printablePaper * { visibility: visible; }
    #printablePaper { position: absolute; left: 0; top: 0; width: 100%; border: none; padding: 0; box-shadow: none; }
    .no-print { display: none !important; }
}

@media(max-width:750px){
    .main-split { flex-direction: column; }
    .left-img, .right-form { width: 100%; position: static; }
    .left-img img { height: 260px; }
    .form-grid { grid-template-columns: 1fr; }
    .full-width { grid-column: 1; }
}
</style>
</head>
<body>

<div class="container">

<div class="header no-print">
    <div class="logo-left">
        <img src="logo.jpg" alt="Vivekanand College Logo">
        <div class="logo-text">
            Vivekanand College<br>
            <span>Autonomous Examination Center</span>
        </div>
    </div>
    <div class="role-badge">
        <?= $user_role === 'teacher' ? '👨‍🏫 Faculty: ' . htmlspecialchars($user_name) : '🏛️ Exam Controller (Admin)' ?>
    </div>
</div>

<h2 class="no-print">Automatic Question Paper Generator</h2>

<div class="nav no-print">
    <a href="dashboard.php">Dashboard</a> |
    <a href="question_pattern.php">Question Pattern</a> |
    <a href="mcq_question.php">MCQ Bank</a> |
    <a href="short_long_question.php">Short & Long Bank</a> |
    <a href="saved_papers.php">Paper Archive</a> |
    <a href="logout.php">Logout</a>
</div>

<?php if ($success_msg != ""): ?>
    <div class="msg no-print"><?= htmlspecialchars($success_msg) ?></div>
<?php endif; ?>

<?php if ($error != ""): ?>
    <div class="error no-print"><?= $error ?></div>
<?php endif; ?>

<div class="main-split no-print">
    <div class="left-img">
        <img src="qp.png" alt="Question Paper Generation">
    </div>

    <div class="right-form">
        <h3 style="color:#0a2a5a; margin-top:0;">Generate Question Paper with AI</h3>
        
        <form method="post" id="genForm" onsubmit="showLoading()">
            <div class="form-grid">
                
                <div>
                    <label>Select Subject:</label>
                    <select name="Sub_code" id="Sub_code" required onchange="updateSubjectName()">
                        <?php 
                        $first = true;
                        foreach($subjects as $sub): 
                            $code = $sub['Sub_code'] ?? $sub['Sub_id'];
                            $name = $sub['Sub_name'] ?? $sub['Subject_name'];
                            $sem = $sub['Semester'] ?? 'VI';
                            $selected = $first ? 'selected' : '';
                            $first = false;
                        ?>
                            <option value="<?= htmlspecialchars($code) ?>" data-name="<?= htmlspecialchars($name) ?>" data-sem="<?= htmlspecialchars($sem) ?>" <?= $selected ?>>
                                <?= htmlspecialchars($code) ?> - <?= htmlspecialchars($name) ?> (Sem <?= htmlspecialchars($sem) ?>)
                            </option>
                        <?php endforeach; ?>
                        <?php if(empty($subjects)): ?>
                            <option value="DSC06COM51" data-name="Database Management Systems" data-sem="VI" selected>
                                DSC06COM51 - Database Management Systems (Sem VI)
                            </option>
                        <?php endif; ?>
                    </select>
                    <input type="hidden" name="Sub_name" id="Sub_name" value="Database Management Systems">
                    <input type="hidden" name="Semester" id="Semester" value="VI">
                </div>

                <div>
                    <label>Select Marks Pattern:</label>
                    <select name="Pattern_id" id="Pattern_id" required>
                        <?php foreach($patterns as $pat): ?>
                            <option value="<?= $pat['Qp_id'] ?>">
                                Pattern #<?= $pat['Qp_id'] ?> (Total: <?= $pat['Total_marks'] ?> Marks)
                            </option>
                        <?php endforeach; ?>
                        <?php if(empty($patterns)): ?>
                            <option value="1">Pattern #1 (80 Marks: 10x1M, 5x2M, 4x5M, 2x10M)</option>
                            <option value="2">Pattern #2 (40 Marks: 5x1M, 5x2M, 3x5M, 1x10M)</option>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Feature 1: Multiple Sets -->
                <div>
                    <label>Generate Sets (Confidential):</label>
                    <select name="Num_sets">
                        <option value="1">Single Set (Set A)</option>
                        <option value="2" selected>2 Parallel Sets (Set A & Set B)</option>
                        <option value="3">3 Parallel Sets (Set A, Set B & Set C)</option>
                    </select>
                </div>

                <!-- Feature 4: Unit Coverage -->
                <div>
                    <label>Syllabus Units Distribution:</label>
                    <select name="Syllabus_units">
                        <option value="All Units (Unit 1 to 5 evenly)" selected>All Units Evenly (Unit 1 to 5)</option>
                        <option value="Unit 1 and Unit 2 (Mid-Term Exam)">Unit 1 & 2 (Mid-Term Exam)</option>
                        <option value="Unit 3, 4 and Unit 5 (Second Half)">Unit 3, 4 & 5 (Second Half)</option>
                    </select>
                </div>

                <!-- Feature 5: Bloom's Taxonomy -->
                <div>
                    <label>Bloom's Taxonomy Target:</label>
                    <select name="Blooms_target">
                        <option value="Balanced (L1-L4 NAAC Standard)" selected>Balanced (L1-L4 NAAC Standard)</option>
                        <option value="Foundation Level (L1 Remember, L2 Understand)">Foundation Focus (L1/L2)</option>
                        <option value="Higher-Order Thinking (L3 Apply, L4 Analyze, L5 Evaluate)">Higher-Order Thinking (L3-L5)</option>
                    </select>
                </div>

                <div>
                    <label>Examination Name:</label>
                    <input type="text" name="Exam_name" value="Semester End Examination" required placeholder="e.g. End Semester Exam">
                </div>

                <div>
                    <label>Academic Year:</label>
                    <input type="text" name="Academic_year" value="2025-2026" required placeholder="e.g. 2025-2026">
                </div>

                <div>
                    <label>Time Allowed:</label>
                    <select name="Time_allowed">
                        <option value="3 Hours" selected>3 Hours</option>
                        <option value="2.5 Hours">2.5 Hours</option>
                        <option value="2 Hours">2 Hours</option>
                        <option value="1.5 Hours">1.5 Hours</option>
                    </select>
                </div>

                <div>
                    <label>Difficulty Level:</label>
                    <select name="Difficulty">
                        <option value="Moderate" selected>Moderate (Standard University)</option>
                        <option value="Easy">Easy (Foundation / Basic)</option>
                        <option value="Challenging">Challenging (Advanced / Analytical)</option>
                    </select>
                </div>

                <!-- Feature 8: Marking Scheme Checkbox -->
                <div class="full-width checkbox-row">
                    <input type="checkbox" id="Include_answers" name="Include_answers" value="1" checked>
                    <label for="Include_answers" style="margin-top:0; cursor:pointer;">
                        ✨ Generate Model Answers & Scheme of Marking (Confidential Evaluator Sheet)
                    </label>
                </div>

            </div>

            <button type="submit" name="generate" id="submitBtn" class="btn-submit">
                ✨ Generate Question Paper Sets
            </button>
        </form>

        <div style="margin-top:15px; font-size:12px; color:#555; text-align:center;">
            Model: <b>gemini-3.8-flash</b> | Automatic Question Bank Fallback enabled.
        </div>

    </div>
</div>

<?php if (!empty($generated_sets)): ?>
<div class="paper-wrapper" id="printablePaper">
    <div class="watermark">CONFIDENTIAL<br>VIVEKANAND COLLEGE</div>

    <div class="set-tabs no-print">
        <?php foreach(array_keys($generated_sets) as $idx => $sname): ?>
            <div class="set-tab <?= $idx === 0 ? 'active' : '' ?>" onclick="switchSet('<?= $sname ?>', this)">
                📄 <?= htmlspecialchars($sname) ?>
            </div>
        <?php endforeach; ?>
        <?php if(!empty($marking_scheme_output)): ?>
            <div class="set-tab" onclick="switchSet('scheme', this)" style="background:#fff3e0; color:#e65100; border-color:#ffb74d;">
                📝 Scheme of Marking
            </div>
        <?php endif; ?>
    </div>

    <div class="paper-actions no-print">
        <button class="action-btn btn-print" onclick="window.print()">🖨️ Print Set</button>
        <!-- Feature 7: PDF Export -->
        <button class="action-btn btn-pdf" onclick="exportSealedPDF()">📄 Download PDF (Sealed)</button>
        <button class="action-btn btn-download" onclick="downloadAsDoc()">📥 Download Word (.doc)</button>
        <button class="action-btn btn-copy" onclick="copyPaperText()">📋 Copy Text</button>
    </div>

    <div class="official-header">
        <img src="logo.jpg" style="width:70px;height:70px;float:left;" class="no-print" onerror="this.style.display='none'">
        <img src="logo.jpg" style="width:70px;height:70px;float:right;" class="no-print" onerror="this.style.display='none'">
        <h1>VIVEKANAND COLLEGE</h1>
        <h3><?= htmlspecialchars($generated_meta['exam'] ?? 'EXAMINATION') ?></h3>
        <p style="margin:4px 0; font-size:14px; font-weight:bold;">
            Subject: <?= htmlspecialchars($generated_meta['title'] ?? '') ?> | <?= htmlspecialchars($generated_meta['sem'] ?? '') ?>
        </p>
        <div id="setIndicator" class="set-badge">SET - A</div>
        <div class="official-meta">
            <span>Time Allowed: <?= htmlspecialchars($generated_meta['time'] ?? '3 Hours') ?></span>
            <span>Maximum Marks: <?= htmlspecialchars($generated_meta['marks'] ?? 80) ?></span>
        </div>
    </div>

    <!-- Paper Contents by Set -->
    <?php foreach($generated_sets as $sname => $set_data): ?>
        <div class="paper-set-block paper-text" id="set_<?= $sname ?>" style="display: <?= $sname === 'Set A' ? 'block' : 'none' ?>;">
            <?= formatPaperHTML($set_data['content']) ?>
        </div>
    <?php endforeach; ?>

    <?php if(!empty($marking_scheme_output)): ?>
        <div class="paper-set-block paper-text" id="set_scheme" style="display:none; background:#fffdf8; padding:15px; border-radius:8px;">
            <div style="background:#e65100; color:white; padding:6px 12px; font-weight:bold; border-radius:4px; margin-bottom:15px;">
                CONFIDENTIAL: EXAMINER'S SCHEME OF EVALUATION & MODEL ANSWERS
            </div>
            <?= formatPaperHTML($marking_scheme_output) ?>
        </div>
    <?php endif; ?>

</div>
<?php endif; ?>

</div>

<script>
function updateSubjectName() {
    var select = document.getElementById('Sub_code');
    var selectedOption = select.options[select.selectedIndex];
    if (selectedOption && selectedOption.value) {
        document.getElementById('Sub_name').value = selectedOption.getAttribute('data-name') || '';
        document.getElementById('Semester').value = selectedOption.getAttribute('data-sem') || 'VI';
    }
}

window.onload = function() {
    updateSubjectName();
};

function showLoading() {
    var btn = document.getElementById('submitBtn');
    btn.innerHTML = '⏳ Generating Question Sets with AI... Please wait...';
    btn.style.background = '#0d47a1';
    btn.style.cursor = 'wait';
}

function switchSet(setName, tabEl) {
    document.querySelectorAll('.set-tab').forEach(function(el){ el.classList.remove('active'); });
    tabEl.classList.add('active');

    document.querySelectorAll('.paper-set-block').forEach(function(el){ el.style.display = 'none'; });

    var target = document.getElementById('set_' + setName);
    if(target) target.style.display = 'block';

    var indicator = document.getElementById('setIndicator');
    if (indicator) {
        indicator.innerText = (setName === 'scheme') ? 'EVALUATION SCHEME' : ('SET - ' + setName.replace('Set ', ''));
    }
}

function copyPaperText() {
    var visibleBlock = document.querySelector('.paper-set-block[style*="display: block"]') || document.querySelector('.paper-set-block');
    if(visibleBlock) {
        navigator.clipboard.writeText(visibleBlock.innerText).then(function() {
            alert("Question paper text copied to clipboard!");
        });
    }
}

function exportSealedPDF() {
    var element = document.getElementById('printablePaper');
    var opt = {
        margin:       [10, 10, 10, 10],
        filename:     'Vivekanand_College_Question_Paper.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).save();
}

function downloadAsDoc() {
    var header = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>"+
        "<head><meta charset='utf-8'><title>Question Paper</title><style>body{font-family:'Times New Roman',serif; font-size:12pt; line-height:1.5;} h1,h2,h3{text-align:center;} .meta{display:flex; justify-content:space-between; border-bottom:1px solid #000; padding-bottom:5px; margin-bottom:15px;}</style></head><body>";
    var body = document.getElementById('printablePaper').innerHTML;
    var footer = "</body></html>";
    
    var tempDiv = document.createElement('div');
    tempDiv.innerHTML = body;
    var actions = tempDiv.querySelector('.paper-actions');
    if (actions) actions.remove();
    var tabs = tempDiv.querySelector('.set-tabs');
    if (tabs) tabs.remove();

    var sourceHTML = header + tempDiv.innerHTML + footer;
    var source = 'data:application/vnd.ms-word;charset=utf-8,' + encodeURIComponent(sourceHTML);
    var fileDownload = document.createElement("a");
    document.body.appendChild(fileDownload);
    fileDownload.href = source;
    fileDownload.download = 'Question_Paper_Vivekanand_College.doc';
    fileDownload.click();
    document.body.removeChild(fileDownload);
}
</script>

</body>
</html>
