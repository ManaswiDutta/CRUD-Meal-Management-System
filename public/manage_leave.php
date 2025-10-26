<?php
session_start();
include '../backend/config/db_connect.php';

// Only allow superintendent
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: blocked.php");
    exit;
}

$super_id = $_SESSION['user_id'];

// Approve or reject leave
if (isset($_POST['action']) && isset($_POST['leave_id'])) {
    $leave_id = (int)$_POST['leave_id'];
    $action = $_POST['action'];
    $remarks = trim($_POST['remarks'] ?? '');

    $status = ($action === 'approve') ? 'Approved' : 'Rejected';
    $stmt = $conn->prepare("UPDATE leaves SET status=?, remarks=?, reviewed_at=NOW() WHERE id=?");
    $stmt->bind_param("ssi", $status, $remarks, $leave_id);

    if ($stmt->execute()) {
        // Fetch student id for notification
        $s = $conn->prepare("SELECT student_id FROM leaves WHERE id=?");
        $s->bind_param("i", $leave_id);
        $s->execute();
        $result = $s->get_result();
        if ($result->num_rows === 1) {
            $student = $result->fetch_assoc();
            $student_id = $student['student_id'];
            $msg = ($status === 'Approved') 
                ? "Your leave request (ID #$leave_id) has been approved." 
                : "Your leave request (ID #$leave_id) was rejected. Remarks: $remarks";
            $type = ($status === 'Approved') ? 'success' : 'warning';
            $n = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, ?)");
            $n->bind_param("iss", $student_id, $msg, $type);
            $n->execute();
        }
        $feedback = "Leave #$leave_id has been $status successfully.";
    } else {
        $feedback = "Error updating leave status: " . $conn->error;
    }
}

// Fetch all leave requests
$query = "SELECT l.*, u.username, u.email 
          FROM leaves l 
          JOIN users u ON l.student_id = u.id 
          ORDER BY l.requested_at DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Leaves</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background:#f3f6fb; }
        .container { max-width:1100px; margin:30px auto; padding:20px; background:#fff; border-radius:10px; box-shadow:0 8px 24px rgba(15,23,42,0.08); }
        h2 { color:#f28c28; margin-bottom:20px; }
        table { width:100%; border-collapse:collapse; margin-top:15px; }
        th, td { text-align:left; padding:12px; border-bottom:1px solid #e5e7eb; }
        th { background:#f28c28; color:#fff; font-weight:700; }
        tr:nth-child(even) { background:#fafafa; }
        .status { font-weight:600; padding:6px 10px; border-radius:8px; }
        .status.Pending { background:#fef3c7; color:#92400e; }
        .status.Approved { background:#dcfce7; color:#166534; }
        .status.Rejected { background:#fee2e2; color:#991b1b; }
        .action-form { display:flex; flex-direction:column; gap:6px; }
        .btn {
            border:0; padding:6px 12px; border-radius:8px; font-weight:600; cursor:pointer;
            color:#fff;
        }
        .approve { background:#10b981; }
        .reject { background:#ef4444; }
        textarea { width:100%; height:60px; border:1px solid #e5e7eb; border-radius:8px; padding:6px; }
        .feedback { margin:10px 0; color:#2563eb; font-weight:600; }
        .top-bar {
            display:flex; align-items:center; justify-content:space-between;
        }
        .top-bar a {
            text-decoration:none;
            background:#f28c28;
            color:white;
            padding:8px 14px;
            border-radius:8px;
            font-weight:600;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="top-bar">
        <h2>Manage Leave Requests</h2>
        <a href="super_dashboard.php">← Back to Dashboard</a>
    </div>

    <?php if (isset($feedback)): ?>
        <p class="feedback"><?= htmlspecialchars($feedback) ?></p>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Student</th>
                <th>Period</th>
                <th>Reason</th>
                <th>Meals</th>
                <th>Status</th>
                <th>Remarks / Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td>#<?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['username']) ?><br><small><?= htmlspecialchars($row['email']) ?></small></td>
                <td><?= htmlspecialchars($row['from_date']) ?> → <?= htmlspecialchars($row['to_date']) ?></td>
                <td><?= htmlspecialchars($row['reason']) ?></td>
                <td>
                    <small>Dept: L <?= $row['lunch_departure'] ?>, D <?= $row['dinner_departure'] ?><br>
                    Ret: L <?= $row['lunch_return'] ?>, D <?= $row['dinner_return'] ?></small>
                </td>
                <td><span class="status <?= $row['status'] ?>"><?= $row['status'] ?></span></td>
                <td>
                    <?php if ($row['status'] === 'Pending'): ?>
                        <form method="POST" class="action-form">
                            <input type="hidden" name="leave_id" value="<?= $row['id'] ?>">
                            <textarea name="remarks" placeholder="Add remarks (optional)"></textarea>
                            <button type="submit" name="action" value="approve" class="btn approve">Approve</button>
                            <button type="submit" name="action" value="reject" class="btn reject">Reject</button>
                        </form>
                    <?php else: ?>
                        <small><strong>Remarks:</strong> <?= htmlspecialchars($row['remarks'] ?? '-') ?></small>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
