<?php
include 'config.php';
$msg = "";
$error = "";
$paperQuestions = [];

/* SAVE MCQ QUESTION */
if (isset($_POST['save'])) {

    $sub_code = trim($_POST['Sub_code'] ?? '');
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
        $error = "Please fill all fields.";
    } else {

        $stmt = mysqli_prepare($conn,
            "INSERT INTO mcq_question
            (Sub_code, Question, Option1, Option2,
             Option3, Option4, Option5)
            VALUES (?, ?, ?, ?, ?, ?, ?)");

        mysqli_stmt_bind_param(
            $stmt, "sssssss",
            $sub_code, $question, $op1, $op2,
            $op3, $op4, $op5
        );

        if (mysqli_stmt_execute($stmt)) {
            header("Location: mcq_question.php?saved=1");
            exit();
        } else {
            $error = "Error: " . mysqli_error($conn);
        }

        mysqli_stmt_close($stmt);
    }
}

if (isset($_GET['saved'])) {
    $msg = "MCQ Saved Successfully!";
}

/* CREATE PAPER FROM SELECTED QUESTIONS */
if (isset($_POST['create_paper'])) {

    $ids = $_POST['question_ids'] ?? [];

    $ids = array_values(array_unique(array_filter(
        array_map('intval', $ids),
        function ($id) {
            return $id > 0;
        }
    )));

    if (empty($ids)) {
        $error = "Please select at least one question.";
    } else {

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));

        $sql = "SELECT Sub_code, Question, Option1, Option2,
                Option3, Option4
                FROM mcq_question
                WHERE Question_id IN ($placeholders)
                ORDER BY Question_id ASC";

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
<title>QueGenP - MCQ Questions</title>

<style>
body {
    font-family: Arial;
    background: #e1f5fe;
    margin: 0;
    padding: 20px;
    font-size: 14px;
}
.container {
    background: white;
    max-width: 1150px;
    margin: auto;
    padding: 30px 40px;
    border-radius: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
.header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}
.logo-left {
    display: flex;
    align-items: center;
    gap: 15px;
}
.logo-left img {
    width: 90px;
    height: 90px;
    object-fit: contain;
}
.logo-text {
    font-weight: bold;
    color: #0a3d6b;
    font-size: 23px;
}
.logo-text span {
    font-size: 14px;
    color: #555;
    font-weight: normal;
}
h2 {
    color: #0a2a5a;
    text-align: center;
    font-size: 32px;
    margin: 10px 0 5px;
}
.nav {
    text-align: center;
    margin-bottom: 20px;
}
.nav a {
    color: #0288d1;
    text-decoration: none;
    font-weight: bold;
    margin: 0 10px;
}
.main-split {
    display: flex;
    gap: 30px;
    align-items: flex-start;
}
.left-img {
    width: 40%;
    position: sticky;
    top: 20px;
}
.left-img img {
    width: 100%;
    height: 470px;
    object-fit: cover;
    border-radius: 15px;
}
.right-form {
    width: 60%;
    background: #f7fbff;
    padding: 20px;
    border-radius: 15px;
    border: 1px solid #b3e5fc;
    box-sizing: border-box;
}
label {
    display: block;
    margin-top: 12px;
    font-weight: bold;
}
input[type=text], select, textarea {
    width: 100%;
    padding: 12px;
    margin-top: 5px;
    border: 2px solid #b3e5fc;
    border-radius: 8px;
    box-sizing: border-box;
    font-family: Arial;
}
input[type=submit], button {
    padding: 12px 18px;
    margin-top: 15px;
    background: #1565c0;
    color: white;
    border: none;
    border-radius: 25px;
    font-size: 15px;
    font-weight: bold;
    cursor: pointer;
}
.msg {
    text-align: center;
    color: green;
    font-weight: bold;
    margin: 10px 0;
}
.error {
    text-align: center;
    color: red;
    font-weight: bold;
    margin: 10px 0;
}
.table-wrap {
    overflow-x: auto;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}
th {
    background: #0a3d6b;
    color: white;
    padding: 10px;
    font-size: 13px;
}
td {
    padding: 8px;
    border: 1px solid #b3e5fc;
    text-align: left;
    font-size: 13px;
}
.option {
    display: block;
    margin: 5px 0;
}
.check {
    width: 17px;
    height: 17px;
}
.paper {
    margin-top: 30px;
    padding: 20px;
    border: 1px solid #b3e5fc;
}
.paper-title {
    text-align: center;
}
.paper-question {
    margin: 20px 0;
    break-inside: avoid;
}
.paper-option {
    margin: 7px 0 7px 20px;
}
@media(max-width:700px) {
    body {
        padding: 8px;
    }
    .container {
        padding: 15px;
    }
    .main-split {
        flex-direction: column;
    }
    .left-img, .right-form {
        width: 100%;
        position: static;
    }
    .left-img img {
        height: 250px;
    }
    .logo-text {
        font-size: 18px;
    }
}
@media print {
    body * {
        visibility: hidden;
    }
    #paper, #paper * {
        visibility: visible;
    }
    #paper {
        position: absolute;
        left: 0;
        top: 0;
        width: 95%;
        border: none;
    }
    .no-print {
        display: none !important;
    }
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
            <span>MCQ Question Entry</span>
        </div>
    </div>
</div>

<h2>MCQ Questions</h2>

<div class="nav no-print">
    <a href="question_pattern.php">Back: Question Pattern</a> |
    <a href="short_long_question.php">Next: Short & Long Que</a> |
    <a href="logout.php">Logout</a>
</div>

<?php if ($msg != ""): ?>
    <div class="msg"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<?php if ($error != ""): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="main-split no-print">

    <div class="left-img">
        <img src="mcq.png" alt="MCQ Questions">
    </div>

    <div class="right-form">
        <h3>Add MCQ Question</h3>

        <form method="post">

            <label>Subject Code:</label>
            <select name="Sub_code" required>
                <option value="">-- Select Subject --</option>
                <?php
                $sub_r = mysqli_query($conn, "SELECT * FROM subject");

                if ($sub_r) {
                    while ($sub = mysqli_fetch_assoc($sub_r)) {
                        $code = $sub['Sub_code']
                            ?? $sub['Sub_id']
                            ?? $sub['sub_code']
                            ?? '';

                        $name = $sub['Sub_name']
                            ?? $sub['Subject_name']
                            ?? $code;

                        echo '<option value="' .
                            htmlspecialchars($code, ENT_QUOTES, 'UTF-8') .
                            '">' .
                            htmlspecialchars($code, ENT_QUOTES, 'UTF-8') .
                            ' - ' .
                            htmlspecialchars($name, ENT_QUOTES, 'UTF-8') .
                            '</option>';
                    }
                }
                ?>
            </select>

            <label>Question:</label>
            <textarea name="Question" rows="3" required
                placeholder="Type your question here..."></textarea>

            <label>Option 1:</label>
            <input type="text" name="Option1" required>

            <label>Option 2:</label>
            <input type="text" name="Option2" required>

            <label>Option 3:</label>
            <input type="text" name="Option3" required>

            <label>Option 4:</label>
            <input type="text" name="Option4" required>

            <label>Correct Option:</label>
            <select name="Option5" required>
                <option value="">-- Select Correct Option --</option>
                <option value="1">Option 1</option>
                <option value="2">Option 2</option>
                <option value="3">Option 3</option>
                <option value="4">Option 4</option>
            </select>

            <input type="submit" name="save" value="Save Question">
        </form>
    </div>
</div>

<h3>📋 Saved Questions</h3>

<form method="post" id="questionForm" class="no-print">
<div class="table-wrap">
<table>
<tr>
    <th>Select</th>
    <th>Sub Code</th>
    <th>Question</th>
    <th>Options</th>
    <th>Correct Option</th>
</tr>

<?php
$r = mysqli_query(
    $conn,
    "SELECT * FROM mcq_question ORDER BY Question_id DESC"
);

if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $id = (int)($row['Question_id'] ?? 0);

        echo '<tr>';

        echo '<td><input class="check question-check" type="checkbox" name="question_ids[]" value="' .
            $id . '"></td>';

        echo '<td>' .
            htmlspecialchars($row['Sub_code'] ?? '') .
            '</td>';

        echo '<td>' .
            htmlspecialchars($row['Question'] ?? '') .
            '</td>';

        echo '<td>';
        echo '1) ' . htmlspecialchars($row['Option1'] ?? '') . '<br>';
        echo '2) ' . htmlspecialchars($row['Option2'] ?? '') . '<br>';
        echo '3) ' . htmlspecialchars($row['Option3'] ?? '') . '<br>';
        echo '4) ' . htmlspecialchars($row['Option4'] ?? '');
        echo '</td>';

        echo '<td>Option ' .
            htmlspecialchars($row['Option5'] ?? '') .
            '</td>';

        echo '</tr>';
    }
}
?>
</table>
</div>

<button type="button" onclick="selectAll(true)">Select All</button>
<button type="button" onclick="selectAll(false)">Deselect All</button>

</form>



</div>

<script>
function selectAll(status) {
    document.querySelectorAll('.question-check')
        .forEach(function (checkbox) {
            checkbox.checked = status;
        });
}

document.getElementById('questionForm')
    .addEventListener('submit', function(event) {
        const selected = document.querySelectorAll(
            '.question-check:checked'
        );

        if (selected.length === 0) {
            event.preventDefault();
            alert('Please select at least one question!');
        }
    });
</script>

</body>
</html>