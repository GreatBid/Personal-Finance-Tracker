# Personal-Finance-Tracker
A web-based application that helps users track their personal finances by managing multiple accounts, recording income and expenses, and visualizing financial data.

##Features

User Authentication: Secure signup and login system
Multiple Account Management: Create and manage various account types (Checking, Savings, Credit, Investment)
Transaction Tracking: Record income and expenses with dates and descriptions
Financial Visualization: View income vs. expenses charts to track financial health
Monthly Summary: See monthly financial performance at a glance
Responsive Design: Works on desktop and mobile devices

##Setup Instructions

Clone the repository
bashgit clone https://github.com/yourusername/personal-finance-tracker.git
cd personal-finance-tracker

Set up the database

Create a MySQL database
Import the database structure from database.sql

bashmysql -u username -p your_database_name < database.sql

Configure database connection

Edit db_connect.php with your database credentials:

php$servername = "localhost";
$username = "your_username";
$password = "your_password";
$dbname = "PersonalFinanceDB";

Deploy to web server

Copy all files to your web server's document root or subdirectory


Access the application

Navigate to the URL where you deployed the application
Example: http://localhost/personal-finance-tracker
