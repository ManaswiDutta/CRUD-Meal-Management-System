<?php
// Define your character set
$charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*().';

$host = 'localhost';      
$user = 'root';           
$pass = '';              
$dbname = 'reverse';    

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Set the desired maximum string length
// $maxLength = 1; // Start with 1 and increase as needed

function generate($charset, $prefix, $maxLength) {
    if ($maxLength == 0) {
        return $prefix;
    }
    $length = strlen($charset);
    for ($i = 0; $i < $length; $i++) {
        generate($charset, $prefix . $charset[$i], $maxLength - 1);
    }
}

// To generate all possible strings of length 1:
generate($charset, '', 1);

// To generate for longer strings, increase the maxLength:
// generate($charset, '', 2); // For length 2

while (true) {
    // Infinite loop to keep generating longer strings
    static $currentLength = 2;
    echo generate($charset, '', $currentLength);
    $currentLength++;
}
?>
