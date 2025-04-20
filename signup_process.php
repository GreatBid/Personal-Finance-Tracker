<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $account_type = $_POST['account_type'];
    $balance = $_POST['balance'];
    
    // Generate default account name based on account type
    $account_name = $account_type . " Account";
    
    // Hash the password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Check if the user already exists
    $sql = "SELECT UserID, PasswordHash FROM Users WHERE Email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // User exists, verify password
        $user = $result->fetch_assoc();
        if (!password_verify($password, $user['PasswordHash'])) {
            $_SESSION['error'] = "Incorrect password for existing user.";
            header("Location: index.php");
            exit();
        }
        $user_id = $user['UserID'];
    } else {
        // User does not exist, create a new user
        $sql = "INSERT INTO Users (Username, PasswordHash, Email) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $username, $password_hash, $email);
        
        if ($stmt->execute()) {
            $user_id = $conn->insert_id;
        } else {
            $_SESSION['error'] = "Error creating user: " . $stmt->error;
            header("Location: index.php");
            exit();
        }
    }
    
    // Insert account
    $sql = "INSERT INTO Accounts (UserID, AccountName, AccountType, Balance) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issd", $user_id, $account_name, $account_type, $balance);
    
    if ($stmt->execute()) {
        // Set session variables
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['account_type'] = $account_type; // Store account type in session
        
        // Redirect to dashboard
        header("Location: dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "Error creating account: " . $stmt->error;
        header("Location: index.php");
        exit();
    }
}
?>