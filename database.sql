CREATE DATABASE IF NOT EXISTS budget_planner
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE budget_planner;

CREATE TABLE users (
id INT AUTO_INCREMENT PRIMARY KEY,
username VARCHAR(50) NOT NULL UNIQUE,
password VARCHAR(255) NOT NULL
);

CREATE TABLE expenses (
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT NOT NULL,
amount DECIMAL(10,2) NOT NULL,
category VARCHAR(50) NOT NULL,
date DATE NOT NULL,
description TEXT,

CONSTRAINT fk_expenses_user
    FOREIGN KEY (user_id)
    REFERENCES users(id)
    ON DELETE CASCADE

);

CREATE TABLE incomes (
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT NOT NULL,
amount DECIMAL(10,2) NOT NULL,
source VARCHAR(100) NOT NULL,
date DATE NOT NULL,
description TEXT,

CONSTRAINT fk_incomes_user
    FOREIGN KEY (user_id)
    REFERENCES users(id)
    ON DELETE CASCADE

);

CREATE TABLE budgets (
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT NOT NULL,
category VARCHAR(50) NOT NULL,
amount_limit DECIMAL(10,2) NOT NULL,

CONSTRAINT fk_budgets_user
    FOREIGN KEY (user_id)
    REFERENCES users(id)
    ON DELETE CASCADE,

UNIQUE KEY unique_budget (user_id, category)

);
