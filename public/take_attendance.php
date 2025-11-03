<?php
session_start();
include '../backend/config/db_connect.php';

// Only allow logged-in teachers
if (!isset($_SESSION['teacher_id'])) {
    header("Location: teacher_login.php");
    exit;
}

$teacher_id = $_SESSION['teacher_id'];
$subject = $_SESSION['subject'] ?? 'Subject';
$message = "";

// Fetch students enrolled in this subject from student_class table
// Note: keep the column access safe by using backticks in the SQL if subject name is a column
$col = preg_replace('/[^a-zA-Z0-9_]/', '', $subject); // basic sanitization for column name
$sql = "SELECT u.id, u.username
        FROM users u
        JOIN student_class sc ON u.id = sc.student_id
        WHERE sc.`$col` = 1
        ORDER BY u.username ASC";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $students = [];
    $message = "<p class='error'>Database error: " . htmlspecialchars($conn->error) . "</p>";
}

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

    $stmt = $conn->prepare("INSERT INTO attendance (teacher_id, date, subject, present_ids, absent_ids) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("issss", $teacher_id, $date, $subject, $present_str, $absent_str);
        if ($stmt->execute()) {
            $message = "<p class='success'>✅ Attendance recorded successfully for " . htmlspecialchars($subject) . "!</p>";
        } else {
            $message = "<p class='error'>❌ Error saving attendance: " . htmlspecialchars($conn->error) . "</p>";
        }
        $stmt->close();
    } else {
        $message = "<p class='error'>❌ Error preparing statement: " . htmlspecialchars($conn->error) . "</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Take Attendance — <?= htmlspecialchars($subject) ?></title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
      /* small overrides to match dashboards */
      .teacher-header {
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
      }
      .teacher-header .brand {
        display:flex; align-items:center; gap:12px;
      }
      .teacher-header .logo {
        width:46px; height:46px; border-radius:10px;
        display:flex; align-items:center; justify-content:center;
        color:#fff; font-weight:800; background: linear-gradient(135deg,var(--saffron),var(--saffron-dark));
      }
      .student-list th { background: var(--saffron-dark); color:#fff; }
      .checkbox { transform: scale(1.1); margin-left:6px; }
    </style>
</head>
<body>
<?php include 'includes/teacher_header.php'; ?>

<main class="container">
  <div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
      <div>
        <h2 style="margin:0 0 6px 0; font-family:'Merriweather', serif; color:var(--saffron-dark);">Take Attendance — <?= htmlspecialchars($subject) ?></h2>
        <p style="color:var(--muted); margin:0;">Date: <?= date('d M Y') ?></p>
      </div>
      <div>
        <a href="teacher_dashboard.php" class="btn" style="margin-right:8px;">Back</a>
      </div>
    </div>

    <?php if ($message): ?><?= $message ?><?php endif; ?>

    <form method="POST" style="margin-top:18px;">
    <table class="student-list">
      <thead>
        <tr>
          <th>Roll No</th>
          <th>Student Name</th>
          <th style="width:120px; text-align:center;">Present</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($students): ?>
          <?php
            // Fetch roll number from student_details
            foreach ($students as $s):
                $sid = $s['id'];
                $rollRes = $conn->query("SELECT roll_no FROM student_details WHERE student_id = $sid");
                $roll = ($rollRes && $rollRes->num_rows > 0) ? $rollRes->fetch_assoc()['roll_no'] : '-';
          ?>
            <tr>
              <td><?= htmlspecialchars($roll) ?></td>
              <td><?= htmlspecialchars($s['username']) ?></td>
              <td style="text-align:center;">
                <input type="checkbox" class="checkbox" name="present[]" value="<?= (int)$s['id'] ?>" checked>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="3" style="text-align:center; padding:16px;">No students found for this class.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

  <div style="margin-top:18px; display:flex; gap:10px; flex-wrap:wrap;">
    <button type="submit" class="btn">Submit Attendance</button>
    <a href="teacher_dashboard.php" class="btn ghost" style="background:transparent; color:var(--saffron-dark); border:1px solid var(--border);">Cancel</a>
  </div>
</form>

  </div>
</main>

<footer>
  &copy; <?= date('Y') ?> Ramakrishna Mission Vidyamandira
</footer>
</body>
</html>
