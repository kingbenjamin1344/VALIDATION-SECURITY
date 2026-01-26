<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="../css/main.forgotpass.css">
</head>
<body>
    <div class="main-content">
        <div class="navbar">
            <div class="container flex">
                <h1 class="logo">Leave Management System</h1>
                <nav></nav>
            </div>
        </div>

        <section class="showcase">
            <div class="container grid">
                <div></div>

                <div class="showcase-form card">
                    <div style="text-align:center;margin-top:5px;">
                        <img src="../picture/ofice.png" alt="illustration" style="width:110px;display:block;margin:10px auto 5px;">
                        <h2>Forgot Password?</h2>
                        <p style="color:#333333;font-size:14px;margin-bottom:10px;">Search your registered email for authentication</p>
                    </div>

                    <form method="post" action="..//.php">
                        <div class="form-control">
                            <input type="email" name="email" placeholder="Enter your email" required>
                        </div>

                        <div style="text-align:center;margin-top:10px;">
                            <input type="submit" class="btn" value="Continue">
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>

    <div id="footer"><p></p></div>

</body>
</html>



