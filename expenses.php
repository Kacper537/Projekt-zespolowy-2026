<?php
require_once 'database.php';
session_start();

if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
$user_id = $_SESSION['user_id'];

// Dodawanie wydatku
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
    $amount = floatval($_POST['amount']);
    $category = $_POST['category'];
    $date = $_POST['date'];
    $description = trim($_POST['description'] ?? '');

    if ($amount > 0 && !empty($category) && !empty($date)) {
        $stmt = $db->prepare("INSERT INTO expenses (user_id, amount, category, date, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $amount, $category, $date, $description]);
    }
    header("Location: expenses.php");
    exit;
}

// Usuwanie wydatku
if (isset($_GET['delete'])) {
    $id_to_delete = intval($_GET['delete']);
    // Bezpieczeństwo: sprawdzamy, czy wydatek należy do zalogowanego użytkownika
    $stmt = $db->prepare("DELETE FROM expenses WHERE id = ? AND user_id = ?");
    $stmt->execute([$id_to_delete, $user_id]);
    header("Location: expenses.php");
    exit;
}

// Filtrowanie
$filter_cat = $_GET['filter_category'] ?? '';
$query = "SELECT * FROM expenses WHERE user_id = ?"; // Tutaj dodano brakujący znak $
$params = [$user_id];

if (!empty($filter_cat)) {
    $query .= " AND category = ?";
    $params[] = $filter_cat;
}
$query .= " ORDER BY date DESC, id DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$expenses = $stmt->fetchAll();

$categories = ['Jedzenie', 'Transport', 'Rachunki', 'Rozrywka', 'Studia', 'Inne'];
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Wydatki</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Twój portfel (Witaj, <?= htmlspecialchars($_SESSION['username']) ?>)</a>
        <div class="navbar-nav">
            <a class="nav-link" href="dashboard.php">Panel główny</a>
            <a class="nav-link active" href="expenses.php">Wydatki</a>
            <a class="nav-link" href="income.php">Przychody</a>
            <a class="nav-link" href="budgets.php">Budżety</a>
            <a class="nav-link text-danger" href="logout.php">Wyloguj</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card p-3 shadow-sm">
                <h5>Dodaj wydatek</h5>
                <form method="POST">
                    <input type="hidden" name="add_expense" value="1">
                    <div class="mb-2">
                        <label class="form-label">Kwota (zł)</label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Kategoria</label>
                        <select name="category" class="form-control" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat ?>"><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Data</label>
                        <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Opis</label>
                        <input type="text" name="description" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Dodaj</button>
                </form>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card p-3 shadow-sm mb-3">
                <form method="GET" class="row g-2 align-items-center">
                    <div class="col-auto"><h6>Filtruj wg kategorii:</h6></div>
                    <div class="col-auto">
                        <select name="filter_category" class="form-select">
                            <option value="">Wszystkie</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat ?>" <?= $filter_cat === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto"><button type="submit" class="btn btn-secondary btn-sm">Filtruj</button></div>
                </form>
            </div>

            <div class="card p-3 shadow-sm">
                <table class="table table-striped">
                    <thead><tr><th>Data</th><th>Kategoria</th><th>Kwota</th><th>Opis</th><th>Akcja</th></tr></thead>
                    <tbody>
                        <?php foreach ($expenses as $e): ?>
                        <tr>
                            <td><?= $e['date'] ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($e['category']) ?></span></td>
                            <td><strong><?= number_format($e['amount'], 2, ',', ' ') ?> zł</strong></td>
                            <td><?= htmlspecialchars($e['description']) ?></td>
                            <td><a href="expenses.php?delete=<?= $e['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Na pewno usunąć?')">Usuń</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>