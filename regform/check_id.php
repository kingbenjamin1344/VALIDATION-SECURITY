<?php
$servername = "localhost";
$username = "root"; // your db username
$password = ""; // your db password
$dbname = "it107_security_sql"; // your db name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['id_no'])) {
    $inputIdNo = $_GET['id_no'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id_no = ?");
    $stmt->bind_param("s", $inputIdNo); // 
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "exists";
    } else {
        echo "available";
    }
    
    $stmt->close();
}
$conn->close();
?>
