<?php
include 'config.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: admin.php");
    exit();
}

$msg = "";

// Handle Delete
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM saved_papers WHERE id=$del_id");
    header("Location: saved_papers.php?deleted=1");
    exit();
}

if (isset($_GET['deleted'])) {
    $msg = "Question paper record deleted successfully.";
}

// View Specific Paper
$view_paper = null;
if (isset($_GET['view'])) {
    $view_id = intval($_GET['view']);
    $vp = mysqli_query($conn, "SELECT * FROM saved_papers WHERE id=$view_id");
    if ($vp && mysqli_num_rows($vp) > 0) {
        $view_paper = mysqli_fetch_assoc($vp);
    }
}

// Format Markdown to HTML
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

// Fetch all saved papers
$papers_res = mysqli_query($conn, "SELECT * FROM saved_papers ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Saved Question Papers - Vivekanand College</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
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
.nav a:hover { text-decoration: underline; }

.msg { text-align: center; color: #2e7d32; background: #e8f5e9; padding: 10px; border-radius: 8px; font-weight: bold; margin-bottom: 15px; }

table { width: 100%; border-collapse: collapse; margin-top: 15px; }
th { background: #0a3d6b; color: white; padding: 10px; font-size: 13px; }
td { padding: 9px; border: 1px solid #b3e5fc; text-align: center; font-size: 13px; }
tr:hover { background: #f7fbff; }

.badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; }
.badge-ai { background: #e3f2fd; color: #1565c0; }
.badge-db { background: #e8f5e9; color: #2e7d32; }
.badge-set { background: #0a2a5a; color: white; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }

.btn-view { background: #1565c0; color: white; padding: 5px 12px; text-decoration: none; border-radius: 15px; font-size: 12px; font-weight: bold; }
.btn-del { background: #c62828; color: white; padding: 5px 12px; text-decoration: none; border-radius: 15px; font-size: 12px; font-weight: bold; }

/* Modal / Paper View */
.view-box { margin-top: 30px; border: 2px solid #0a2a5a; border-radius: 12px; padding: 35px; background: #fff; position: relative; }
.watermark { position: absolute; top: 40%; left: 10%; transform: rotate(-35deg); font-size: 55px; color: rgba(10, 42, 90, 0.04); font-weight: 900; pointer-events: none; z-index: 1; text-align: center; width: 80%; }
.paper-actions { display: flex; justify-content: flex-end; gap: 10px; margin-bottom: 15px; position: relative; z-index: 2; }
.action-btn { padding: 7px 16px; border-radius: 15px; font-size: 12px; font-weight: bold; border: none; cursor: pointer; }
.btn-print { background: #0a2a5a; color: white; }
.btn-pdf { background: #c62828; color: white; }
.btn-download { background: #2e7d32; color: white; }

.official-header { text-align: center; border-bottom: 2px double #0a2a5a; padding-bottom: 12px; margin-bottom: 15px; position: relative; z-index: 2; }
.official-header h1 { margin: 0; font-size: 22px; color: #0a2a5a; }
.official-meta { display: flex; justify-content: space-between; font-weight: bold; font-size: 13px; margin-top: 10px; }
.paper-content { font-family: "Times New Roman", Times, serif; font-size: 15px; line-height: 1.6; color: #111; position: relative; z-index: 2; }

@media print {
    body * { visibility: hidden; }
    #printablePaper, #printablePaper * { visibility: visible; }
    #printablePaper { position: absolute; left: 0; top: 0; width: 100%; border: none; }
    .no-print { display: none !important; }
}
</style>
</head>
<body>

<div class="container">

<div class="header no-print">
    <div class="logo-left">
        <img src="logo.jpg" alt="College Logo">
        <div class="logo-text">
            Vivekanand College<br>
            <span>Question Paper Archive</span>
        </div>
    </div>
</div>

<h2 class="no-print">Saved Question Papers</h2>

<div class="nav no-print">
    <a href="dashboard.php">Dashboard</a> |
    <a href="generate_paper.php">Generate New Paper</a> |
    <a href="question_pattern.php">Question Pattern</a> |
    <a href="mcq_question.php">MCQ Bank</a> |
    <a href="short_long_question.php">Short & Long Bank</a> |
    <a href="logout.php">Logout</a>
</div>

<?php if ($msg != ""): ?>
    <div class="msg no-print"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<?php if ($view_paper): ?>
<div class="view-box" id="printablePaper">
    <div class="watermark">CONFIDENTIAL<br>VIVEKANAND COLLEGE</div>
    
    <div class="paper-actions no-print">
        <button class="action-btn btn-print" onclick="window.print()">🖨️ Print Paper</button>
        <button class="action-btn btn-pdf" onclick="exportSealedPDF()">📄 Download PDF (Sealed)</button>
        <button class="action-btn btn-download" onclick="downloadWord()">📥 Download Word (.doc)</button>
        <a href="saved_papers.php" class="action-btn" style="background:#555; color:white; text-decoration:none;">✖ Close</a>
    </div>

    <div class="official-header">
        <h1>VIVEKANAND COLLEGE</h1>
        <h3><?= htmlspecialchars($view_paper['exam_name']) ?> (<?= htmlspecialchars($view_paper['academic_year']) ?>)</h3>
        <p style="margin:4px 0; font-weight:bold;">
            Subject: <?= htmlspecialchars($view_paper['sub_name']) ?> (<?= htmlspecialchars($view_paper['sub_code']) ?>) | Sem <?= htmlspecialchars($view_paper['semester']) ?>
        </p>
        <span class="badge-set"><?= htmlspecialchars($view_paper['set_name'] ?? 'Set A') ?></span>
        <div class="official-meta">
            <span>Time Allowed: <?= htmlspecialchars($view_paper['time_allowed']) ?></span>
            <span>Max Marks: <?= htmlspecialchars($view_paper['total_marks']) ?></span>
        </div>
    </div>

    <div class="paper-content" id="paperBody"><?= formatPaperHTML($view_paper['paper_content']) ?></div>
</div>
<br class="no-print">
<?php endif; ?>

<h3 class="no-print" style="color:#0a2a5a; margin-top:20px;">📋 Archived Question Papers</h3>

<table class="no-print">
    <tr>
        <th>ID</th>
        <th>Set</th>
        <th>Subject</th>
        <th>Examination</th>
        <th>Marks</th>
        <th>Engine</th>
        <th>Date</th>
        <th>Actions</th>
    </tr>
    <?php
    $count = 0;
    if ($papers_res && mysqli_num_rows($papers_res) > 0) {
        while ($p = mysqli_fetch_assoc($papers_res)) {
            $count++;
            $badge_cls = (strpos($p['generation_mode'], 'Gemini') !== false) ? 'badge-ai' : 'badge-db';
            echo "<tr>";
            echo "<td>{$p['id']}</td>";
            echo "<td><span class='badge-set'>" . htmlspecialchars($p['set_name'] ?? 'Set A') . "</span></td>";
            echo "<td><b>{$p['sub_code']}</b><br><small>{$p['sub_name']}</small></td>";
            echo "<td>{$p['exam_name']}<br><small>{$p['academic_year']}</small></td>";
            echo "<td><b>{$p['total_marks']} M</b></td>";
            echo "<td><span class='badge {$badge_cls}'>{$p['generation_mode']}</span></td>";
            echo "<td>" . date('d-m-Y H:i', strtotime($p['created_at'])) . "</td>";
            echo "<td>";
            echo "<a href='saved_papers.php?view={$p['id']}#printablePaper' class='btn-view'>View / Print</a> ";
            echo "<a href='saved_papers.php?delete={$p['id']}' class='btn-del' onclick=\"return confirm('Delete this question paper record?')\">Delete</a>";
            echo "</td>";
            echo "</tr>";
        }
    }
    if ($count == 0) {
        echo "<tr><td colspan='8'>No question papers archived yet. <a href='generate_paper.php' style='color:#1565c0; font-weight:bold;'>Generate your first paper now!</a></td></tr>";
    }
    ?>
</table>

</div>

<script>
function exportSealedPDF() {
    var element = document.getElementById('printablePaper');
    var opt = {
        margin:       [10, 10, 10, 10],
        filename:     'Vivekanand_College_Archived_Paper.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).save();
}

function downloadWord() {
    var header = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>"+
        "<head><meta charset='utf-8'><title>Question Paper</title><style>body{font-family:'Times New Roman',serif; font-size:12pt; line-height:1.5;} h1,h2,h3{text-align:center;}</style></head><body>";
    var body = document.getElementById('printablePaper').innerHTML;
    var footer = "</body></html>";
    
    var tempDiv = document.createElement('div');
    tempDiv.innerHTML = body;
    var actions = tempDiv.querySelector('.paper-actions');
    if (actions) actions.remove();

    var sourceHTML = header + tempDiv.innerHTML + footer;
    var source = 'data:application/vnd.ms-word;charset=utf-8,' + encodeURIComponent(sourceHTML);
    var fileDownload = document.createElement("a");
    document.body.appendChild(fileDownload);
    fileDownload.href = source;
    fileDownload.download = 'Question_Paper_Archive.doc';
    fileDownload.click();
    document.body.removeChild(fileDownload);
}
</script>

</body>
</html>
