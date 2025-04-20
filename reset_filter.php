<?php
session_start();

// Remove account type filter
if (isset($_SESSION['account_type'])) {
    unset($_SESSION['account_type']);
}

// Redirect back to dashboard
header("Location: dashboard.php");
exit();
?>

