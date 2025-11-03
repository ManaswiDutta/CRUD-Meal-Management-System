<?php
include 'backend/config/db_connect.php';

$students = [
    ['Tapobrata Nanda', '293', 'PHSA', 'UG-1', 'vivek'],
];

foreach ($students as $s) {
    [$name, $roll, $dept, $year, $hostel] = $s;

    // Insert into users table
    $email = strtolower(str_replace(' ', '', $name)) . "@example.com";
    $password = password_hash('1234', PASSWORD_DEFAULT); // default password
    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role_id) VALUES (?, ?, ?, 1)");
    $stmt->bind_param("sss", $name, $email, $password);
    $stmt->execute();
    $student_id = $conn->insert_id;

    // Insert into student_details
    $stmt = $conn->prepare("INSERT INTO student_details (student_id, roll_no, department, year) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $student_id, $roll, $dept, $year);
    $stmt->execute();

    // Insert into student_class (marking Physics = 1)
    $stmt = $conn->prepare("INSERT INTO student_class (student_id, physics, chemistry) VALUES (?, 1, 0)");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
}

echo "✅ All student records inserted successfully!";
?>
