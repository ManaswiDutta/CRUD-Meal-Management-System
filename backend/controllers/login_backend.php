<?php
// only start session if none exists to avoid PHP notice
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_COOKIE['login_attempts']) || (int)$_COOKIE['login_attempts'] < 5) {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // Fetch user by email
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify password
            if (password_verify($password, $user['password_hash'])) {
                // set session and redirect based on role
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role_id'] = $user['role_id'];
                $_SESSION['username'] = $user['username'];

                if ($user['role_id'] == 3) {
                    header("Location: admin_dashboard.php");
                    exit;
                } elseif ($user['role_id'] == 2) {
                    header("Location: super_dashboard.php");
                    exit;
                } elseif ($user['role_id'] == 1) {
                    header("Location: student_dashboard.php");
                    exit;
                } else {
                    $error = "Access denied: invalid role.";
                }
            } else {
                $error = "Invalid password.";
                if (!isset($_COOKIE['login_attempts'])) {
                    setcookie('login_attempts', 1, time() + 900); // 15 minutes
                } else {
                    $attempts = (int)$_COOKIE['login_attempts'] + 1;
                    setcookie('login_attempts', $attempts, time() + 900); // reset timer
                    if ($attempts >= 5) {
                        $error = "Too many failed attempts. Please try again later.";
                    }
                }
            }
        } else {
            $error = "No account found with that email.";
        }
    } else {
        $error = "Too many failed attempts. Please try again later.";
    }
}
?>