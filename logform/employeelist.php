<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../css/main.index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
        }

        /* Left Sidebar */
        .left-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 220px;
            height: 100%;
            background-color: #1E90FF;
            color: white;
            padding-top: 60px;
            box-sizing: border-box;
        }

        .left-sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .left-sidebar ul li {
            padding: 15px 20px;
        }

        .left-sidebar ul li a {
            color: white;
            text-decoration: none;
            font-size: 18px;
            display: block;
        }

        .left-sidebar ul li a:hover {
            background-color: rgba(255,255,255,0.2);
            border-radius: 5px;
        }

        /* Top Navbar */
        .top-navbar {
            position: fixed;
            top: 0;
            left: 220px;
            right: 0;
            height: 60px;
            background-color: #1E90FF;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 0 20px;
            box-sizing: border-box;
            z-index: 999;
            gap: 18px;
        }

        .navbar-title {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            font-size: 20px;
            font-weight: bold;
            color: white;
        }

        .settings-icon,
        .logout-icon {
            font-size: 22px;
            cursor: pointer;
            color: white;
            transition: 0.2s ease;
        }

        .logout-icon:hover {
            color: #ff4d4d;
            transform: scale(1.1);
        }

        .settings-icon:hover {
            transform: rotate(90deg);
        }

        /* Main */
        main {
            margin-left: 220px;
            padding: 90px 20px 80px 20px;
            width: 100%;
            background: #f5f7fb;
            min-height: 100vh;
        }

        /* Dashboard Cards */
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 12px rgba(0,0,0,0.1);
            text-align: center;
            transition: 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
        }

        .card h2 {
            margin: 0;
            font-size: 34px;
            color: #1E90FF;
        }

        .card p {
            margin: 5px 0 0;
            color: #555;
            font-size: 16px;
        }

        /* Right Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            right: -300px;
            width: 300px;
            height: 100%;
            background-color: #5a6268;
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

        .sidebar .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            cursor: pointer;
        }

        /* Footer */
        #footer {
            position: fixed;
            bottom: 0;
            left: 220px;
            width: calc(100% - 220px);
            background-color: #1E90FF;
            color: white;
            text-align: center;
            padding: 10px 0;
        }

        /* ================= MODAL ================= */

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(6px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }

        .modal-box {
            background: white;
            padding: 25px 30px;
            border-radius: 12px;
            width: 320px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.25);
            animation: scaleIn 0.25s ease;
        }

        .modal-box h3 {
            margin: 0;
            color: #1E90FF;
        }

        .modal-box p {
            margin: 15px 0 25px;
            color: #555;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
        }

        .btn-confirm {
            flex: 1;
            background: #28a745;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn-confirm:hover {
            background: #218838;
        }

        .btn-cancel {
            flex: 1;
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn-cancel:hover {
            background: #c82333;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0.9);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>
</head>

<body>

    <!-- Left Sidebar -->
    <div class="left-sidebar">
        <ul>
            <li><a href="../logform/indexes.php">Dashboard</a></li>
            <li><a href="../logform/addaccount.php">Add Account</a></li>
            <li><a href="../logform/employeelist.php">Employee List</a></li>
        </ul>
    </div>

    <!-- Top Navbar -->
    <div class="top-navbar">
        <span class="settings-icon" onclick="toggleSidebar()">&#9881;</span>
        <div class="navbar-title">Leave Management</div>
        <span class="logout-icon" onclick="logout()">
            <i class="fa-solid fa-right-from-bracket"></i>
        </span>
    </div>

    <!-- Main Content -->
    <main>
      
    </main>

    <!-- Right Sidebar -->
    <div class="sidebar" id="sidebar">
        <span class="close-btn" onclick="toggleSidebar()">&times;</span>
        <ul>
            <li><a href="#">Change Password</a></li>
            <li><a href="../security/security_question.php">Set Security</a></li>
        </ul>
    </div>

    <!-- Footer -->
    <div id="footer">
        <p>@South Loan & Finance Company Inc. 2024</p>
    </div>

    <!-- Logout Modal -->
    <div id="logoutModal" class="modal-overlay">
        <div class="modal-box">
            <h3>Confirm Logout</h3>
            <p>Are you sure you want to logout?</p>
            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeLogoutModal()">Cancel</button>
                <button class="btn-confirm" onclick="confirmLogout()">Yes</button>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }

        function logout() {
            document.getElementById('logoutModal').style.display = "flex";
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').style.display = "none";
        }

        function confirmLogout() {
            window.location.href = "logout.php";
        }
    </script>

</body>
</html>
