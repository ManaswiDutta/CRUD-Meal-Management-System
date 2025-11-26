<?php
session_start();
include '../backend/config/db_connect_v.php';

// Access control
// if (!isset($_SESSION['teacher_id'])) {
//     header("Location: teacher_login.php");
//     exit;
// }

$teacher_id = $_SESSION['teacher_id'];
$subject = $_SESSION['subject'] ?? 'General';
$message = "";
$today = date('Y-m-d');

// Fetch students and check leave status
$stmt = $conn->prepare("
    SELECT 
        u.id, 
        CONCAT(u.first_name, ' ', u.last_name) AS full_name,
        sp.roll_number,
        CASE 
            WHEN EXISTS (
                SELECT 1 FROM leave_requests lr
                WHERE lr.student_user_id = u.id
                AND lr.is_active = 1
            ) THEN 'on_leave'
            ELSE 'normal'
        END AS leave_status
    FROM users u
    JOIN student_profiles sp ON u.id = sp.user_id
    JOIN student_class sc ON u.id = sc.student_id
    WHERE sc.subject = ?
    ORDER BY sp.roll_number ASC
");
$stmt->bind_param("s", $subject);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $present_ids = isset($_POST['present']) ? (array)$_POST['present'] : [];
    $absent_ids = [];

    foreach ($students as $s) {
        if (!in_array($s['id'], $present_ids)) {
            $absent_ids[] = $s['id'];
        }
    }

    $present_str = implode(',', $present_ids);
    $absent_str = implode(',', $absent_ids);

    $stmt = $conn->prepare("
        INSERT INTO attendance (teacher_id, subject, date, present_ids, absent_ids)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("issss", $teacher_id, $subject, $today, $present_str, $absent_str);
    if ($stmt->execute()) {
        $message = "<p class='success'>✅ Attendance saved successfully for <b>" . htmlspecialchars($subject) . "</b>.</p>";
    } else {
        $message = "<p class='error'>❌ Database error: " . htmlspecialchars($conn->error) . "</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Take Attendance – <?= htmlspecialchars($subject) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
  .attendance-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    margin-top: 20px;
  }
  .attendance-table th {
    background: linear-gradient(135deg, #ff9933, #ff7711);
    color: #fff;
    padding: 16px;
    text-align: center;
    font-weight: 600;
  }
  .attendance-table td {
    padding: 12px;
    text-align: center;
    border-bottom: 1px solid #eee;
  }
  .on-leave {
    background-color: #fff3cd !important;
  }
  .toggle-btn {
    width: 45px;
    height: 45px;
    border-radius: 12px;
    border: none;
    font-weight: 700;
    color: white;
    cursor: pointer;
    font-size: 1.2rem;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .present {
    background: linear-gradient(135deg, #10b981, #03ee16ff);
  }
  .absent {
    background: linear-gradient(135deg, #ef4444, #f50202ff);
  }
  .toggle-btn:hover { transform: scale(1.05); }
  .toggle-btn:disabled { opacity: 0.6; cursor: not-allowed; }
  .status-badge {
    padding: 6px 10px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
  }
  .status-normal { background: #d1fae5; color: #065f46; }
  .status-leave { background: #fef3c7; color: #92400e; }
</style>
<script>
function toggleStatus(btn) {
  const hidden = btn.nextElementSibling;
  if (btn.classList.contains('present')) {
    btn.classList.remove('present');
    btn.classList.add('absent');
    btn.textContent = 'A';
    hidden.disabled = true;
  } else {
    btn.classList.remove('absent');
    btn.classList.add('present');
    btn.textContent = 'P';
    hidden.disabled = false;
  }
}
</script>
</head>
<body>
<?php include 'includes/teacher_header.php'; ?>

<div class="container">
  <h2>Take Attendance – <?= htmlspecialchars($subject) ?></h2>
  <p>📅 <?= date('d M Y') ?></p>
  <?= $message ?>

  <form method="POST">
    <table class="attendance-table">
      <thead>
        <tr>
          <th>Roll No</th>
          <th>Name</th>
          <th>Status</th>
          <th>Attendance</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($students): ?>
          <?php foreach ($students as $s): ?>
            <tr class="<?= $s['leave_status'] === 'on_leave' ? 'on-leave' : '' ?>">
              <td><?= htmlspecialchars($s['roll_number']) ?></td>
              <td><?= htmlspecialchars($s['full_name']) ?></td>
              <td>
                <?php if ($s['leave_status'] === 'on_leave'): ?>
                  <span class="status-badge status-leave">On Leave</span>
                <?php else: ?>
                  <span class="status-badge status-normal">Normal</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($s['leave_status'] === 'on_leave'): ?>
                  <button type="button" class="toggle-btn absent" disabled>A</button>
                  <input type="hidden" name="present[]" value="<?= $s['id'] ?>" disabled>
                <?php else: ?>
                  <button type="button" class="toggle-btn present" onclick="toggleStatus(this)">P</button>
                  <input type="hidden" name="present[]" value="<?= $s['id'] ?>">
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="4">No students found for this subject.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
    <br>
    <button type="submit" class="btn">Submit Attendance</button>
  </form>
</div>

<footer>
  &copy; <?= date('Y') ?> Ramakrishna Mission Vidyamandira
</footer>
</body>
</html>
