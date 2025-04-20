<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $account_name = $_POST['account_name'];
    $account_type = $_POST['account_type'];
    $balance = $_POST['balance'];
    
    // Insert account
    $sql = "INSERT INTO Accounts (UserID, AccountName, AccountType, Balance) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issd", $user_id, $account_name, $account_type, $balance);
    
    if ($stmt->execute()) {
        $account_id = $conn->insert_id;
        header("Location: dashboard.php?account_id=" . $account_id);
        exit();
    } else {
        $error = "Error creating account: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Account - Personal Finance Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Add New Account</h1>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form action="add_account.php" method="post" class="form-card">
            <div class="form-group">
                <label for="account-name">Account Name</label>
                <input type="text" id="account-name" name="account_name" required>
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
            <div class="form-actions">
                <button type="submit" class="btn">Add Account</button>
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>

