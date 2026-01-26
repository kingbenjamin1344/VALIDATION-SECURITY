<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

include '../regform/config.php';
$user_id = $_SESSION['user_id'];
$sql = "SELECT id FROM user_security_questions WHERE user_id = '$user_id'";
$result = mysqli_query($conn, $sql);
$has_security = mysqli_num_rows($result) > 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../css/main.index.css">
    <style>
        .settings-icon {
            font-size: 24px;
            cursor: pointer;
            margin-left: 20px;
        }
        .sidebar {
            position: fixed;
            top: 0;
            right: -300px;
            width: 300px;
            height: 100%;
            background-color: #fff;
            box-shadow: -2px 0 5px rgba(0,0,0,0.5);
            transition: right 0.3s;
            z-index: 1000;
            padding: 20px;
        }
        .sidebar.open {
            right: 0;
        }
        .sidebar ul {
            list-style: none;
            padding: 0;
        }
        .sidebar ul li {
            margin: 20px 0;
        }
        .sidebar ul li a {
            text-decoration: none;
            color: #333;
            font-size: 18px;
        }
        .sidebar .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            cursor: pointer;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
        }
        .modal.show {
            display: flex;
        }
    </style>
</head>
<body>
    <!-- Modal for security questions -->
    <div class="modal <?php echo !$has_security ? 'show' : ''; ?>" id="securityModal">
        <div class="modal-content">
            <h2>Set Security Questions</h2>
            <p>To secure your account, please set your security questions.</p>
            <button onclick="window.location.href='../stored_answer/security_question.php'">Set Now</button>
        </div>
    </div>
    <!-- Navbar -->
    <div class="navbar">
        <div class="container flex">
            <h1 class="logo">Leave Management System</h1>
            <nav>
                <ul>
                    <li><a href="#">Profile</a></li>
                    <li><span class="settings-icon" onclick="toggleSidebar()">&#9881;</span></li>
                    <li><a href="../logform/login.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <span class="close-btn" onclick="toggleSidebar()">&times;</span>
        <ul>
            <li><a href="#">Profile</a></li>
            <li><a href="#">Change Password</a></li>
            <li><a href="#">Leave Remaining</a></li>
            <li><a href="#">Leave Request</a></li>
            <li><a href="../stored_answer/security_question.php">Set Security Questions</a></li>
            <li><a href="../logform/login.php">Logout</a></li>
        </ul>
    </div>

    <!-- Showcase -->
    <section class="showcase">
        <div class="container grid">
            <div class="showcase-text">
                <h1>South Loan & Finance Company Inc.</h1>
                <p>In this website we will accommodate South Loan & Finance Company Inc. employees' leave requests through a system.</p>
            </div>
        </div>
    </section>

    <div id="footer">
        <p>@South Loan & Finance Company Inc. 2024</p>
    </div>

    <script>
        function toggleSidebar() {
            var sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }
    </script>
    <script src="../jsform/login.js"></script>
</body>
</html>
