<?php
session_start();
include '../backend/config/db_connect.php';

// Only allow superintendents (2) or admins (3)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [2,3])) {
    header("Location: blocked.php");
    exit;
}

// helper to run aggregate query and return assoc counts
function get_counts($conn, $column) {
    $sql = "SELECT {$column} AS opt, COUNT(*) AS c FROM meal_preference GROUP BY {$column}";
    $res = $conn->query($sql);
    $out = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $key = $row['opt'] ?? 'None';
            $out[$key] = (int)$row['c'];
        }
    }
    return $out;
}

$lunch_counts = get_counts($conn, 'lunch_preference');
$dinner1_counts = get_counts($conn, 'dinner_preference1');
$dinner2_counts = get_counts($conn, 'dinner_preference2');

// total students (users with role_id = 1)
$total_students = (int)($conn->query("SELECT COUNT(*) AS c FROM users WHERE role_id = 1")->fetch_assoc()['c'] ?? 0);

// ensure common keys present
$possible_opts = ['Veg','Non-Veg','None','Rice','Roti']; // some sites may use different labels
foreach ([$lunch_counts, $dinner1_counts, $dinner2_counts] as $k => $v) {
    // nothing here; handled below when reading
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Meal Counts — Reynold</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .counts-grid { display:grid; grid-template-columns: repeat(3,1fr); gap:16px; margin-top:14px; }
        .count-card { background:#fff; padding:16px; border-radius:12px; box-shadow: 0 8px 24px rgba(2,6,23,0.06); }
        .count-card h3 { margin:0 0 8px 0; font-size:1.05rem; }
        .count-row { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #f1f5f9; }
        .count-row:last-child { border-bottom:0; }
        .badge-small { padding:6px 10px; border-radius:999px; color:#fff; font-weight:700; }
        .veg { background:#10b981; }
        .nonveg { background:#ef4444; }
        .none { background:#64748b; }
        .back-row { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:12px; }
    </style>
</head>
<body>
<header>
  <h1>Meal Counts</h1>
  <nav>
    <a href="super_dashboard.php">Home </a>
    <a href="manage_users.php"> Manage Students </a>
    <a href="meal_count.php"> Meal Count </a>
    <a href="logout.php" class="btn">Logout</a>
  </nav>
</header>

<div class="container">
    <div class="back-row">
        <div>
            <h2 style="margin:0">Current Meal Summary</h2>
            <p style="margin:6px 0 0 0; color:#64748b;">Total students: <?= $total_students ?></p>
        </div>
        <!-- <div style="display:flex; gap:8px;">
            <a href="manage_users.php" class="btn">Manage Users</a>
            <a href="<?= ($_SESSION['role_id']==3)?'admin_dashboard.php':'super_dashboard.php' ?>" class="btn ghost">Back</a>
        </div> -->
    </div>

    <div class="counts-grid">
        <div class="count-card">
            <h3>Lunch</h3>
            <?php
                $veg = $lunch_counts['Veg'] ?? 0;
                $non = $lunch_counts['Non-Veg'] ?? ($lunch_counts['NonVeg'] ?? 0);
                $none = $lunch_counts['None'] ?? 0;
            ?>
            <div class="count-row"><strong>Veg</strong><span class="badge-small veg"><?= $veg ?></span></div>
            <div class="count-row"><strong>Non-Veg</strong><span class="badge-small nonveg"><?= $non ?></span></div>
            <div class="count-row"><strong>None</strong><span class="badge-small none"><?= $none ?></span></div>
        </div>

        <div class="count-card">
            <h3>Dinner (Preference 1)</h3>
            <?php
                $d1veg = $dinner1_counts['Veg'] ?? 0;
                $d1non = $dinner1_counts['Non-Veg'] ?? ($dinner1_counts['NonVeg'] ?? 0);
                $d1none = $dinner1_counts['None'] ?? 0;
            ?>
            <div class="count-row"><strong>Veg</strong><span class="badge-small veg"><?= $d1veg ?></span></div>
            <div class="count-row"><strong>Non-Veg</strong><span class="badge-small nonveg"><?= $d1non ?></span></div>
            <div class="count-row"><strong>None</strong><span class="badge-small none"><?= $d1none ?></span></div>
        </div>

        <div class="count-card">
            <h3>Dinner (Preference 2)</h3>
            <?php
                $d2veg = $dinner2_counts['Veg'] ?? 0;
                $d2non = $dinner2_counts['Non-Veg'] ?? ($dinner2_counts['NonVeg'] ?? 0);
                $d2none = $dinner2_counts['None'] ?? 0;
            ?>
            <div class="count-row"><strong>Veg</strong><span class="badge-small veg"><?= $d2veg ?></span></div>
            <div class="count-row"><strong>Non-Veg</strong><span class="badge-small nonveg"><?= $d2non ?></span></div>
            <div class="count-row"><strong>None</strong><span class="badge-small none"><?= $d2none ?></span></div>
        </div>
    </div>

    <div style="margin-top:18px;">
        <div class="card">
            <h3 style="margin-top:0;">Raw breakdown</h3>
            <table>
                <thead>
                    <tr><th>Student ID</th><th>Lunch</th><th>Dinner 1</th><th>Dinner 2</th></tr>
                </thead>
                <tbody>
                    <?php
                    $q = "SELECT mp.student_id, u.username, mp.lunch_preference, mp.dinner_preference1, mp.dinner_preference2
                          FROM meal_preference mp
                          LEFT JOIN users u ON u.id = mp.student_id
                          ORDER BY u.username ASC";
                    $res = $conn->query($q);
                    if ($res && $res->num_rows) {
                        while ($r = $res->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>".(int)$r['student_id']."</td>";
                            echo "<td>".htmlspecialchars($r['lunch_preference'] ?? '-') ."</td>";
                            echo "<td>".htmlspecialchars($r['dinner_preference1'] ?? '-') ."</td>";
                            echo "<td>".htmlspecialchars($r['dinner_preference2'] ?? '-') ."</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo '<tr><td colspan="4" style="text-align:center;">No meal preference records.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</body>
</html>