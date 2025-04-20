<?php
session_start();
// Redirect to dashboard if already logged in
if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Finance Tracker</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Inline critical centering styles to ensure they take effect */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        
        body {
            display: flex;
            flex-direction: column;
            min-height: 100%;
        }
        
        .index-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 20px;
            box-sizing: border-box;
        }
        
        .index-container h1 {
            text-align: center;
            margin-bottom: 2rem;
            color: #2c3e50;
        }
        
        .auth-container {
            width: 100%;
            max-width: 500px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
    </style>
</head>
<body>
    <div class="index-container">
        <h1>Personal Finance Tracker</h1>
        <div class="auth-container">
            <div class="tabs">
                <button class="tab-btn active" onclick="showTab('login')">Login</button>
                <button class="tab-btn" onclick="showTab('signup')">Sign Up</button>
            </div>
            
            <div id="login" class="tab-content">
                <form action="login_process.php" method="post">
                    <div class="form-group">
                        <label for="login-username">Username</label>
                        <input type="text" id="login-username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="login-password">Password</label>
                        <input type="password" id="login-password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="login-account-type">Account Type</label>
                        <select id="login-account-type" name="account_type" required>
                            <option value="Checking">Checking</option>
                            <option value="Savings">Savings</option>
                            <option value="Credit">Credit</option>
                            <option value="Investment">Investment</option>
                        </select>
                    </div>
                    <button type="submit" class="btn">Login</button>
                </form>
            </div>
            
            <div id="signup" class="tab-content" style="display: none;">
                <form action="signup_process.php" method="post">
                    <div class="form-group">
                        <label for="signup-username">Username</label>
                        <input type="text" id="signup-username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="signup-email">Email</label>
                        <input type="email" id="signup-email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="signup-password">Password</label>
                        <input type="password" id="signup-password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="account-type">Account Type</label>
                        <select id="account-type" name="account_type" required>
                            <option value="Checking">Checking</option>
                            <option value="Savings">Savings</option>
                            <option value="Credit">Credit</option>
                            <option value="Investment">Investment</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="initial-balance">Initial Balance</label>
                        <input type="number" id="initial-balance" name="balance" step="0.01" min="0" required>
                    </div>
                    <button type="submit" class="btn">Sign Up</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // Hide all tab contents
            const tabContents = document.getElementsByClassName('tab-content');
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].style.display = 'none';
            }
            
            // Remove active class from all tab buttons
            const tabButtons = document.getElementsByClassName('tab-btn');
            for (let i = 0; i < tabButtons.length; i++) {
                tabButtons[i].classList.remove('active');
            }
            
            // Show the selected tab content and mark its button as active
            document.getElementById(tabName).style.display = 'block';
            document.querySelector(`.tab-btn[onclick="showTab('${tabName}')"]`).classList.add('active');
        }
    </script>
</body>
</html>