<?php
include 'config.php';
session_start();

$msg = "";
$error = "";
$paperQuestions = [];

/* SAVE MCQ QUESTION */
if (isset($_POST['save'])) {
    $sub_code = strtoupper(trim($_POST['Sub_code'] ?? ''));
    $unit_no = trim($_POST['Unit_no'] ?? 'Unit 1');
    $blooms_level = trim($_POST['Blooms_level'] ?? 'L1 - Remember');
    $question = trim($_POST['Question'] ?? '');
    $op1 = trim($_POST['Option1'] ?? '');
    $op2 = trim($_POST['Option2'] ?? '');
    $op3 = trim($_POST['Option3'] ?? '');
    $op4 = trim($_POST['Option4'] ?? '');
    $op5 = trim($_POST['Option5'] ?? '');

    if (
        $sub_code === '' || $question === '' ||
        $op1 === '' || $op2 === '' ||
        $op3 === '' || $op4 === '' || $op5 === ''
    ) {
        $error = "Error: All fields are required. Please fill every field.";
    } elseif (!in_array($op5, ['1', '2', '3', '4'])) {
        $error = "Error: Correct Option must be 1, 2, 3, or 4.";
    } else {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO mcq_question
            (Sub_code, Unit_no, Blooms_level, Question, Option1, Option2, Option3, Option4, Option5)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        mysqli_stmt_bind_param(
            $stmt, "sssssssss",
            $sub_code, $unit_no, $blooms_level, $question, $op1, $op2,
            $op3, $op4, $op5
        );

        if (mysqli_stmt_execute($stmt)) {
            header("Location: MCQ_question.php?saved=1");
            exit();
        } else {
            $error = "Database Error: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}

if (isset($_GET['saved'])) {
    $msg = "MCQ Saved Successfully with Bloom's Taxonomy & Unit mapping!";
}

/* CREATE PAPER FROM SELECTED QUESTIONS */
if (isset($_POST['create_paper'])) {
    $ids = $_POST['question_ids'] ?? [];
    $ids = array_values(array_unique(array_filter(
        array_map('intval', $ids),
        function ($id) { return $id > 0; }
    )));

    if (empty($ids)) {
        $error = "Please select at least one question.";
    } else {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));

        $sql = "SELECT Sub_code, Unit_no, Blooms_level, Question, Option1, Option2, Option3, Option4
                FROM mcq_question WHERE Question_id IN ($placeholders) ORDER BY Question_id ASC";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$ids);
        mysqli_stmt_execute($stmt);
        $resultPaper = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($resultPaper)) {
            $paperQuestions[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MCQ Questions Bank - Vivekanand College</title>
<style>
body { font-family: Arial, sans-serif; background: #e1f5fe; margin: 0; padding: 20px; font-size: 14px; }
.container { background: white; max-width: 1150px; margin: auto; padding: 30px 40px; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
.header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
.logo-left { display: flex; align-items: center; gap: 15px; }
.logo-left img { width: 90px; height: 90px; object-fit: contain; }
.logo-text { font-weight: bold; color: #0a3d6b; font-size: 23px; }
.logo-text span { font-size: 14px; color: #555; font-weight: normal; }
h2 { color: #0a2a5a; text-align: center; font-size: 32px; margin: 10px 0 5px; }
.nav { text-align: center; margin-bottom: 20px; }
.nav a { color: #0288d1; text-decoration: none; font-weight: bold; margin: 0 10px; }
.main-split { display: flex; gap: 30px; align-items: flex-start; }
.left-img { width: 40%; position: sticky; top: 20px; }
.left-img img { width: 100%; height: 500px; object-fit: cover; border-radius: 15px; }
.right-form { width: 60%; background: #f7fbff; padding: 20px; border-radius: 15px; border: 1px solid #b3e5fc; box-sizing: border-box; }
label { display: block; margin-top: 10px; font-weight: bold; }
input[type=text], select, textarea { width: 100%; padding: 10px; margin-top: 4px; border: 2px solid #b3e5fc; border-radius: 8px; box-sizing: border-box; font-family: Arial; }
input[type=submit], button { padding: 11px 18px; margin-top: 15px; background: #1565c0; color: white; border: none; border-radius: 25px; font-size: 15px; font-weight: bold; cursor: pointer; }
.msg { text-align: center; color: green; font-weight: bold; margin: 10px 0; }
.error { text-align: center; color: red; font-weight: bold; margin: 10px 0; }
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th { background: #0a3d6b; color: white; padding: 10px; font-size: 13px; }
td { padding: 8px; border: 1px solid #b3e5fc; text-align: left; font-size: 13px; }
.badge-tag { background: #e3f2fd; color: #0a3d6b; padding: 2px 6px; border-radius: 8px; font-size: 11px; font-weight: bold; }
.paper { margin-top: 30px; padding: 20px; border: 2px solid #0a2a5a; border-radius: 10px; background: white; }
@media print {
    body * { visibility: hidden; }
    #paper, #paper * { visibility: visible; }
    #paper { position: absolute; left: 0; top: 0; width: 95%; border: none; }
    .no-print { display: none !important; }
}
</style>
</head>
<body>
<div class="container">

<div class="header">
    <div class="logo-left">
        <img src="logo.jpg" alt="College Logo">
        <div class="logo-text">
            Vivekanand College<br>
            <span>MCQ Question Bank</span>
        </div>
    </div>
</div>

<h2>MCQ Questions</h2>

<div class="nav no-print">
    <a href="dashboard.php">Dashboard</a> |
    <a href="question_pattern.php">Question Pattern</a> |
    <a href="short_long_question.php">Short & Long Bank</a> |
    <a href="generate_paper.php">Generate Paper (AI)</a> |
    <a href="saved_papers.php">Saved Papers Archive</a> |
    <a href="logout.php">Logout</a>
</div>

<?php if ($msg != ""): ?><div class="msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($error != ""): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="main-split no-print">
    <div class="left-img">
        <img src="mcq.png" alt="MCQ Questions">
    </div>

    <div class="right-form">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
            <h3 style="margin:0; color:#0a2a5a;">Add MCQ Question</h3>
            <span style="font-size:12px; color:#555; background:#e3f2fd; padding:4px 10px; border-radius:12px; font-weight:bold;">Unit & Bloom's Mapped</span>
        </div>

        <form method="post">
            <label>Subject Code:</label>
            <select name="Sub_code" required>
                <option value="">-- Select Subject --</option>
                <?php
                $sub_r = mysqli_query($conn, "SELECT * FROM subject");
                if ($sub_r) {
                    while ($sub = mysqli_fetch_assoc($sub_r)) {
                        $code = $sub['Sub_code'] ?? $sub['Sub_id'] ?? '';
                        $name = $sub['Sub_name'] ?? $sub['Subject_name'] ?? $code;
                        echo '<option value="' . htmlspecialchars($code) . '">' . htmlspecialchars($code) . ' - ' . htmlspecialchars($name) . '</option>';
                    }
                }
                ?>
            </select>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                <div>
                    <label>Unit / Module:</label>
                    <select name="Unit_no" required>
                        <option value="Unit 1">Unit 1</option>
                        <option value="Unit 2">Unit 2</option>
                        <option value="Unit 3">Unit 3</option>
                        <option value="Unit 4">Unit 4</option>
                        <option value="Unit 5">Unit 5</option>
                    </select>
                </div>
                <div>
                    <label>Bloom's Taxonomy Level:</label>
                    <select name="Blooms_level" required>
                        <option value="L1 - Remember">L1 - Remember (Recall / Define)</option>
                        <option value="L2 - Understand">L2 - Understand (Explain)</option>
                        <option value="L3 - Apply">L3 - Apply (Calculate / Solve)</option>
                        <option value="L4 - Analyze">L4 - Analyze (Compare / Distinguish)</option>
                    </select>
                </div>
            </div>

            <label>Question:</label>
            <textarea name="Question" rows="3" required placeholder="Type your MCQ question here..."></textarea>

            <label>Option 1:</label><input type="text" name="Option1" required>
            <label>Option 2:</label><input type="text" name="Option2" required>
            <label>Option 3:</label><input type="text" name="Option3" required>
            <label>Option 4:</label><input type="text" name="Option4" required>

            <label>Correct Option:</label>
            <select name="Option5" required>
                <option value="">-- Select Correct Option --</option>
                <option value="1">Option 1</option>
                <option value="2">Option 2</option>
                <option value="3">Option 3</option>
                <option value="4">Option 4</option>
            </select>

            <input type="submit" name="save" value="Save MCQ Question">
        </form>
    </div>
</div>

<h3>📋 Question Bank Records</h3>

<form method="post" id="questionForm" class="no-print">
<div class="table-wrap">
<table>
<tr>
    <th>Select</th>
    <th>Sub Code</th>
    <th>Unit & Bloom's</th>
    <th>Question</th>
    <th>Options</th>
    <th>Answer</th>
</tr>

<?php
$r = mysqli_query($conn, "SELECT * FROM mcq_question ORDER BY Question_id DESC");
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $id = (int)($row['Question_id'] ?? 0);
        $unit = $row['Unit_no'] ?? 'Unit 1';
        $blooms = $row['Blooms_level'] ?? 'L1';
        echo '<tr>';
        echo '<td><input class="check question-check" type="checkbox" name="question_ids[]" value="' . $id . '"></td>';
        echo '<td>' . htmlspecialchars($row['Sub_code'] ?? '') . '</td>';
        echo '<td><span class="badge-tag">' . htmlspecialchars($unit) . '</span><br><small style="color:#555;">' . htmlspecialchars($blooms) . '</small></td>';
        echo '<td>' . htmlspecialchars($row['Question'] ?? '') . '</td>';
        echo '<td>';
        echo '1) ' . htmlspecialchars($row['Option1'] ?? '') . '<br>';
        echo '2) ' . htmlspecialchars($row['Option2'] ?? '') . '<br>';
        echo '3) ' . htmlspecialchars($row['Option3'] ?? '') . '<br>';
        echo '4) ' . htmlspecialchars($row['Option4'] ?? '');
        echo '</td>';
        echo '<td><b>Option ' . htmlspecialchars($row['Option5'] ?? '') . '</b></td>';
        echo '</tr>';
    }
}
?>
</table>
</div>

<button type="submit" name="create_paper" style="background:#0a2a5a;">🖨️ Generate Paper from Selected</button>
<button type="button" onclick="selectAll(true)" style="background:#555;">Select All</button>
<button type="button" onclick="selectAll(false)" style="background:#777;">Deselect All</button>
</form>

<?php if (!empty($paperQuestions)): ?>
<div class="paper" id="paper">
    <div style="text-align:center; border-bottom:2px double #0a2a5a; padding-bottom:10px; margin-bottom:15px;">
        <h2>VIVEKANAND COLLEGE</h2>
        <h3>MCQ Examination Sheet</h3>
    </div>
    <?php foreach($paperQuestions as $idx => $pq): ?>
        <div style="margin:12px 0;">
            <b>Q<?= ($idx + 1) ?>. <?= htmlspecialchars($pq['Question']) ?></b> [1 Mark]<br>
            <div style="margin-left:15px; margin-top:4px;">
                (A) <?= htmlspecialchars($pq['Option1']) ?> &nbsp;&nbsp;&nbsp;&nbsp;
                (B) <?= htmlspecialchars($pq['Option2']) ?><br>
                (C) <?= htmlspecialchars($pq['Option3']) ?> &nbsp;&nbsp;&nbsp;&nbsp;
                (D) <?= htmlspecialchars($pq['Option4']) ?>
            </div>
        </div>
    <?php endforeach; ?>
    <button class="no-print" onclick="window.print()" style="margin-top:15px; background:#0a2a5a;">Print MCQ Paper</button>
</div>
<?php endif; ?>

</div>

<script>
function selectAll(status) {
    document.querySelectorAll('.question-check').forEach(function (cb) { cb.checked = status; });
}
</script>
</body>
</html>
