<?php
session_start();
include '../backend/config/db_connect.php';

// Access control: allow admin (3), super (2), or a student (1) editing own info
$role = isset($_SESSION['role_id']) ? (int)$_SESSION['role_id'] : null;
$user_session_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

if (!isset($user_session_id)) {
    header("Location: blocked.php");
    exit;
}

// Students can only edit their own profile via edit_id param
$requested_edit_id = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : null;
if (!in_array($role, [2,3]) && !($role == 1 && $requested_edit_id !== null && $user_session_id === $requested_edit_id)) {
    header("Location: blocked.php");
    exit;
}

$is_admin = $role === 3;
$is_super = $role === 2;
$is_student = $role === 1;

if ($is_student) {
    if (!isset($requested_edit_id) || $user_session_id !== $requested_edit_id) {
        die("Access denied. Students can only edit their own information.");
    }
}

$edit_mode = false;
$error = '';
$success = '';

$username = $email = '';
$role_id = 1; // default to student for safety

$roll_no = $department = $year = '';
$lunch_pref = $dinner_pref1 = $dinner_pref2 = '';

// If editing an existing user, load data
if ($requested_edit_id !== null) {
    $edit_id = $requested_edit_id;

    // fetch user row
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $user_result = $stmt->get_result();

    if ($user_result && $user_result->num_rows === 1) {
        $user = $user_result->fetch_assoc();

        // superintendents can only edit students
        if ($is_super && (int)$user['role_id'] !== 1) {
            die("Access denied. Superintendents can only edit students.");
        }

        $username = $user['username'];
        $email = $user['email'];
        $role_id = (int)$user['role_id'];
        $edit_mode = true;

        // fetch student_details if exist
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

        // fetch meal preferences if exist
        $stmt = $conn->prepare("SELECT * FROM meal_preference WHERE student_id = ?");
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $meal_result = $stmt->get_result();
        if ($meal_result && $meal_result->num_rows === 1) {
            $meal = $meal_result->fetch_assoc();
            // adapt to your column names
            $lunch_pref = isset($meal['lunch_preference']) ? $meal['lunch_preference'] : '';
            $dinner_pref1 = isset($meal['dinner_preference1']) ? $meal['dinner_preference1'] : '';
            $dinner_pref2 = isset($meal['dinner_preference2']) ? $meal['dinner_preference2'] : '';
        }
    } else {
        die("User not found.");
    }
} else {
    // if no edit_id and user is a superintendent, don't allow create
    if ($is_super) {
        die("Access denied. Superintendents cannot create new users.");
    }
}

// Handle POST submission (only editing allowed here; create_user.php handles creation)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $edit_mode) {
    // sanitize inputs
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    // If super, role stays student (1) so we force role_id to 1. If admin, take posted value.
    if ($is_super) {
        $role_id = 1;
    } else {
        // admin will post role_id
        $role_id = isset($_POST['role_id']) ? (int)$_POST['role_id'] : $role_id;
    }
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    // Student detail POSTs (only relevant if role_id == 1)
    $roll_no = isset($_POST['roll_no']) ? trim($_POST['roll_no']) : $roll_no;
    $department = isset($_POST['department']) ? trim($_POST['department']) : $department;
    $year = isset($_POST['year']) ? $_POST['year'] : $year;

    // Meal prefs: only honor if editor is super or the student themself
    if (($is_super || $is_student) && $role_id == 1) {
        $lunch_pref = isset($_POST['lunch_pref']) ? $_POST['lunch_pref'] : $lunch_pref;
        $dinner_pref1 = isset($_POST['dinner_pref1']) ? $_POST['dinner_pref1'] : $dinner_pref1;
        $dinner_pref2 = isset($_POST['dinner_pref2']) ? $_POST['dinner_pref2'] : $dinner_pref2;
    }

    // Validate basic fields
    if (empty($username) || empty($email)) {
        $error = "Username and email are required.";
    } else {
        // Update users table
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role_id = ?, password_hash = ? WHERE id = ?");
            $stmt->bind_param("ssisi", $username, $email, $role_id, $hashed_password, $edit_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role_id = ? WHERE id = ?");
            $stmt->bind_param("ssii", $username, $email, $role_id, $edit_id);
        }

        if (!$stmt->execute()) {
            $error = "Error updating user: " . $conn->error;
        } else {
            // If student, update / insert student_details
            if ($role_id == 1) {
                // check exists
                $stmt = $conn->prepare("SELECT id FROM student_details WHERE student_id = ?");
                $stmt->bind_param("i", $edit_id);
                $stmt->execute();
                $temp = $stmt->get_result();

                if ($temp && $temp->num_rows > 0) {
                    $stmt = $conn->prepare("UPDATE student_details SET roll_no = ?, department = ?, year = ? WHERE student_id = ?");
                    $stmt->bind_param("sssi", $roll_no, $department, $year, $edit_id);
                } else {
                    $stmt = $conn->prepare("INSERT INTO student_details (student_id, roll_no, department, year) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("isss", $edit_id, $roll_no, $department, $year);
                }
                if (!$stmt->execute()) {
                    $error = "Error updating student details: " . $conn->error;
                }

                // meal_preference update/insert — ONLY if editor is super or the student themself
                if (($is_super || $is_student)) {
                    $stmt = $conn->prepare("SELECT id FROM meal_preference WHERE student_id = ?");
                    $stmt->bind_param("i", $edit_id);
                    $stmt->execute();
                    $tmp2 = $stmt->get_result();

                    if ($tmp2 && $tmp2->num_rows > 0) {
                        $stmt = $conn->prepare("UPDATE meal_preference SET lunch_preference = ?, dinner_preference1 = ?, dinner_preference2 = ? WHERE student_id = ?");
                        $stmt->bind_param("sssi", $lunch_pref, $dinner_pref1, $dinner_pref2, $edit_id);
                    } else {
                        $stmt = $conn->prepare("INSERT INTO meal_preference (student_id, lunch_preference, dinner_preference1, dinner_preference2) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("isss", $edit_id, $lunch_pref, $dinner_pref1, $dinner_pref2);
                    }
                    if (!$stmt->execute()) {
                        $error = "Error updating meal preference: " . $conn->error;
                    }
                }
            } // end if role_id==1
        } // end users update success
    } // end validation

    if (!$error) {
        $success = "User updated successfully! Redirecting...";
        // redirect depending on editor role
        if ($is_admin) {
            header("Refresh: 2; url=manage_users.php");
        } elseif ($is_super) {
            header("Refresh: 2; url=super_dashboard.php");
        } elseif ($is_student) {
            header("Refresh: 2; url=student_dashboard.php");
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

        <?php if ($error): ?>
            <p style="color:red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p style="color:green;"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <h3>User Info</h3>
            <label>Username:</label><br>
            <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" required><br><br>

            <label>Email:</label><br>
            <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required><br><br>

            <label>Password <?= $edit_mode ? "(leave blank to keep current)" : "" ?>:</label><br>
            <input type="password" name="password"><br><br>

            <label>Role:</label><br>
            <?php if ($is_super || $is_student): ?>
                <!-- super cannot change role; keep hidden input so it posts -->
                <input type="hidden" name="role_id" value="1">
                <select disabled>
                    <option>Student</option>
                </select>
            <?php else: ?>
                <select name="role_id" required>
                    <option value="1" <?= ($role_id==1)?"selected":"" ?>>Student</option>
                    <?php if ($is_admin): ?>
                        <option value="2" <?= ($role_id==2)?"selected":"" ?>>Superintendent</option>
                        <option value="3" <?= ($role_id==3)?"selected":"" ?>>Admin</option>
                    <?php endif; ?>
                </select>
            <?php endif; ?>
            <br><br>

            <?php if ($is_admin || $is_student): ?>
                <h3>Student Details</h3>
                <label>Roll No:</label><br>
                <input type="text" name="roll_no" value="<?= htmlspecialchars($roll_no) ?>" required><br><br>

                <label>Department:</label><br>
                <input type="text" name="department" value="<?= htmlspecialchars($department) ?>"><br><br>

                <label>Year:</label><br>
                <select name="year" required>
                    <?php 
                    $years = ['UG1','UG2','UG3','UG4','PG1','PG2'];
                    foreach ($years as $y): ?>
                        <option value="<?= $y ?>" <?= ($year==$y)?"selected":"" ?>><?= $y ?></option>
                    <?php endforeach; ?>
                </select><br><br>

                <h3>Meal Preferences</h3>
                <?php if ($is_super || $is_student): ?>
                    <label>Lunch:</label><br>
                    <select name="lunch_pref" required>
                        <?php $lunch_options = ['Veg','Non-Veg'];
                        foreach ($lunch_options as $opt): ?>
                            <option value="<?= $opt ?>" <?= ($lunch_pref==$opt)?"selected":"" ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select><br><br>

                    <label>Dinner 1:</label><br>
                    <select name="dinner_pref1" required>
                        <?php $dinner1_options = ['Veg','Non-Veg'];
                        foreach ($dinner1_options as $opt): ?>
                            <option value="<?= $opt ?>" <?= ($dinner_pref1==$opt)?"selected":"" ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select><br><br>

                    <label>Dinner 2:</label><br>
                    <select name="dinner_pref2" required>
                        <?php $dinner2_options = ['Rice','Roti'];
                        foreach ($dinner2_options as $opt): ?>
                            <option value="<?= $opt ?>" <?= ($dinner_pref2==$opt)?"selected":"" ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select><br><br>
                <?php else: ?>
                    <p><strong>Lunch:</strong> <?= htmlspecialchars($lunch_pref) ?></p>
                    <p><strong>Dinner 1:</strong> <?= htmlspecialchars($dinner_pref1) ?></p>
                    <p><strong>Dinner 2:</strong> <?= htmlspecialchars($dinner_pref2) ?></p>
                <?php endif; ?>
            <?php endif; ?>

            <button type="submit"><?= $edit_mode ? "Update User" : "Create User" ?></button>
            <?php
            // Dynamic back link based on role
            if ($is_admin) {
                $back_link = "manage_users.php";
            } elseif ($is_super) {
                $back_link = "super_dashboard.php";
            } elseif ($is_student) {
                $back_link = "student_dashboard.php";
            } else {
                $back_link = "login.php";
            }
            ?>
            <a href="<?= $back_link ?>" style="margin-left:10px;">Back</a>

        </form>
    </div>
</body>
</html>
