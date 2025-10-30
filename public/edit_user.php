<?php
session_start();
include '../backend/config/db_connect.php';

// Access control
$role = $_SESSION['role_id'] ?? null;
$user_session_id = $_SESSION['user_id'] ?? null;

if (!$user_session_id) {
    header("Location: blocked.php");
    exit;
}

$requested_edit_id = $_GET['edit_id'] ?? null;
if (!in_array($role, [2, 3]) && !($role == 1 && $requested_edit_id && $user_session_id == (int)$requested_edit_id)) {
    header("Location: blocked.php");
    exit;
}

$is_admin = ($role == 3);
$is_super = ($role == 2);
$is_student = ($role == 1);

$edit_mode = false;
$error = '';
$success = '';
$redirect_url = '';

$username = $email = '';
$role_id = 1;
$roll_no = $department = $year = '';
$lunch_pref = $dinner_pref1 = $dinner_pref2 = '';

// Fetch existing user data
if ($requested_edit_id) {
    $edit_id = (int)$requested_edit_id;

    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $user_result = $stmt->get_result();

    if ($user_result && $user_result->num_rows === 1) {
        $user = $user_result->fetch_assoc();
        if ($is_super && $user['role_id'] != 1) die("Access denied. Superintendents can only edit students.");

        $username = $user['username'];
        $email = $user['email'];
        $role_id = (int)$user['role_id'];
        $edit_mode = true;

        $stmt = $conn->prepare("SELECT * FROM student_details WHERE student_id = ?");
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $details_result = $stmt->get_result();
        if ($details_result && $details_result->num_rows === 1) {
            $details = $details_result->fetch_assoc();
            $roll_no = $details['roll_no'];
            $department = $details['department'];
            $year = $details['year'];
        }

        $stmt = $conn->prepare("SELECT * FROM meal_preference WHERE student_id = ?");
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $meal_result = $stmt->get_result();
        if ($meal_result && $meal_result->num_rows === 1) {
            $meal = $meal_result->fetch_assoc();
            $lunch_pref = $meal['lunch_preference'] ?? '';
            $dinner_pref1 = $meal['dinner_preference1'] ?? '';
            $dinner_pref2 = $meal['dinner_preference2'] ?? '';
        }
    } else {
        die("User not found.");
    }
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $edit_mode) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role_id = $is_super ? 1 : (int)($_POST['role_id'] ?? $role_id);

    $roll_no = $_POST['roll_no'] ?? $roll_no;
    $department = $_POST['department'] ?? $department;
    $year = $_POST['year'] ?? $year;
    $lunch_pref = $_POST['lunch_pref'] ?? $lunch_pref;
    $dinner_pref1 = $_POST['dinner_pref1'] ?? $dinner_pref1;
    $dinner_pref2 = $_POST['dinner_pref2'] ?? $dinner_pref2;

    if (empty($username) || empty($email)) {
        $error = "Username and email are required.";
    } else {
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username=?, email=?, role_id=?, password_hash=? WHERE id=?");
            $stmt->bind_param("ssisi", $username, $email, $role_id, $hashed_password, $edit_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username=?, email=?, role_id=? WHERE id=?");
            $stmt->bind_param("ssii", $username, $email, $role_id, $edit_id);
        }

        if ($stmt->execute()) {
            if ($role_id == 1) {
                $stmt = $conn->prepare("SELECT id FROM student_details WHERE student_id=?");
                $stmt->bind_param("i", $edit_id);
                $stmt->execute();
                $exists = $stmt->get_result();

                if ($exists && $exists->num_rows > 0) {
                    $stmt = $conn->prepare("UPDATE student_details SET roll_no=?, department=?, year=? WHERE student_id=?");
                    $stmt->bind_param("sssi", $roll_no, $department, $year, $edit_id);
                } else {
                    $stmt = $conn->prepare("INSERT INTO student_details (student_id, roll_no, department, year) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("isss", $edit_id, $roll_no, $department, $year);
                }
                $stmt->execute();

                if ($is_super || $is_student) {
                    $stmt = $conn->prepare("SELECT id FROM meal_preference WHERE student_id=?");
                    $stmt->bind_param("i", $edit_id);
                    $stmt->execute();
                    $has_meal = $stmt->get_result();

                    if ($has_meal && $has_meal->num_rows > 0) {
                        $stmt = $conn->prepare("UPDATE meal_preference SET lunch_preference=?, dinner_preference1=?, dinner_preference2=? WHERE student_id=?");
                        $stmt->bind_param("sssi", $lunch_pref, $dinner_pref1, $dinner_pref2, $edit_id);
                    } else {
                        $stmt = $conn->prepare("INSERT INTO meal_preference (student_id, lunch_preference, dinner_preference1, dinner_preference2) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("isss", $edit_id, $lunch_pref, $dinner_pref1, $dinner_pref2);
                    }
                    $stmt->execute();
                }
            }

            $success = "User updated successfully! Redirecting...";
            if ($is_admin) $redirect_url = "manage_users.php";
            elseif ($is_super) $redirect_url = "super_dashboard.php";
            elseif ($is_student) $redirect_url = "student_dashboard.php";
        } else {
            $error = "Error updating user: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit User</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
<?php include 'includes/headers.php'; ?>


  <div class="container">
    <div class="form-card">
      <h2>Edit User Details</h2>

      <?php if ($error): ?>
        <p class="error-msg"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>
      <?php if ($success): ?>
        <p class="success-msg"><?= htmlspecialchars($success) ?></p>
        <?php if ($redirect_url): ?>
          <script>
            setTimeout(() => {
              window.location.href = "<?= $redirect_url ?>";
            }, 2000);
          </script>
        <?php endif; ?>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="form-group">
          <label>Username</label>
          <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" required>
        </div>

        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
        </div>

        <div class="form-group">
          <label>Password (leave blank to keep current)</label>
          <input type="password" name="password">
        </div>

        <div class="form-group">
          <label>Role</label>
          <?php if ($is_super || $is_student): ?>
            <input type="hidden" name="role_id" value="1">
            <select disabled><option>Student</option></select>
          <?php else: ?>
            <select name="role_id" required>
              <option value="1" <?= ($role_id==1)?"selected":"" ?>>Student</option>
              <option value="2" <?= ($role_id==2)?"selected":"" ?>>Superintendent</option>
              <option value="3" <?= ($role_id==3)?"selected":"" ?>>Admin</option>
            </select>
          <?php endif; ?>
        </div>

        <?php if ($role_id == 1): ?>
          <h3>Student Details</h3>
          <div class="form-group">
            <label>Roll No</label>
            <input type="text" name="roll_no" value="<?= htmlspecialchars($roll_no) ?>">
          </div>

          <div class="form-group">
            <label>Department</label>
            <input type="text" name="department" value="<?= htmlspecialchars($department) ?>">
          </div>

          <div class="form-group">
            <label>Year</label>
            <select name="year">
              <?php $years = ['UG1','UG2','UG3','UG4','PG1','PG2'];
              foreach ($years as $y): ?>
                <option value="<?= $y ?>" <?= ($year==$y)?"selected":"" ?>><?= $y ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <h3>Meal Preferences</h3>
          <?php if ($is_super || $is_student): ?>
            <div class="form-group">
              <label>Lunch</label>
              <select name="lunch_pref">
                <?php foreach(['Veg','Non-Veg'] as $opt): ?>
                  <option value="<?= $opt ?>" <?= ($lunch_pref==$opt)?"selected":"" ?>><?= $opt ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Dinner 1</label>
              <select name="dinner_pref1">
                <?php foreach(['Veg','Non-Veg'] as $opt): ?>
                  <option value="<?= $opt ?>" <?= ($dinner_pref1==$opt)?"selected":"" ?>><?= $opt ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Dinner 2</label>
              <select name="dinner_pref2">
                <?php foreach(['Rice','Roti'] as $opt): ?>
                  <option value="<?= $opt ?>" <?= ($dinner_pref2==$opt)?"selected":"" ?>><?= $opt ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php else: ?>
            <p><strong>Lunch:</strong> <?= htmlspecialchars($lunch_pref) ?></p>
            <p><strong>Dinner 1:</strong> <?= htmlspecialchars($dinner_pref1) ?></p>
            <p><strong>Dinner 2:</strong> <?= htmlspecialchars($dinner_pref2) ?></p>
          <?php endif; ?>
        <?php endif; ?>

        <div class="form-buttons">
          <button type="submit" class="btn primary">Update</button>
          <a href="<?= $is_admin ? 'manage_users.php' : ($is_super ? 'super_dashboard.php' : 'student_dashboard.php') ?>" class="btn secondary">Back</a>
        </div>
      </form>
    </div>
  </div>

  <footer>
    &copy; <?= date('Y'); ?> Ramakrishna Mission Vidyamandira
  </footer>
</body>
</html>
