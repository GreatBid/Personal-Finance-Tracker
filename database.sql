 CREATE DATABASE PersonalFinanceDB;


USE PersonalFinanceDB;


CREATE TABLE Users (
    UserID INT PRIMARY KEY AUTO_INCREMENT,
    Username VARCHAR(50) NOT NULL UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL,
    Email VARCHAR(100) NOT NULL UNIQUE,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE Accounts (
    AccountID INT PRIMARY KEY AUTO_INCREMENT,
    UserID INT,
    AccountName VARCHAR(100) NOT NULL,
    AccountType ENUM('Checking', 'Savings', 'Credit', 'Investment') NOT NULL,
    Balance DECIMAL(10, 2) NOT NULL CHECK (Balance >= 0),
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE CASCADE  -- Corrected foreign key reference
);


CREATE TABLE Transactions (
    TransactionID INT PRIMARY KEY AUTO_INCREMENT,
    AccountID INT,
    TransactionDate DATE NOT NULL,
    Amount DECIMAL(10, 2) NOT NULL CHECK (Amount != 0),
    TransactionType ENUM('Income', 'Expense') NOT NULL,
    Description VARCHAR(255),
    FOREIGN KEY (AccountID) REFERENCES Accounts(AccountID) ON DELETE CASCADE
);