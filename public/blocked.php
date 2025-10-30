<?php
session_start();

// Decide where to send them back after 5s
$redirect_page = "login.php";
if (isset($_SESSION['role_id'])) {
    switch ($_SESSION['role_id']) {
        case 1: $redirect_page = "student_dashboard.php"; break;
        case 2: $redirect_page = "super_dashboard.php"; break;
        case 3: $redirect_page = "admin_dashboard.php"; break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Access Blocked</title>
    <style>
        body {
            background-color: black;
            color: #00ff99;
            font-family: 'Courier New', monospace;
            text-align: center;
            padding-top: 100px;
        }
        video, img {
            width: 400px;
            border: 3px solid #00ff99;
            border-radius: 10px;
            margin-top: 30px;
        }
        h1 {
            font-size: 2rem;
        }
    </style>
    <script src="assets/js/countdown.js"></script>
</head>
<body>
    <h1>Access Denied!</h1>
    <p>Uh uh uh... You can't come here!</p>

    <!-- <video controls loop >
        <source src="assets/media/videos/Ah ah ah! You didn't say the magic word! (Jurassic Park - Dennis Nedry).mp4" type="video/mp4">
    </video> -->


    <img src="assets/media/images/cat police.jpeg" alt="you cant escape">


    <p class="redirect-text">Redirecting in <span id="redirect-count">5</span> seconds...</p>
</body>
</html>
