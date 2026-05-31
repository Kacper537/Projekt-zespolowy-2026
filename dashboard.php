<?php
require_once 'database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

/* =========================
   WYBÓR MIESIĄCA
========================= */
$selectedMonth = $_GET['month'] ?? date('Y-m');
$monthPattern = $selectedMonth . '%';

/* =========================
   WYDATKI
========================= */
$stmt = $db->prepare("
SELECT SUM(amount) as total
FROM expenses
WHERE user_id = ?
AND date LIKE ?
");

$stmt->execute([$user_id, $monthPattern]);
$totalMonth = $stmt->fetch()['total'] ?? 0;

/* =========================
   PRZYCHODY
========================= */
$stmt = $db->prepare("
SELECT SUM(amount) as total_income
FROM incomes
WHERE user_id = ?
AND date LIKE ?
");

$stmt->execute([$user_id, $monthPattern]);
$totalIncome = $stmt->fetch()['total_income'] ?? 0;

/* =========================
   BILANS
========================= */
$balance = $totalIncome - $totalMonth;

/* =========================
   OSTATNIE WYDATKI
========================= */
$stmt = $db->prepare("
SELECT * FROM expenses
WHERE user_id = ?
AND date LIKE ?
ORDER BY date DESC, id DESC
LIMIT 5
");

$stmt->execute([$user_id, $monthPattern]);
$recentExpenses = $stmt->fetchAll();

/* =========================
   WYDATKI - WYKRES
========================= */
$stmt = $db->prepare("
SELECT category, SUM(amount) as sum
FROM expenses
WHERE user_id = ?
AND date LIKE ?
GROUP BY category
");

$stmt->execute([$user_id, $monthPattern]);
$expenseChartData = $stmt->fetchAll();

$expenseCategories = [];
$expenseSums = [];

foreach ($expenseChartData as $row) {
    $expenseCategories[] = $row['category'];
    $expenseSums[] = $row['sum'];
}

/* =========================
   PRZYCHODY - WYKRES
========================= */
$stmt = $db->prepare("
SELECT source as category, SUM(amount) as sum
FROM incomes
WHERE user_id = ?
AND date LIKE ?
GROUP BY source
");

$stmt->execute([$user_id, $monthPattern]);
$incomeChartData = $stmt->fetchAll();

$incomeCategories = [];
$incomeSums = [];

foreach ($incomeChartData as $row) {
    $incomeCategories[] = $row['category'];
    $incomeSums[] = $row['sum'];
}

/* =========================
   BUDŻETY
========================= */
$stmt = $db->prepare("
SELECT b.category, b.amount_limit,
COALESCE(SUM(e.amount), 0) as current_spent
FROM budgets b
LEFT JOIN expenses e
ON b.category = e.category
AND e.user_id = b.user_id
AND e.date LIKE ?
WHERE b.user_id = ?
GROUP BY b.category, b.amount_limit
");

$stmt->execute([$monthPattern, $user_id]);
$budgets = $stmt->fetchAll();

/* =========================
   HISTORIA
========================= */
$stmt = $db->prepare("
SELECT 'expense' as type, amount, category as name, date, description
FROM expenses
WHERE user_id = ?
AND date LIKE ?

UNION ALL

SELECT 'income' as type, amount, source as name, date, description
FROM incomes
WHERE user_id = ?
AND date LIKE ?

ORDER BY date DESC
LIMIT 20
");

$stmt->execute([$user_id, $monthPattern, $user_id, $monthPattern]);
$history = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<title>Dashboard</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
<div class="container">

<a class="navbar-brand" href="dashboard.php">
Twój portfel (<?= htmlspecialchars($_SESSION['username']) ?>)
</a>

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

<!-- WYBÓR MIESIĄCA -->
<form method="GET" class="card p-3 mb-3">
<div class="row align-items-center">

<div class="col-auto">
<strong>Miesiąc:</strong>
</div>

<div class="col-auto">
<input type="month"
name="month"
value="<?= $selectedMonth ?>"
class="form-control"
onchange="this.form.submit()">
</div>

</div>
</form>

<!-- BILANS -->
<div class="row">

<div class="col-md-4">
<div class="card <?= $balance >= 0 ? 'bg-success' : 'bg-danger' ?> text-white p-4 text-center">

<h1>BILANS MIESIĄCA</h1>
<h2><?= number_format($balance, 2, ',', ' ') ?> zł</h2>

<p>Przychody: <?= number_format($totalIncome, 2, ',', ' ') ?> zł</p>
<p>Wydatki: <?= number_format($totalMonth, 2, ',', ' ') ?> zł</p>

</div>
</div>

<!-- BUDŻETY -->
<div class="col-md-8">
<div class="card p-3">

<h5>Budżety</h5>

<?php foreach ($budgets as $b):

$pct = $b['amount_limit'] > 0
? ($b['current_spent'] / $b['amount_limit']) * 100
: 0;

$color = $pct > 100 ? 'bg-danger' : ($pct > 80 ? 'bg-warning' : 'bg-success');
?>

<div class="mb-2">

<small>
<?= htmlspecialchars($b['category']) . ' ' ?>
(<?= $b['current_spent'] ?> / <?= $b['amount_limit'] ?> zł)
</small>

<div class="progress">
<div class="progress-bar <?= $color ?>"
style="width: <?= min($pct, 100) ?>%">
<?= round($pct) ?>%
</div>
</div>

</div>

<?php endforeach; ?>

</div>
</div>

</div>

<!-- HISTORIA -->
<div class="row mt-4">

<div class="col-md-6">

<div class="card p-3">
<h5>Historia</h5>

<table class="table">

<?php foreach ($history as $h): ?>

<tr>
<td><?= $h['date'] ?></td>

<td>
<?php if ($h['type'] === 'income'): ?>
<span class="badge bg-success fs-4 fw-bold">+</span>
<?php else: ?>
<span class="badge bg-danger fs-4 fw-bold">-</span>
<?php endif; ?>
</td>

<td><?= htmlspecialchars($h['name']) ?></td>

<td><?= number_format($h['amount'], 2, ',', ' ') ?> zł</td>
</tr>

<?php endforeach; ?>

</table>

</div>

</div>

<!-- WYKRESY -->
<div class="col-md-6">

<div class="card p-3 align-items-center">

<h5>Wydatki</h5>
<div style="width: 300px; height: 300px;">
<canvas id="expenseChart"></canvas>
</div>

<hr>

<h5>Przychody</h5>
<div style="width: 300px; height: 300px;">
<canvas id="incomeChart"></canvas>
</div>

</div>

</div>

</div>

</div>

<script>
window.expenseLabels = <?= json_encode($expenseCategories) ?> ?? [];
window.expenseData = <?= json_encode($expenseSums) ?> ?? [];

window.incomeLabels = <?= json_encode($incomeCategories) ?> ?? [];
window.incomeData = <?= json_encode($incomeSums) ?> ?? [];
</script>


<script src="charts.js"></script>

</body>
</html>