

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../css/main.index.css">
    <style>
        .sidebar {
            position: fixed;
            top: 0;
            right: -300px;
            width: 300px;
            height: 100%;
            background-color: #333;
            color: white;
            transition: right 0.3s;
            z-index: 1000;
            padding: 20px;
            box-sizing: border-box;
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
            color: white;
            text-decoration: none;
            font-size: 18px;
        }
        .sidebar ul li a:hover {
            text-decoration: underline;
        }
        .sidebar .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            cursor: pointer;
        }
        .settings-icon {
            font-size: 24px;
            cursor: pointer;
            margin-left: auto;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="container flex">
            <h1 class="logo">Leave Management System</h1>
            <div class="settings-icon" onclick="toggleSidebar()">&#9881;</div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <span class="close-btn" onclick="toggleSidebar()">&times;</span>
        <ul>
            <li><a href="#">Change Password</a></li>
            <li><a href="#">Leave Request</a></li>
            <li><a href="#">Leave Request History</a></li>
            <li><a href="../security/security_question.php">Set Security</a></li>
            <li><a href="logout.php">Logout</a></li>
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
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }
    </script>
    <script src="../jsform/login.js"></script>
</body>
</html>
