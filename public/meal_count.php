<?php
session_start();
include '../backend/config/db_connect.php';

// Only allow superintendents or admins
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [2,3])) {
    header("Location: blocked.php");
    exit;
}

$tomorrow = date('Y-m-d', strtotime('+1 day'));

// Fetch all students with their meal preferences and leaves
$q = "SELECT u.id AS student_id, u.username,
             mp.lunch_preference, mp.dinner_preference1, mp.dinner_preference2,
             l.from_date, l.to_date,
             l.lunch_departure, l.dinner_departure, l.lunch_return, l.dinner_return
      FROM users u
      LEFT JOIN meal_preference mp ON u.id = mp.student_id
      LEFT JOIN leaves l ON u.id = l.student_id AND l.status = 'approved'
      WHERE u.role_id = 1
      ORDER BY u.username ASC";
$res = $conn->query($q);

// Totals
$totals = [
    'Lunch' => ['Veg' => 0, 'Non-Veg' => 0],
    'Dinner1' => ['Veg' => 0, 'Non-Veg' => 0],
    'Dinner2' => ['Rice' => 0, 'Roti' => 0],
];


date_default_timezone_set('Asia/Kolkata'); // Replace 'Asia/Kolkata' with your specific timezone
$time = date('g:i a');


?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Meal Count — Reynold</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="assets/css/style.css">
<style>
body { background:#f8fafc; font-family:'Segoe UI',sans-serif; color:#0f172a; }
.container { max-width:1000px; margin:30px auto; background:#fff; padding:24px; border-radius:12px; box-shadow:0 8px 24px rgba(15,23,42,0.08); }
h1 { margin-bottom:4px; }
p.note { color:#64748b; margin-top:0; font-size:0.95rem; }

table {
  width:100%; border-collapse:collapse; margin-top:18px;
  background:#fff; border-radius:12px; overflow:hidden;
  box-shadow:0 8px 24px rgba(15,23,42,0.08);
}
th,td { padding:12px 14px; text-align:left; border-bottom:1px solid #f1f5f9; }
th { background:#f28c28; color:#fff; font-weight:700; }
tr:last-child td { border-bottom:0; }
.absent-meal { color:#ef4444; font-weight:600; }
.present-meal { color:#111827; }

.summary {
  display:grid; grid-template-columns:repeat(3,1fr);
  gap:18px; margin-top:30px;
}
.summary-card {
  background:#fff; border-radius:12px; padding:18px;
  box-shadow:0 8px 28px rgba(15,23,42,0.06);
}
.summary-card h3 { margin:0 0 10px 0; font-size:1.1rem; color:#0f172a; }
.summary-card .row {
  display:flex; justify-content:space-between;
  align-items:center; padding:6px 0;
}
.badge {
  display:inline-block; padding:6px 10px; border-radius:999px;
  color:#fff; font-weight:700; font-size:0.9rem;
}
.veg { background:#10b981; }
.nonveg { background:#ef4444; }
.rice { background:#f59e0b; }
.roti { background:#3b82f6; }

.footer { text-align:center; color:#64748b; font-size:0.9rem; margin-top:20px; }
</style>
</head>
<body>
<?php include 'includes/headers.php'; ?>
<div class="container">
  <h1>Meal Count for <?= date('F j, Y', strtotime($tomorrow)) ?></h1>
  <p class="note">Meals marked <span style="color:#ef4444;font-weight:600;">red</span> are excluded due to leave.</p>

  <table>
    <thead>
      <tr>
        <th>Student</th>
        <th>Lunch</th>
        <th>Dinner 1</th>
        <th>Dinner 2</th>
      </tr>
    </thead>
    <tbody>
      <?php
      if ($res && $res->num_rows) {
        while ($r = $res->fetch_assoc()) {
          $from = $r['from_date'];
          $to = $r['to_date'];
          $exclude_lunch = false;
          $exclude_dinner = false;

          if ($from && $to) {
            if ($tomorrow > $from && $tomorrow < $to) {
              $exclude_lunch = $exclude_dinner = true;
            } elseif ($tomorrow == $from) {
              $exclude_lunch = (strtolower($r['lunch_departure']) !== 'yes');
              $exclude_dinner = (strtolower($r['dinner_departure']) !== 'yes');
            } elseif ($tomorrow == $to) {
              $exclude_lunch = (strtolower($r['lunch_return']) !== 'yes');
              $exclude_dinner = (strtolower($r['dinner_return']) !== 'yes');
            }
          }

          // Count totals only for included meals
          if (!$exclude_lunch && isset($totals['Lunch'][$r['lunch_preference']])) {
            $totals['Lunch'][$r['lunch_preference']]++;
          }
          if (!$exclude_dinner && isset($totals['Dinner1'][$r['dinner_preference1']])) {
            $totals['Dinner1'][$r['dinner_preference1']]++;
          }
          if (!$exclude_dinner && isset($totals['Dinner2'][$r['dinner_preference2']])) {
            $totals['Dinner2'][$r['dinner_preference2']]++;
          }

          echo "<tr>";
          echo "<td>" . htmlspecialchars($r['username']) . "</td>";
          echo "<td class='" . ($exclude_lunch ? "absent-meal" : "present-meal") . "'>" . htmlspecialchars($r['lunch_preference'] ?? '-') . "</td>";
          echo "<td class='" . ($exclude_dinner ? "absent-meal" : "present-meal") . "'>" . htmlspecialchars($r['dinner_preference1'] ?? '-') . "</td>";
          echo "<td class='" . ($exclude_dinner ? "absent-meal" : "present-meal") . "'>" . htmlspecialchars($r['dinner_preference2'] ?? '-') . "</td>";
          echo "</tr>";
        }
      } else {
        echo '<tr><td colspan="4" style="text-align:center;">No records found.</td></tr>';
      }
      ?>
    </tbody>
  </table>

  <div class="summary">
    <div class="summary-card">
      <h3>Lunch</h3>
      <div class="row"><span class="badge veg">Veg</span><span><?= $totals['Lunch']['Veg'] ?></span></div>
      <div class="row"><span class="badge nonveg">Non-Veg</span><span><?= $totals['Lunch']['Non-Veg'] ?></span></div>
    </div>
    <div class="summary-card">
      <h3>Dinner 1</h3>
      <div class="row"><span class="badge veg">Veg</span><span><?= $totals['Dinner1']['Veg'] ?></span></div>
      <div class="row"><span class="badge nonveg">Non-Veg</span><span><?= $totals['Dinner1']['Non-Veg'] ?></span></div>
    </div>
    <div class="summary-card">
      <h3>Dinner 2</h3>
      <div class="row"><span class="badge rice">Rice</span><span><?= $totals['Dinner2']['Rice'] ?></span></div>
      <div class="row"><span class="badge roti">Roti</span><span><?= $totals['Dinner2']['Roti'] ?></span></div>
    </div>
  </div>

  <div class="footer">Generated at <?php echo $time ; ?></div>
</div>
</body>
</html>
