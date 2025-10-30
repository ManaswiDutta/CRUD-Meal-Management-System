<?php
session_start();
include '../backend/config/db_connect.php';

// Access control
if (!isset($_SESSION['teacher_id'])) {
    header("Location: teacher_login.php");
    exit;
}

$teacher_name = $_SESSION['teacher_name'];
$subject = $_SESSION['subject'];

// Get total attendance records
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM attendance WHERE teacher_id = ?");
$stmt->bind_param("i", $_SESSION['teacher_id']);
$stmt->execute();
$total_att = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// Get last record
$stmt = $conn->prepare("SELECT date FROM attendance WHERE teacher_id = ? ORDER BY date DESC LIMIT 1");
$stmt->bind_param("i", $_SESSION['teacher_id']);
$stmt->execute();
$last = $stmt->get_result()->fetch_assoc();
$last_date = $last['date']  ?? "No records yet";

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Teacher Dashboard | Reynold</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
  <!-- Header -->
<?php include 'includes/teacher_header.php'; ?>

  <!-- Main Dashboard -->
  <div class="container dashboard">
    <div class="dashboard-actions">
      <h2>Welcome, <?= htmlspecialchars($teacher_name); ?> 👋</h2>
      <p>Subject: <strong><?= htmlspecialchars($subject); ?></strong></p>
    </div>

    <!-- Cards Section -->
    <div class="card-list">
      <div class="card">
        <h3><i class="fa-solid fa-clipboard-list"></i> Total Attendance Records</h3>
        <p>You’ve taken attendance for this many classes so far.</p>
        <p style="font-size:1.8rem; font-weight:700; color:var(--saffron-dark);"><?= $total_att ?></p>
      </div>

      <div class="card">
        <h3><i class="fa-solid fa-calendar-day"></i> Last Attendance Date</h3>
        <p>Your most recent record entry.</p>
        <p style="font-size:1.2rem; font-weight:600;"><?= htmlspecialchars($last_date); ?></p>
      </div>

      <div class="card">
        <h3><i class="fa-solid fa-user-check"></i> Take Attendance</h3>
        <p>Mark attendance for today’s <?= htmlspecialchars($subject) ?> class.</p>
        <a href="take_attendance.php" class="btn">Start Now</a>
      </div>

      <div class="card">
        <h3><i class="fa-solid fa-database"></i> View Records</h3>
        <p>See and filter previous attendance logs you’ve submitted.</p>
        <a href="view_attendance.php" class="btn">Open</a>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer>
    &copy; <?= date('Y'); ?> Ramakrishna Mission Vidyamandira
  </footer>
</body>
</html>
