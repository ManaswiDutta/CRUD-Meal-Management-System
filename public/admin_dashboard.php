<?php
session_start();
include '../backend/config/db_connect.php';

// Protect page: only admins
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: blocked.php");
    exit;
}

// Optional: handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Fetch some quick stats
$total_users = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'] ?? 0;
$total_students = $conn->query("SELECT COUNT(*) as c FROM users WHERE role_id = 1")->fetch_assoc()['c'] ?? 0;
$total_supers = $conn->query("SELECT COUNT(*) as c FROM users WHERE role_id = 2")->fetch_assoc()['c'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* page-specific layout similar to super dashboard */
        .dashboard-header {
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:16px;
            margin-bottom:18px;
        }
        .toolbar { display:flex; gap:8px; align-items:center; }
        .stats { display:flex; gap:14px; margin-top:10px; flex-wrap:wrap; }
        .stat-card {
            background:#fff;
            padding:14px 16px;
            border-radius:10px;
            box-shadow: 0 6px 18px rgba(15,23,42,0.06);
            min-width:140px;
            text-align:center;
        }
        .stat-card h3 { margin:0; font-size:1.6rem; color:#2d3a4a; }
        .stat-card p { margin:6px 0 0 0; color:#55606c; font-size:0.95rem; }
        .dashboard-actions { display:flex; gap:10px; margin-top:18px; flex-wrap:wrap; }
        .btn {
            padding:10px 14px;
            border-radius:8px;
            border:0;
            cursor:pointer;
            font-weight:600;
            background:#4f8cff;
            color:#fff;
        }
        .btn.manage { background:#10b981; }
        .btn.logout { background:#ef4444; }
        .card {
            background:#fff;
            border-radius:10px;
            padding:14px;
            box-shadow: 0 6px 18px rgba(15,23,42,0.06);
            margin-top:18px;
        }
        @media (max-width:720px) {
            .dashboard-header { flex-direction:column; align-items:flex-start; }
            .stats { flex-direction:column; }
            .stat-card { min-width:unset; width:100%; }
        }
    </style>
</head>
<body>
    <div class="dashboard-container" style="max-width:1000px;">
        <div class="dashboard-header">
            <div>
                <h1 style="margin:0;"><?= htmlspecialchars($_SESSION['username']); ?></h1>
                <p style="margin:6px 0 0 0; color:#55606c;">You are logged in as <strong>Admin</strong></p>
                <div class="stats" aria-hidden="true" style="margin-top:10px;">
                    <div class="stat-card">
                        <h3><?= (int)$total_users ?></h3>
                        <p>Total Users</p>
                    </div>
                    <div class="stat-card">
                        <h3><?= (int)$total_students ?></h3>
                        <p>Students</p>
                    </div>
                    <div class="stat-card">
                        <h3><?= (int)$total_supers ?></h3>
                        <p>Superintendents</p>
                    </div>
                </div>
            </div>

            <div class="toolbar">
                <div class="dashboard-actions">
                    <a href="create_user.php"><button class="btn">Create New User</button></a>
                    <a href="manage_users.php"><button class="btn manage">View / Manage Users</button></a>
                    <a href="admin_dashboard.php?logout=true"><button class="btn logout">Logout</button></a>
                </div>
            </div>
        </div>

        <div class="card">
            <h2 style="margin-top:0;">Admin Controls</h2>
            <p style="color:#55606c; margin-bottom:12px;">Use the buttons above to manage users. Click "View / Manage Users" to see full list, edit or delete accounts.</p>

            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <a href="create_user.php"><button class="btn">Create Student</button></a>
                <a href="create_user.php"><button class="btn">Create Superintendent</button></a>
                <a href="manage_users.php"><button class="btn manage">Manage All Users</button></a>
            </div>
        </div>

    </div>
</body>
</html>
