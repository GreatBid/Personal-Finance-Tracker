<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's accounts filtered by account type if set in session
$account_type = isset($_SESSION['account_type']) ? $_SESSION['account_type'] : null;

if ($account_type) {
    $sql = "SELECT * FROM Accounts WHERE UserID = ? AND AccountType = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $account_type);
} else {
    $sql = "SELECT * FROM Accounts WHERE UserID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$accounts_result = $stmt->get_result();
$accounts = [];
while ($row = $accounts_result->fetch_assoc()) {
    $accounts[] = $row;
}

// Default to first account if none selected
$selected_account_id = isset($_GET['account_id']) ? $_GET['account_id'] : null;

if (!$selected_account_id && count($accounts) > 0) {
    $selected_account_id = $accounts[0]['AccountID'];
}

// Get transactions for selected account
$transactions = [];
$daily_income = [];
$daily_expense = [];
$monthly_summary = [];
$account = null;

if ($selected_account_id) {
    // Get account details
    $sql = "SELECT * FROM Accounts WHERE AccountID = ? AND UserID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $selected_account_id, $user_id);
    $stmt->execute();
    $account_result = $stmt->get_result();
    $account = $account_result->fetch_assoc();
    
    // Get transactions
    $sql = "SELECT * FROM Transactions WHERE AccountID = ? ORDER BY TransactionDate DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $selected_account_id);
    $stmt->execute();
    $transactions_result = $stmt->get_result();
    
    while ($row = $transactions_result->fetch_assoc()) {
        $transactions[] = $row;
        
        // Extract date and month for charting
        $date = new DateTime($row['TransactionDate']);
        $day = $date->format('Y-m-d');
        $month_year = $date->format('M Y');
        
        // Initialize arrays if not already set
        if (!isset($daily_income[$day])) {
            $daily_income[$day] = 0;
        }
        if (!isset($daily_expense[$day])) {
            $daily_expense[$day] = 0;
        }
        if (!isset($monthly_summary[$month_year])) {
            $monthly_summary[$month_year] = [
                'income' => 0,
                'expense' => 0
            ];
        }
        
        // Aggregate daily and monthly data
        if ($row['TransactionType'] == 'Income') {
            $daily_income[$day] += $row['Amount'];
            $monthly_summary[$month_year]['income'] += $row['Amount'];
        } else {
            $daily_expense[$day] += $row['Amount'];
            $monthly_summary[$month_year]['expense'] += $row['Amount'];
        }
    }
    
    // Sort by date
    ksort($daily_income);
    ksort($daily_expense);
    ksort($monthly_summary);
}

// Add transaction if form submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_transaction'])) {
    $account_id = $_POST['account_id'];
    $amount = $_POST['amount'];
    $type = $_POST['type'];
    $description = $_POST['description'];
    $date = $_POST['date'];
    
    // Ensure amount is positive
    $amount = abs($amount);
    
    // Insert transaction
    $sql = "INSERT INTO Transactions (AccountID, TransactionDate, Amount, TransactionType, Description) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isdss", $account_id, $date, $amount, $type, $description);
    
    if ($stmt->execute()) {
        // Update account balance
        $balance_change = ($type == 'Income') ? $amount : -$amount;
        $sql = "UPDATE Accounts SET Balance = Balance + ? WHERE AccountID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("di", $balance_change, $account_id);
        $stmt->execute();
        
        // Redirect to refresh page
        header("Location: dashboard.php?account_id=" . $account_id);
        exit();
    } else {
        $error = "Error adding transaction: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Personal Finance Tracker</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Personal Finance Tracker</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php" class="btn btn-small">Logout</a>
            </div>
        </header>
        
        <div class="dashboard-content">
            <aside class="sidebar">
                <h2>Your Accounts</h2>
                <ul class="account-list">
                    <?php foreach ($accounts as $acc): ?>
                    <li class="<?php echo ($selected_account_id == $acc['AccountID']) ? 'active' : ''; ?>">
                        <a href="dashboard.php?account_id=<?php echo $acc['AccountID']; ?>">
                            <div class="account-name"><?php echo htmlspecialchars($acc['AccountName']); ?></div>
                            <div class="account-balance">
                                <p>Total Balance:</p>
                                ₹<?php echo number_format($acc['Balance'], 2); ?>
                            </div>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                
                <div class="add-account">
                    <a href="add_account.php" class="btn">Add New Account</a>
                </div>
            </aside>
            
            <main class="main-content">
                <?php if (isset($account)): ?>
                <!-- Account details and transactions -->
                <div class="account-header">
                    <h2><?php echo htmlspecialchars($account['AccountName']); ?></h2>
                    <div class="account-balance">Balance: ₹<?php echo number_format($account['Balance'], 2); ?></div>
                </div>
                
                <!-- Income vs Expense Chart -->
                <div class="charts-container">
                    <div class="chart-wrapper">
                        <h3>Daily Income vs. Expenses</h3>
                        <canvas id="incomeExpenseChart"></canvas>
                    </div>
                    
                    <!-- Monthly Summary Table -->
                    <div class="monthly-summary">
                        <h3>Monthly Summary</h3>
                        <table class="summary-table">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Total Income</th>
                                    <th>Total Expenses</th>
                                    <th>Net</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($monthly_summary as $month => $data): ?>
                                <tr>
                                    <td><?php echo $month; ?></td>
                                    <td class="income">₹<?php echo number_format($data['income'], 2); ?></td>
                                    <td class="expense">₹<?php echo number_format($data['expense'], 2); ?></td>
                                    <td class="<?php echo ($data['income'] - $data['expense'] >= 0) ? 'income' : 'expense'; ?>">
                                        ₹<?php echo number_format($data['income'] - $data['expense'], 2); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Transactions section with add transaction form -->
                <div class="transactions-section">
                    <div class="transactions-header">
                        <h3>Recent Transactions</h3>
                        <button class="btn" onclick="toggleTransactionForm()">Add Transaction</button>
                    </div>
                    
                    <?php if (isset($error)): ?>
                        <div class="error-message"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                 <!-- In dashboard.php, find the transaction form and modify it -->

<!-- Transaction form (hidden by default) -->
<div id="transaction-form" class="transaction-form" style="display: none;">
    <form action="dashboard.php" method="post" id="transaction-form-element">
        <input type="hidden" name="account_id" value="<?php echo $selected_account_id; ?>">
        <input type="hidden" id="current-balance" value="<?php echo isset($account) ? $account['Balance'] : 0; ?>">
        
        <div id="balance-warning" class="warning-message" style="display: none;">
            Warning: This expense exceeds your current balance. Your account will go negative if you proceed.
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="date">Date</label>
                <input type="date" id="date" name="date" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-group">
                <label for="amount">Amount</label>
                <input type="number" id="amount" name="amount" step="0.01" min="0.01" required>
            </div>
            <div class="form-group">
                <label for="type">Type</label>
                <select id="type" name="type" required>
                    <option value="Income">Income</option>
                    <option value="Expense">Expense</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <input type="text" id="description" name="description" required>
        </div>
        <div class="form-actions">
            <button type="submit" name="add_transaction" class="btn">Add Transaction</button>
            <button type="button" class="btn btn-secondary" onclick="toggleTransactionForm()">Cancel</button>
        </div>
    </form>
</div>

<!-- Add this JavaScript after the transaction form -->
<script>
    // Add this JavaScript to handle date validation and balance warning
    document.addEventListener('DOMContentLoaded', function() {
        validateTransactionDate();
        setupBalanceValidation();
    });

    function validateTransactionDate() {
        const dateInput = document.getElementById('date');
        if (dateInput) {
            const today = new Date();
            const formattedToday = today.toISOString().split('T')[0]; // Format: YYYY-MM-DD
            
            // Set the max attribute to today
            dateInput.setAttribute('max', formattedToday);
            
            // Add event listener to validate when the date changes
            dateInput.addEventListener('change', function() {
                if (this.value > formattedToday) {
                    alert("You cannot select a future date for transactions.");
                    this.value = formattedToday; // Reset to today if a future date is selected
                }
            });
        }
    }

    function setupBalanceValidation() {
        const amountInput = document.getElementById('amount');
        const typeSelect = document.getElementById('type');
        const balanceWarning = document.getElementById('balance-warning');
        const currentBalanceInput = document.getElementById('current-balance');
        
        // Function to check if expense exceeds balance
        function validateExpenseAmount() {
            const amount = parseFloat(amountInput.value) || 0;
            const type = typeSelect.value;
            const currentBalance = parseFloat(currentBalanceInput.value) || 0;
            
            if (type === 'Expense' && amount > currentBalance) {
                balanceWarning.style.display = 'block';
            } else {
                balanceWarning.style.display = 'none';
            }
        }
        
        // Add event listeners to validate on input change
        if (amountInput && typeSelect) {
            amountInput.addEventListener('input', validateExpenseAmount);
            typeSelect.addEventListener('change', validateExpenseAmount);
        }

        // Also validate on form submission
        const form = document.getElementById('transaction-form-element');
        if (form) {
            form.addEventListener('submit', function(event) {
                validateExpenseAmount(); // Show warning if needed, but still allow submission
            });
        }
    }
</script>
                    <!-- Transactions table -->
                    <table class="transactions-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Type</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($transactions) > 0): ?>
                                <?php foreach ($transactions as $transaction): ?>
                                <tr class="<?php echo strtolower($transaction['TransactionType']); ?>">
                                    <td><?php echo date('M d, Y', strtotime($transaction['TransactionDate'])); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['Description']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['TransactionType']); ?></td>
                                    <td class="amount">
                                        <?php echo ($transaction['TransactionType'] == 'Income' ? '+' : '-'); ?>
                                        ₹<?php echo number_format(abs($transaction['Amount']), 2); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="no-transactions">No transactions found. Add your first transaction!</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php else: ?>
                <!-- No account selected or created yet -->
                <div class="no-account">
                    <p>Please select an account or create a new one.</p>
                    <a href="add_account.php" class="btn">Add New Account</a>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <script>
        // Toggle transaction form visibility
        function toggleTransactionForm() {
            const form = document.getElementById('transaction-form');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
        
        <?php if (isset($account) && (!empty($daily_income) || !empty($daily_expense))): ?>
        // Chart data preparation
        const days = <?php echo json_encode(array_keys(array_merge($daily_income, $daily_expense))); ?>;
        
        // Prepare income data matching the days array
        const incomeData = days.map(day => {
            return <?php echo json_encode($daily_income); ?>[day] || 0;
        });
        
        // Prepare expense data matching the days array
        const expenseData = days.map(day => {
            return <?php echo json_encode($daily_expense); ?>[day] || 0;
        });
        
        // Calculate net data (income - expense)
        const netData = days.map((day, index) => {
            return incomeData[index] - expenseData[index];
        });
        
        // Create income vs expense chart
        const ctx = document.getElementById('incomeExpenseChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: days,
                datasets: [
                    {
                        label: 'Income',
                        data: incomeData,
                        borderColor: '#4CAF50',
                        backgroundColor: 'rgba(76, 175, 80, 0.1)',
                        tension: 0.1,
                        fill: false
                    },
                    {
                        label: 'Expenses',
                        data: expenseData,
                        borderColor: '#F44336',
                        backgroundColor: 'rgba(244, 67, 54, 0.1)',
                        tension: 0.1,
                        fill: false
                    },
                    {
                        label: 'Net',
                        data: netData,
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        tension: 0.1,
                        fill: false,
                        borderWidth: 2,
                        borderDash: [5, 5]
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + value;
                            }
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
