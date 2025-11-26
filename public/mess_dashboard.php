<?php
session_start();
include '../backend/config/db_connect.php';

// Date navigation setup
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Fetch the record for the selected date
$stmt = $conn->prepare("SELECT * FROM meal_summary WHERE date = ?");
$stmt->bind_param("s", $selected_date);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

$has_data = $data ? true : false;

// Date helpers
$yesterday = date('Y-m-d', strtotime($selected_date . ' -1 day'));
$tomorrow = date('Y-m-d', strtotime($selected_date . ' +1 day'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Mess Dashboard | Reynold</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .nav-row {
      display: flex; justify-content: space-between; align-items: center;
      margin-bottom: 20px;
    }
    .nav-btn {
      background: var(--saffron-dark);
      color: white;
      border: none;
      border-radius: 8px;
      padding: 8px 14px;
      cursor: pointer;
      font-weight: 600;
      text-decoration: none;
    }
    .nav-btn:hover { background: var(--saffron); }

    .summary-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
      gap: 18px;
      margin-top: 20px;
    }
    .summary-card {
      background: var(--card);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      padding: 18px;
      text-align: center;
      border: 1px solid var(--border);
    }
    .summary-card h3 {
      color: var(--saffron-dark);
      margin-bottom: 8px;
    }
    .summary-card .count {
      font-size: 1.6rem;
      font-weight: 700;
      color: #222;
    }
    .pending-box {
      text-align: center;
      background: #fff7ed;
      border: 1px solid #f28c28;
      color: #c46516;
      padding: 14px;
      border-radius: 10px;
      font-weight: 600;
      box-shadow: var(--shadow);
    }
  </style>
</head>
<body>

<header>
  <h1>Mess Dashboard</h1>
  <nav>
    <a href="super_dashboard.php">Super Dashboard</a>
    <a href="meal_count.php">Meal Count</a>
  </nav>
</header>

<div class="container">
  <div class="nav-row">
    <a href="?date=<?= $yesterday ?>" class="nav-btn"><i class="fa fa-arrow-left"></i> Previous</a>
    <h2 style="margin:0; color:var(--saffron-dark); font-family:'Merriweather', serif;">
      <?= date('l, d M Y', strtotime($selected_date)) ?>
    </h2>
    <a href="?date=<?= $tomorrow ?>" class="nav-btn">Next <i class="fa fa-arrow-right"></i></a>
  </div>

  <?php if (!$has_data): ?>
    <div class="pending-box">
      ⚠️ Meal count pending approval for this date.<br>
      Please check back once it’s sent by the Superintendent.
    </div>
  <?php else: ?>
    <div class="summary-grid">
      <div class="summary-card">
        <h3>Lunch veg</h3>
        <div class="count"><?= (int)$data['lunch_veg'] ?></div>
      </div>
      <div class="summary-card">
        <h3>Lunch Fish</h3>
        <div class="count"><?= (int)$data['lunch_nonveg'] ?></div>
      </div>
      <div class="summary-card">
        <h3>Dinner veg</h3>
        <div class="count"><?= (int)$data['dinner1_veg'] ?></div>
      </div>
      <div class="summary-card">
        <h3>Dinner egg</h3>
        <div class="count"><?= (int)$data['dinner1_nonveg'] ?></div>
      </div>
      <div class="summary-card">
        <h3>Dinner rice</h3>
        <div class="count"><?= (int)$data['dinner2_veg'] ?></div>
      </div>
      <div class="summary-card">
        <h3>Dinner roti</h3>
        <div class="count"><?= (int)$data['dinner2_nonveg'] ?></div>
      </div>
    </div>

    <div style="margin-top:20px; text-align:center;">
      <p>Status: 
        <strong style="color:<?= ($data['status'] == 'sent') ? 'green' : 'orange' ?>">
          <?= ucfirst($data['status']) ?>
        </strong>
      </p>
      <p style="font-size:0.9rem; color:#666;">
        Recorded on <?= date('d M Y, h:i A', strtotime($data['created_at'])) ?>
      </p>
    </div>
  <?php endif; ?>
</div>

<footer>
  &copy; <?= date('Y') ?> Ramakrishna Mission Vidyamandira
</footer>

</body>
</html>
