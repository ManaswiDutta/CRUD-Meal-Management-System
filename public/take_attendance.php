<?php
session_start();
include '../backend/config/db_connect_v.php';

// Access control
if (!isset($_SESSION['teacher_id'])) {
    header("Location: teacher_login.php");
    exit;
}

$teacher_id = $_SESSION['teacher_id'];

// Get teacher info
$stmt = $conn->prepare("SELECT name, subject FROM teacher_account WHERE id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$teacher_name = $teacher['name'];
$subject = strtolower($teacher['subject']);
$message = "";

// Valid subjects
$valid_subjects = ['physics', 'chemistry', 'english', 'math'];
if (!in_array($subject, $valid_subjects)) {
    die("<p class='error'>Invalid subject configuration for teacher.</p>");
}

// Fetch students
$sql = "
    SELECT u.id, u.username, sd.roll_no , u.first_name, u.last_name
    FROM users u
    JOIN student_class sc ON u.id = sc.student_id
    JOIN student_details sd ON u.id = sd.student_id
    WHERE sc.`$subject` = 1
    ORDER BY sd.roll_no ASC
";
$res = $conn->query($sql);
$students = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = date('Y-m-d');
    $present_ids = isset($_POST['present']) ? (array)$_POST['present'] : [];
    $absent_ids = [];

    foreach ($students as $s) {
        if (!in_array($s['id'], $present_ids)) {
            $absent_ids[] = $s['id'];
        }
    }

    $present_str = implode(',', $present_ids);
    $absent_str = implode(',', $absent_ids);

    $stmt = $conn->prepare("INSERT INTO attendance (teacher_id, subject, date, present_ids, absent_ids) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $teacher_id, $subject, $date, $present_str, $absent_str);
    if ($stmt->execute()) {
        $message = "<p class='success'>✅ Attendance recorded successfully for today!</p>";
    } else {
        $message = "<p class='error'>❌ Error: " . htmlspecialchars($conn->error) . "</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Take Attendance — <?= htmlspecialchars(ucfirst($subject)) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .attendance-btn {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1rem;
            color: #ffffff !important;
            border: none;
            cursor: pointer;
            transition: transform 0.25s;
            outline: none;
        }
        /* Force colors and prevent other rules (from global CSS) overriding them */
        .attendance-btn.present-btn {
            background: #00b35a !important;
        }
        .attendance-btn.absent-btn {
            background: #e53935 !important;
        }

        /* Prevent color change on hover (and remove transform if you want fully static)
           The rules use !important to override any global `.btn:hover` or similar selectors. */
        .attendance-btn.present-btn:hover {
            background: #00b35a !important;
            transform: none !important;
            box-shadow: none !important;
        }
        .attendance-btn.absent-btn:hover {
            background: #e53935 !important;
            transform: none !important;
            box-shadow: none !important;
        }
        .attendance-btn:hover {
            transform: none !important;
            box-shadow: none !important;
        }
    </style>
</head>
<body>

<header>
    <h1>Teacher Panel</h1>
    <nav>
        <a href="teacher_dashboard.php">Dashboard</a>
        <a href="take_attendance.php">Take Attendance</a>
        <a href="view_attendance.php">View Records</a>
        <a href="logout_teacher.php">Logout</a>
    </nav>
</header>

<div class="container">
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
            <div>
                <h2>Take Attendance — <?= ucfirst($subject) ?></h2>
                <p style="color:var(--muted);margin:0;">Date: <?= date('d M Y') ?></p>
            </div>
            <a href="teacher_dashboard.php" class="btn">Back</a>
        </div>

        <?= $message ?>

        <form method="POST" id="attendanceForm" style="margin-top:16px;">
            <table>
                <thead>
                    <tr>
                        <th>Roll No</th>
                        <th>Student Name</th>
                        <th style="text-align:center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($students): ?>
                        <?php foreach ($students as $s): ?>
                            <tr>
                                <td><?= htmlspecialchars($s['roll_no']) ?></td>
                                <td><?= htmlspecialchars($s['first_name'] . " " . $s['last_name']) ?></td>
                                <td style="text-align:center;">
                                    <input type="checkbox" name="present[]" value="<?= (int)$s['id'] ?>" id="chk_<?= $s['id'] ?>" checked hidden>
                                    <button type="button" class="attendance-btn present-btn" data-id="<?= $s['id'] ?>" onclick="toggleStatus(this)">P</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align:center;">No students found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div style="margin-top:18px;display:flex;gap:10px;flex-wrap:wrap;">
                <button type="submit" class="btn">Submit Attendance</button>
                <a href="teacher_dashboard.php" class="btn" style="background:transparent;color:var(--saffron-dark);border:1px solid var(--border);">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
function toggleStatus(btn) {
    const id = btn.getAttribute('data-id');
    const checkbox = document.getElementById('chk_' + id);
    const isPresent = checkbox.checked;

    if (isPresent) {
        // Change to Absent
        checkbox.checked = false;
        btn.textContent = 'A';
        btn.classList.remove('present-btn');
        btn.classList.add('absent-btn');
    } else {
        // Change to Present
        checkbox.checked = true;
        btn.textContent = 'P';
        btn.classList.remove('absent-btn');
        btn.classList.add('present-btn');
    }
}
</script>

<footer>
    &copy; <?= date('Y') ?> Ramakrishna Mission Vidyamandira
</footer>
</body>
</html>
