<?php
require_once 'database.php';
session_start();

if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
$user_id = $_SESSION['user_id'];

// Dodawanie/Aktualizacja limitu budżetowego
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = $_POST['category'];
    $limit = floatval($_POST['limit']);

    if (!empty($category) && $limit >= 0) {
        // Składnia INSERT OR REPLACE (unikalne dla SQLite, nadpisuje rekord jeśli kategoria już istnieje)
        $stmt = $db->prepare("INSERT INTO budgets (user_id, category, amount_limit) 
                      VALUES (?, ?, ?) 
                      ON DUPLICATE KEY UPDATE amount_limit = VALUES(amount_limit)");
        $stmt->execute([$user_id, $category, $limit]);
    }
    header("Location: budgets.php");
    exit;
}

// Pobranie aktualnych limitów użytkownika
$stmt = $db->prepare("SELECT * FROM budgets WHERE user_id = ?");
$stmt->execute([$user_id]);
$currentBudgets = $stmt->fetchAll();

$categories = ['Jedzenie', 'Transport', 'Rachunki', 'Rozrywka', 'Studia', 'Inne'];
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Limity Budżetowe</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">💰 Portfel</a>
        <div class="navbar-nav">
            <a class="nav-link" href="dashboard.php">Dashboard</a>
            <a class="nav-link" href="expenses.php">Wydatki</a>
            <a class="nav-link active" href="budgets.php">Budżety</a>
            <a class="nav-link text-danger" href="logout.php">Wyloguj</a>
        </div>
    </div>
</nav>

<div class="container" style="max-width: 600px;">
    <div class="card p-4 shadow-sm mb-4">
        <h5>Ustaw limit miesięczny</h5>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Kategoria</label>
                <select name="category" class="form-control" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat ?>"><?= $cat ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Limit (zł)</label>
                <input type="number" step="0.01" min="0" name="limit" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-success w-100">Zapisz limit</button>
        </form>
    </div>

    <div class="card p-4 shadow-sm">
        <h5>Zdefiniowane limity</h5>
        <ul class="list-group">
            <?php foreach ($currentBudgets as $b): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <?= htmlspecialchars($b['category']) ?>
                    <span class="badge bg-primary rounded-pill"><?= number_format($b['amount_limit'], 2, ',', ' ') ?> zł</span>
                </li>
            <?php endforeach; ?> <?php if(empty($currentBudgets)): ?>
                <p class="text-muted text-center mt-2">Brak ustawionych limitów.</p>
            <?php endif; ?>
        </ul>
    </div>
</div>
</body>
</html>