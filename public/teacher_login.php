<?php
session_start();
include '../backend/config/db_connect_v.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // use null-coalescing to avoid "Undefined index" notices
    $subject = trim($_POST['subject'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // basic validation
    if ($subject === '' || $username === '' || $password === '') {
        $error = 'Please fill all fields.';
    } else {
        // prepare statement and check for errors
        $stmt = $conn->prepare("SELECT * FROM teacher_account WHERE subject = ? AND name = ?");
        if (!$stmt) {
            $error = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("ss", $subject, $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $teacher = $result->fetch_assoc();
                if (isset($teacher['password_hash']) && password_verify($password, $teacher['password_hash'])) {
                    $_SESSION['teacher_id'] = $teacher['id'];
                    $_SESSION['teacher_name'] = $teacher['name'];
                    $_SESSION['subject'] = $teacher['subject'];
                    header("Location: teacher_dashboard.php");
                    exit;
                } else {
                    $error = "Invalid password.";
                }
            } else {
                $error = "No teacher found for that subject/username.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Login | Reynold</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            display:flex; align-items:center; justify-content:center;
            height:100vh; background:linear-gradient(135deg,#f28c28,#f28c28);
            font-family:'Poppins',sans-serif;
        }
        .login-box {
            background:#fff; padding:40px 50px; border-radius:16px;
            box-shadow:0 10px 35px rgba(0,0,0,0.1); width:360px;
        }
        h2 {
            text-align:center; margin-bottom:22px; color:#333;
        }
        label { font-weight:600; color:#444; display:block; margin-bottom:6px; }
        select, input[type=text], input[type=password] {
            width:100%; padding:10px; margin-bottom:16px;
            border:1px solid #ccc; border-radius:10px; font-size:15px;
        }
        button {
            width:100%; padding:10px; border:none; border-radius:10px;
            background:#f28c28; color:#fff; font-weight:600; font-size:16px;
            cursor:pointer; transition:0.3s;
        }
        button:hover { background:#c46516; }
        .error { color:#d00000; font-size:14px; text-align:center; margin-bottom:12px; }
    </style>
</head>
<body>
<div class="login-box">
    <h2>Teacher Login</h2>
    <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <!-- <form method="POST" action="">
        <label for="subject">Select Subject:</label>
        <select name="subject" id="subject" required>
            <option value="">-- Choose Subject --</option>
            <option value="Physics">Physics</option>
            <option value="Chemistry">Chemistry</option>
            <option value="English">English</option>
            <option value="Math">Math</option>
        </select>

        <label for="username">Username:</label>
        <input type="text" name="username" id="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">

        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required>

        <button type="submit">Login</button>
    </form> -->

    <form method="POST" action="">
        <label for="subject">Select Subject:</label>
        <select name="subject" id="subject" required>
            <option value="">-- Choose Subject --</option>
            <option value="Physics">Physics</option>
            <option value="Chemistry">Chemistry</option>
            <option value="English">English</option>
            <option value="Math">Math</option>
        </select>

        <label for="username">Username:</label>
        <input type="text" name="username" id="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">

        <label for="password">Password</label>
        <input id="password" type="password" name="password" required>



        <button type="submit" class="btn" style="width:100%;">Sign In</button>

        <div class="login-footer">
        </div>
    </form>
</div>
</body>
</html>
