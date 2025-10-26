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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Meal Counts — Reynold</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body { background: #f9fafb; }
    header { background: #f28c28; color:#fff; padding:12px 20px; display:flex; align-items:center; justify-content:space-between; }
    header h1 { margin:0; font-size:1.2rem; }
    header nav a { color:#fff; margin-left:12px; text-decoration:none; font-weight:600; }
    .container { max-width:1100px; margin:28px auto; padding:20px; background:#fff; border-radius:12px; box-shadow:0 8px 24px rgba(15,23,42,0.06); }
    .counts-grid { display:grid; grid-template-columns: repeat(3,1fr); gap:16px; margin-top:14px; }
    .count-card { background:#fff7ed; padding:16px; border-radius:12px; box-shadow: 0 8px 18px rgba(2,6,23,0.06); }
    .count-card h3 { margin:0 0 8px 0; font-size:1.05rem; color:#0f172a; }
    .count-row { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #f1f5f9; }
    .count-row:last-child { border-bottom:0; }
    .badge-small { padding:6px 10px; border-radius:999px; color:#fff; font-weight:700; }
    .veg { background:#10b981; }
    .nonveg { background:#ef4444; }
    .none { background:#64748b; }
    .back-row { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:12px; }

    /* Table Styling */
    table { width:100%; border-collapse:separate; border-spacing:0 8px; margin-top:12px; }
    thead th { background: linear-gradient(90deg,#f28c28,#f28c28); color:#fff; padding:10px 14px; border-radius:8px; text-align:left; }
    tbody tr { background:#f8fafc; transition:0.15s; }
    tbody tr:hover { background:#fff7ed; }
    td { padding:10px 14px; color:#0f172a; border-radius:8px; }

    @media (max-width:800px){
      .counts-grid { grid-template-columns:1fr; }
      header { flex-direction:column; gap:8px; }
    }
  </style>
</head>
<body>
<header>
  <h1>Meal Counts</h1>
  <nav>
    <a href="super_dashboard.php">Home</a>
    <a href="manage_users.php">Manage Students</a>
    <a href="logout.php" class="btn">Logout</a>
  </nav>
</header>

<div class="container">
  <div class="back-row">
    <div>
      <h2 style="margin:0;">Current Meal Summary</h2>
      <p style="margin:6px 0 0 0; color:#64748b;">Total students: <?= $total_students ?></p>
    </div>
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
        $d2rice = $dinner2_counts['Rice'] ?? 0;
        $d2roti = $dinner2_counts['Roti'] ?? 0;
        $d2none = $dinner2_counts['None'] ?? 0;
      ?>
      <div class="count-row"><strong>Rice</strong><span class="badge-small veg"><?= $d2rice ?></span></div>
      <div class="count-row"><strong>Roti</strong><span class="badge-small nonveg"><?= $d2roti ?></span></div>
      <div class="count-row"><strong>None</strong><span class="badge-small none"><?= $d2none ?></span></div>
    </div>
  </div>

  <div style="margin-top:24px;">
    <div class="card" style="background:#fff; border-radius:12px; padding:18px; box-shadow:0 6px 18px rgba(15,23,42,0.06);">
      <h3 style="margin-top:0;">Raw Breakdown</h3>
      <table>
        <thead>
          <tr>
            <th>Student Name</th>
            <th>Lunch</th>
            <th>Dinner 1</th>
            <th>Dinner 2</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $q = "SELECT u.username, mp.lunch_preference, mp.dinner_preference1, mp.dinner_preference2
                FROM meal_preference mp
                LEFT JOIN users u ON u.id = mp.student_id
                WHERE u.role_id = 1
                ORDER BY u.username ASC";
          $res = $conn->query($q);
          if ($res && $res->num_rows) {
              while ($r = $res->fetch_assoc()) {
                  echo '<tr>';
                  echo '<td>'.htmlspecialchars($r['username'] ?? '-').'</td>';
                  echo '<td>'.htmlspecialchars($r['lunch_preference'] ?? '-').'</td>';
                  echo '<td>'.htmlspecialchars($r['dinner_preference1'] ?? '-').'</td>';
                  echo '<td>'.htmlspecialchars($r['dinner_preference2'] ?? '-').'</td>';
                  echo '</tr>';
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
