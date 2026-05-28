<?php
require_once 'database.php';
session_start();

if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
$user_id = $_SESSION['user_id'];

// 1. Suma wydatków w tym miesiącu
$currentMonth = date('Y-m');

$stmt = $db->prepare("
SELECT SUM(amount) as total
FROM expenses
WHERE user_id = ?
AND date LIKE ?
");

$stmt->execute([$user_id, "$currentMonth%"]);

$totalMonth = $stmt->fetch()['total'] ?? 0;

// 2. Suma przychodów
$stmt = $db->prepare("
SELECT SUM(amount) as total_income
FROM incomes
WHERE user_id = ?
AND date LIKE ?
");

$stmt->execute([$user_id, "$currentMonth%"]);

$totalIncome = $stmt->fetch()['total_income'] ?? 0;

// 3. Bilans
$balance = $totalIncome - $totalMonth;


// 2. Ostatnie 5 transakcji
$stmt = $db->prepare("SELECT * FROM expenses WHERE user_id = ? ORDER BY date DESC, id DESC LIMIT 5");
$stmt->execute([$user_id]);
$recentExpenses = $stmt->fetchAll();

// 3. Dane do wykresu (Suma wg kategorii z tego miesiąca)
$stmt = $db->prepare("SELECT category, SUM(amount) as sum FROM expenses WHERE user_id = ? AND date LIKE ? GROUP BY category");
$stmt->execute([$user_id, "$currentMonth%"]);
$chartData = $stmt->fetchAll();

$categories = [];
$sums = [];
foreach ($chartData as $row) {
    $categories[] = $row['category'];
    $sums[] = $row['sum'];
}

// 4. Pobranie limitów budżetowych i obliczenie wykorzystania
// 4. Pobranie limitów budżetowych i obliczenie wykorzystania (Wersja dla MySQL)
$stmt = $db->prepare("SELECT b.category, b.amount_limit, COALESCE(SUM(e.amount), 0) as current_spent 
                      FROM budgets b 
                      LEFT JOIN expenses e ON b.category = e.category AND e.user_id = b.user_id AND e.date LIKE ?
                      WHERE b.user_id = ? 
                      GROUP BY b.category, b.amount_limit"); // W MySQL dobra praktyka nakazuje grupować po wszystkich pobieranych polach
$stmt->execute(["$currentMonth%", $user_id]);
$budgets = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Panel główny</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Twój portfel (Witaj, <?= htmlspecialchars($_SESSION['username']) ?>)</a>
        <div class="navbar-nav">
            <a class="nav-link active" href="dashboard.php">Panel główny</a>
            <a class="nav-link" href="expenses.php">Wydatki</a>
            <a class="nav-link" href="income.php">Przychody</a>
            <a class="nav-link" href="budgets.php">Budżety</a>
            <a class="nav-link text-danger" href="logout.php">Wyloguj</a>
        </div>
    </div>
</nav>

<div class="container">
<div class="row">   
<div class="col-md-4">
<div class="card <?= $balance >= 0 ? 'bg-success' : 'bg-danger' ?> text-white p-4 text-center">

    <h3>Bilans miesiąca</h3>

    <h2>
        <?= number_format($balance, 2, ',', ' ') ?> zł
    </h2>

    <p class="mt-2 mb-0">
        Przychody:
        <strong>
            <?= number_format($totalIncome, 2, ',', ' ') ?> zł
        </strong>
    </p>

    <p class="mb-0">
        Wydatki:
        <strong>
            <?= number_format($totalMonth, 2, ',', ' ') ?> zł
        </strong>
    </p>

</div> 
</div>   
<div class="col-md-8">
<div class="card p-3">
                <h5>Stan budżetów</h5>
                <?php foreach ($budgets as $b): 
                    $pct = $b['amount_limit'] > 0 ? ($b['current_spent'] / $b['amount_limit']) * 100 : 0;
                    $color = $pct > 100 ? 'bg-danger' : ($pct > 80 ? 'bg-warning' : 'bg-success');
                ?>
                    <div class="mb-2">
                        <small><?= htmlspecialchars($b['category']) ?> (<?= $b['current_spent'] ?> / <?= $b['amount_limit'] ?> zł)</small>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar <?= $color ?>" role="progressbar" style="width: <?= min($pct, 100) ?>%"><?= round($pct) ?>%</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
</div>   

</div>

</div>

    <div class="row p-3">
        <div class="col-md-6">
            <div class="card p-3 mb-4">
                <h5>Ostatnie transakcje</h5>
                <table class="table">
                    <thead><tr><th>Data</th><th>Kat.</th><th>Kwota</th></tr></thead>
                    <tbody>
                        <?php foreach ($recentExpenses as $e): ?>
                        <tr>
                            <td><?= $e['date'] ?></td>
                            <td><?= htmlspecialchars($e['category']) ?></td>
                            <td><strong><?= $e['amount'] ?> zł</strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card p-3 mb-4 align-items-center">
                <h5>Podział wydatków</h5>
                <div style="width: 300px; height: 300px;">
                    <canvas id="expenseChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Przekazanie danych z PHP do pliku JS przy pomocy globalnych zmiennych
    window.chartLabels = <?= json_encode($categories) ?>;
    window.chartData = <?= json_encode($sums) ?>;
</script>
<script src="charts.js"></script>
</body>
</html>