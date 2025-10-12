<?php
session_start();
include '../backend/config/db_connect.php';

// Access control: allow admin, super, or student editing own info
if (
    !isset($_SESSION['user_id']) ||
    (
        !in_array($_SESSION['role_id'], [2, 3]) && // not admin or super
        !(
            $_SESSION['role_id'] == 1 && // student
            isset($_GET['edit_id']) &&
            $_SESSION['user_id'] == (int)$_GET['edit_id'] // editing own info
        )
    )
) {
    header("Location: login.php");
    exit;
}

$is_admin = $_SESSION['role_id'] == 3;
$is_super = $_SESSION['role_id'] == 2;
$is_student = $_SESSION['role_id'] == 1;

// Add: Only allow students to edit their own info
if ($is_student) {
    if (!isset($_GET['edit_id']) || $_SESSION['user_id'] != (int)$_GET['edit_id']) {
        die("Access denied. Students can only edit their own information.");
    }
}

$edit_mode = false;
$error = '';
$success = '';

$username = $email = $role_id = '';
$roll_no = $department = $year = '';
$lunch_pref = $dinner_pref1 = $dinner_pref2 = '';

// Check if editing an existing user
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];

    // Fetch user info
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $user_result = $stmt->get_result();

    if ($user_result->num_rows === 1) {
        $user = $user_result->fetch_assoc();

        // Super can only edit students
        if ($is_super && $user['role_id'] != 1) {
            die("Access denied. Superintendents can only edit students.");
        }

        $username = $user['username'];
        $email = $user['email'];
        $role_id = $user['role_id'];
        $edit_mode = true;

        // Fetch student details
        $stmt = $conn->prepare("SELECT * FROM student_details WHERE student_id = ?");
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $details_result = $stmt->get_result();
        if ($details_result->num_rows === 1) {
            $details = $details_result->fetch_assoc();
            $roll_no = $details['roll_no'];
            $department = $details['department'];
            $year = $details['year'];
        }

        // Fetch meal preferences
        $stmt = $conn->prepare("SELECT * FROM meal_preference WHERE student_id = ?");
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $meal_result = $stmt->get_result();
        if ($meal_result->num_rows === 1) {
            $meal = $meal_result->fetch_assoc();
            $lunch_pref = $meal['lunch_preference'];
            $dinner_pref1 = $meal['dinner_preference1'];
            $dinner_pref2 = $meal['dinner_preference2'];
        }
    } else {
        die("User not found.");
    }
} else {
    // Super cannot create new users
    if ($is_super) die("Access denied. Superintendents cannot create new users.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    if ($is_super) {
        $role_id = 1;
    } else {
        $role_id = (int)$_POST['role_id'];
    }
    $password = trim($_POST['password']);

    $roll_no = trim($_POST['roll_no']);
    $department = trim($_POST['department']);
    $year = $_POST['year'];

    $lunch_pref = $_POST['lunch_pref'];
    $dinner_pref1 = $_POST['dinner_pref1'];
    $dinner_pref2 = $_POST['dinner_pref2'];

    if ($edit_mode) {
        // Update users table
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username=?, email=?, role_id=?, password_hash=? WHERE id=?");
            $stmt->bind_param("ssisi", $username, $email, $role_id, $hashed_password, $edit_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username=?, email=?, role_id=? WHERE id=?");
            $stmt->bind_param("ssii", $username, $email, $role_id, $edit_id);
        }
        if (!$stmt->execute()) $error = "Error updating user: " . $conn->error;

        // Update student_details table
        if ($role_id == 1) {
            // Check if entry exists
            $stmt = $conn->prepare("SELECT id FROM student_details WHERE student_id=?");
            $stmt->bind_param("i", $edit_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Update
                $stmt = $conn->prepare("UPDATE student_details SET roll_no=?, department=?, year=? WHERE student_id=?");
                $stmt->bind_param("sssi", $roll_no, $department, $year, $edit_id);
            } else {
                // Insert
                $stmt = $conn->prepare("INSERT INTO student_details (student_id, roll_no, department, year) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $edit_id, $roll_no, $department, $year);
            }
            if (!$stmt->execute()) $error = "Error updating student details: " . $conn->error;

            // Update meal_preference table
            $stmt = $conn->prepare("SELECT id FROM meal_preference WHERE student_id=?");
            $stmt->bind_param("i", $edit_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $stmt = $conn->prepare("UPDATE meal_preference SET lunch_preference=?, dinner_preference1=?, dinner_preference2=? WHERE student_id=?");
                $stmt->bind_param("sssi", $lunch_pref, $dinner_pref1, $dinner_pref2, $edit_id);
            } else {
                $stmt = $conn->prepare("INSERT INTO meal_preference (student_id, lunch_preference, dinner_preference1, dinner_preference2) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $edit_id, $lunch_pref, $dinner_pref1, $dinner_pref2);
            }
            if (!$stmt->execute()) $error = "Error updating meal preference: " . $conn->error;
        }

        if (!$error) {
            $success = "User updated successfully! Redirecting...";
            if ($is_admin) header("Refresh: 2; url=manage_users.php");
            elseif ($is_super) header("Refresh: 2; url=super_dashboard.php");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $edit_mode ? "Edit User" : "Create User" ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="form-container">
        <h2><?= $edit_mode ? "Edit User" : "Create New User" ?></h2>

        <?php if ($error) echo "<p style='color:red;'>$error</p>"; ?>
        <?php if ($success) echo "<p style='color:green;'>$success</p>"; ?>

        <form method="POST" action="">
            <h3>User Info</h3>
            <label>Username:</label><br>
            <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" required><br><br>

            <label>Email:</label><br>
            <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required><br><br>

            <label>Password <?= $edit_mode ? "(leave blank to keep current)" : "" ?>:</label><br>
            <input type="password" name="password"><br><br>

            <label>Role:</label><br>
            <select name="role_id" <?= $is_super ? "disabled" : "" ?> required>
                <option value="1" <?= ($role_id==1)?"selected":"" ?>>Student</option>
                <?php if ($is_admin): ?>
                <option value="2" <?= ($role_id==2)?"selected":"" ?>>Superintendent</option>
                <option value="3" <?= ($role_id==3)?"selected":"" ?>>Admin</option>
                <?php endif; ?>
            </select><br><br>

            <?php if ($role_id == 1): ?>
            <h3>Student Details</h3>
            <label>Roll No:</label><br>
            <input type="text" name="roll_no" value="<?= htmlspecialchars($roll_no) ?>" required><br><br>

            <label>Department:</label><br>
            <input type="text" name="department" value="<?= htmlspecialchars($department) ?>"><br><br>

            <label>Year:</label><br>
            <select name="year" required>
                <?php 
                $years = ['UG1','UG2','UG3','UG4','PG1','PG2'];
                foreach($years as $y): ?>
                    <option value="<?= $y ?>" <?= ($year==$y)?"selected":"" ?>><?= $y ?></option>
                <?php endforeach; ?>
            </select><br><br>

            <h3>Meal Preferences</h3>
            <label>Lunch:</label><br>
            <select name="lunch_pref" required>
                <?php $lunch_options = ['Veg','Non-Veg'];
                foreach($lunch_options as $opt): ?>
                    <option value="<?= $opt ?>" <?= ($lunch_pref==$opt)?"selected":"" ?>><?= $opt ?></option>
                <?php endforeach; ?>
            </select><br><br>

            <label>Dinner 1:</label><br>
            <select name="dinner_pref1" required>
                <?php $dinner1_options = ['Veg','Non-Veg'];
                foreach($dinner1_options as $opt): ?>
                    <option value="<?= $opt ?>" <?= ($dinner_pref1==$opt)?"selected":"" ?>><?= $opt ?></option>
                <?php endforeach; ?>
            </select><br><br>

            <label>Dinner 2:</label><br>
            <select name="dinner_pref2" required>
                <?php $dinner2_options = ['Rice','Roti'];
                foreach($dinner2_options as $opt): ?>
                    <option value="<?= $opt ?>" <?= ($dinner_pref2==$opt)?"selected":"" ?>><?= $opt ?></option>
                <?php endforeach; ?>
            </select><br><br>
            <?php endif; ?>

            <button type="submit"><?= $edit_mode ? "Update User" : "Create User" ?></button>
            <a href="manage_users.php" style="margin-left:10px;">Back to Users</a>
        </form>
    </div>
</body>
</html>
