<?php
session_start();
include '../backend/config/db_connect_v.php';

// Access control
if (!isset($_SESSION['teacher_id'])) {
    header("Location: teacher_login.php");
    exit;
}

$teacher_id = $_SESSION['teacher_id'];
$message = "";

function ids_to_array($str) {
    if (!$str || trim($str) === '') return [];
    $parts = array_filter(array_map('trim', explode(',', $str)), function($v){ return $v !== ''; });
    return array_map('intval', $parts);
}

function get_students_by_ids($conn, $ids) {
    if (empty($ids)) return [];
    $ids = array_map('intval', $ids);
    $in = implode(',', $ids);
    $sql = "SELECT u.id, u.first_name, u.last_name, sd.roll_no FROM users u JOIN student_details sd ON u.id = sd.student_id WHERE u.id IN ($in) ORDER BY sd.roll_no ASC";
    $res = $conn->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

// If an id param is present, show detail view
$view_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($view_id > 0) {
    $stmt = $conn->prepare("SELECT id, teacher_id, subject, date, present_ids, absent_ids FROM attendance WHERE id = ? AND teacher_id = ? LIMIT 1");
    $stmt->bind_param("ii", $view_id, $teacher_id);
    $stmt->execute();
    $record = $stmt->get_result()->fetch_assoc();
    if (!$record) {
        $message = "<p class='error'>Record not found or access denied.</p>";
    } else {
        $present_ids = ids_to_array($record['present_ids']);
        $absent_ids = ids_to_array($record['absent_ids']);
        $present_students = get_students_by_ids($conn, $present_ids);
        $absent_students = get_students_by_ids($conn, $absent_ids);
    }
} else {
    // List view
    $stmt = $conn->prepare("SELECT id, subject, date, present_ids, absent_ids FROM attendance WHERE teacher_id = ? ORDER BY date DESC");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Attendance Records</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        table { width:100%; border-collapse: collapse; }
        th, td { padding:8px 10px; border-bottom:1px solid var(--border); text-align:left; }
        th { background: #f7f7f7; }
        .muted { color: var(--muted); }
        .pill { display:inline-block; padding:6px 10px; border-radius:999px; background:#eee; font-weight:600; }
        .count-present { color:#007a3d; font-weight:700; }
        .count-absent { color:#b71c1c; font-weight:700; }
        .small-list { margin:8px 0; padding:0; list-style:none; }
        .small-list li { padding:4px 0; }
    </style>
</head>
<body>

<?php include 'includes/teacher_header.php'; ?>

<div class="container">
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
            <div>
                <h2>Attendance Records</h2>
                <p class="muted" style="margin:0;">View past attendance logs you have submitted.</p>
            </div>
            <a href="teacher_dashboard.php" class="btn">Back</a>
        </div>

        <?= $message ?>

        <?php if ($view_id > 0 && isset($record) && $record): ?>
            <div style="margin-top:16px;">
                <h3>Details — <?= htmlspecialchars(ucfirst($record['subject'])) ?> — <?= date('d M Y', strtotime($record['date'])) ?></h3>
                <p class="muted">ID: <?= (int)$record['id'] ?></p>

                <div style="display:flex;gap:20px;flex-wrap:wrap;margin-top:10px;">
                    <div style="flex:1;min-width:250px;">
                        <h4>Present <span class="pill count-present"><?= count($present_students) ?></span></h4>
                        <?php if ($present_students): ?>
                            <ul class="small-list">
                            <?php foreach ($present_students as $p): ?>
                                <li><?= htmlspecialchars($p['roll_no'] . ' — ' . $p['first_name'] . ' ' . $p['last_name']) ?></li>
                            <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="muted">No students marked present.</p>
                        <?php endif; ?>
                    </div>

                    <div style="flex:1;min-width:250px;">
                        <h4>Absent <span class="pill count-absent"><?= count($absent_students) ?></span></h4>
                        <?php if ($absent_students): ?>
                            <ul class="small-list">
                            <?php foreach ($absent_students as $a): ?>
                                <li><?= htmlspecialchars($a['roll_no'] . ' — ' . $a['first_name'] . ' ' . $a['last_name']) ?></li>
                            <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="muted">No students marked absent.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="margin-top:16px;"><a href="view_attendance.php" class="btn">Back to records</a></div>
            </div>

        <?php else: ?>
            <div style="margin-top:16px;overflow:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Subject</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($records)): ?>
                            <?php foreach ($records as $r):
                                $p = ids_to_array($r['present_ids']);
                                $a = ids_to_array($r['absent_ids']);
                            ?>
                                <tr>
                                    <td><?= date('d M Y', strtotime($r['date'])) ?></td>
                                    <td><?= htmlspecialchars(ucfirst($r['subject'])) ?></td>
                                    <td><span class="count-present"><?= count($p) ?></span></td>
                                    <td><span class="count-absent"><?= count($a) ?></span></td>
                                    <td style="text-align:right;"><a href="view_attendance.php?id=<?= (int)$r['id'] ?>" class="btn">View</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center;">No records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>
</div>

<footer>
    &copy; <?= date('Y') ?> Ramakrishna Mission Vidyamandira
</footer>
</body>
</html>
