<?php
session_start();
include '../backend/config/db_connect.php';

// Only allow students
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: blocked.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$success = $error = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $from_date = $_POST['from_date'];
    $to_date = $_POST['to_date'];
    $reason = trim($_POST['reason']);
    $lunch_departure = $_POST['lunch_departure'];
    $dinner_departure = $_POST['dinner_departure'];
    $lunch_return = $_POST['lunch_return'];
    $dinner_return = $_POST['dinner_return'];

    if (empty($from_date) || empty($to_date) || empty($reason)) {
        $error = "Please fill in all required fields.";
    } else {
        $stmt = $conn->prepare("INSERT INTO leaves 
            (student_id, from_date, to_date, reason, lunch_departure, dinner_departure, lunch_return, dinner_return)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssss", $student_id, $from_date, $to_date, $reason, $lunch_departure, $dinner_departure, $lunch_return, $dinner_return);

        if ($stmt->execute()) {
            $success = "Leave request submitted successfully! Await superintendent’s approval.";
        } else {
            $error = "Error submitting request: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Apply for Leave</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .form-container {
            max-width: 700px;
            margin: 40px auto;
            background: #fff;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(15,23,42,0.08);
        }
        h2 { color: #f28c28; margin-bottom: 20px; }
        label { font-weight: 600; color: #333; display: block; margin-bottom: 6px; }
        input, textarea, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 0.95rem;
        }
        textarea { height: 90px; resize: none; }
        .radio-group {
            display: flex;
            gap: 12px;
            margin-bottom: 14px;
        }
        .btn {
            background: #f28c28;
            color: #fff;
            border: 0;
            border-radius: 8px;
            padding: 10px 18px;
            font-weight: 700;
            cursor: pointer;
        }
        .msg { font-weight: 600; margin-bottom: 12px; }
        .success { color: #16a34a; }
        .error { color: #dc2626; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Apply for Leave</h2>
        <?php if ($success): ?><p class="msg success"><?= $success ?></p><?php endif; ?>
        <?php if ($error): ?><p class="msg error"><?= $error ?></p><?php endif; ?>

        <form method="POST">
            <label>From Date:</label>
            <input type="date" name="from_date" required>

            <label>To Date:</label>
            <input type="date" name="to_date" required>

            <label>Reason for Leave:</label>
            <textarea name="reason" required></textarea>

            <h3>Meal Preferences</h3>

            <label>Lunch on Departure Day:</label>
            <div class="radio-group">
                <label><input type="radio" name="lunch_departure" value="Yes" required> Yes</label>
                <label><input type="radio" name="lunch_departure" value="No"> No</label>
            </div>

            <label>Dinner on Departure Day:</label>
            <div class="radio-group">
                <label><input type="radio" name="dinner_departure" value="Yes" required> Yes</label>
                <label><input type="radio" name="dinner_departure" value="No"> No</label>
            </div>

            <label>Lunch on Return Day:</label>
            <div class="radio-group">
                <label><input type="radio" name="lunch_return" value="Yes" required> Yes</label>
                <label><input type="radio" name="lunch_return" value="No"> No</label>
            </div>

            <label>Dinner on Return Day:</label>
            <div class="radio-group">
                <label><input type="radio" name="dinner_return" value="Yes" required> Yes</label>
                <label><input type="radio" name="dinner_return" value="No"> No</label>
            </div>

            <button type="submit" class="btn">Submit Leave</button>
        </form>
        <p><a href="student_dashboard.php">← Back to Dashboard</a></p>
    </div>
</body>
</html>
