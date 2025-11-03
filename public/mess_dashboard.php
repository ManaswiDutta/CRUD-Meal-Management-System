<?php
include '../backend/config/db_connect.php';

// Default date (today) or navigate with ?date=YYYY-MM-DD
$today = date('Y-m-d');
$date = isset($_GET['date']) ? $_GET['date'] : $today;

// Fetch meal summary for that date
$stmt = $conn->prepare("SELECT * FROM meal_summary WHERE meal_date = ?");
$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();
$meal = $result->fetch_assoc();

$prev_date = date('Y-m-d', strtotime($date . ' -1 day'));
$next_date = date('Y-m-d', strtotime($date . ' +1 day'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Mess Dashboard | Reynold</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .date-nav {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 20px;
      margin-bottom: 24px;
    }
    .date-nav a {
      color: var(--saffron-dark);
      font-size: 1.3rem;
      background: var(--card);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      padding: 8px 14px;
      text-decoration: none;
      transition: 0.2s;
    }
    .date-nav a:hover {
      background: var(--saffron);
      color: #fff;
      transform: translateY(-2px);
    }
    .summary-box {
      background: var(--card);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      padding: 30px;
      text-align: center;
    }
    .summary-box h2 {
      color: var(--saffron-dark);
      margin-bottom: 18px;
      font-family: 'Merriweather', serif;
    }
    .meal-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 18px;
    }
    .meal-card {
      background: #fffaf3;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      padding: 18px;
      text-align: center;
      transition: 0.2s;
    }
    .meal-card:hover { transform: translateY(-3px); }
    .meal-card h3 {
      color: var(--saffron-dark);
      font-size: 1.1rem;
      margin-bottom: 6px;
    }
    .meal-card p {
      font-size: 1.4rem;
      font-weight: 700;
      color: var(--text);
    }
    .no-record {
      text-align: center;
      background: #fff6ea;
      color: #b55;
      padding: 20px;
      border-radius: var(--radius);
      border: 1px solid var(--border);
      margin-top: 16px;
    }
  </style>
</head>
<body>
  <header>
    <h1> Mess Dashboard</h1>
    <nav>
      <a href="meal_count.php"><i class="fa fa-utensils"></i> Meal Count</a>
      <a href="super_dashboard.php"><i class="fa fa-arrow-left"></i> Back</a>
    </nav>
  </header>

  <main class="container">
    <div class="date-nav">
      <a href="?date=<?= $prev_date ?>"><i class="fa fa-chevron-left"></i></a>
      <h2 style="color:var(--saffron-dark); margin:0;">
        <?= date('l, d M Y', strtotime($date)) ?>
      </h2>
      <a href="?date=<?= $next_date ?>"><i class="fa fa-chevron-right"></i></a>
    </div>

    <?php if ($meal): ?>
      <div class="summary-box">
        <h2>Meal Summary</h2>
        <div class="meal-grid">
          <div class="meal-card">
            <h3>Veg Lunch</h3>
            <p><?= (int)$meal['veg_lunch'] ?></p>
          </div>
          <div class="meal-card">
            <h3>Non-Veg Lunch</h3>
            <p><?= (int)$meal['nonveg_lunch'] ?></p>
          </div>
          <div class="meal-card">
            <h3>Veg Dinner</h3>
            <p><?= (int)$meal['veg_dinner'] ?></p>
          </div>
          <div class="meal-card">
            <h3>Non-Veg Dinner</h3>
            <p><?= (int)$meal['nonveg_dinner'] ?></p>
          </div>
        </div>
      </div>
    <?php else: ?>
      <div class="no-record">
        <i class="fa fa-exclamation-circle"></i> No meal data found for this date.<br>
        The super may not have approved it yet.
      </div>
    <?php endif; ?>
  </main>

  <footer>
    &copy; <?= date('Y') ?> Ramakrishna Mission Vidyamandira
  </footer>
</body>
</html>
