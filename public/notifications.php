<?php
session_start();
include '../backend/config/db_connect.php';

// Only allow students
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: blocked.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch notifications for this student
$stmt = $conn->prepare("SELECT id, message, created_at, is_read FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Mark all as read once opened
$conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $user_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications — <?= htmlspecialchars($_SESSION['username'] ?? 'Student') ?></title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: #f9fafb;
            font-family: 'Segoe UI', sans-serif;
        }
        .page-container {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            padding: 24px;
            border-radius: 14px;
            box-shadow: 0 8px 28px rgba(15,23,42,0.08);
        }
        h2 {
            margin: 0 0 20px 0;
            font-size: 1.4rem;
            color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .notif {
            padding: 16px 18px;
            border-radius: 12px;
            background: #f8fafc;
            margin-bottom: 12px;
            border-left: 5px solid #f28c28;
            transition: all 0.2s ease-in-out;
        }
        .notif.unread {
            background: #fff9f3;
            border-left-color: #f97316;
        }
        .notif p {
            margin: 0;
            font-size: 1rem;
            color: #0f172a;
        }
        .notif small {
            color: #64748b;
            display: block;
            margin-top: 6px;
            font-size: 0.85rem;
        }
        .notif:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(15,23,42,0.05);
        }
        .back-btn {
            background: #f28c28;
            border: none;
            color: #fff;
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }
        .back-btn:hover {
            background: #ea580c;
        }
        .empty {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
            font-style: italic;
        }
    </style>
</head>
<body>
<div class="page-container">
    <h2>
        Notifications
        <a href="student_dashboard.php"><button class="back-btn">← Back</button></a>
    </h2>

    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="notif <?= $row['is_read'] ? 'read' : 'unread' ?>">
                <p><?= htmlspecialchars($row['message']) ?></p>
                <small><?= date("F j, Y, g:i a", strtotime($row['created_at'])) ?></small>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty">No notifications yet 🎉</div>
    <?php endif; ?>
</div>
</body>
</html>
