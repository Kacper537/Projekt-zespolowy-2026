<?php
require_once 'database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$expenseToEdit = null;

/* =========================
   DODAWANIE WYDATKU
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {

    $amount = floatval($_POST['amount']);
    $category = trim($_POST['category']);
    $date = $_POST['date'];
    $description = trim($_POST['description'] ?? '');

    // jeśli "Inne" → bierzemy custom input
    if ($category === 'Inne' && !empty($_POST['custom_category'])) {
        $category = trim($_POST['custom_category']);
    }

    if ($amount > 0 && !empty($category) && !empty($date)) {

        $stmt = $db->prepare("
            INSERT INTO expenses (user_id, amount, category, date, description)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $user_id,
            $amount,
            $category,
            $date,
            $description
        ]);
    }

    header("Location: expenses.php");
    exit;
}

/* =========================
   USUWANIE
========================= */
if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);

    $stmt = $db->prepare("
        DELETE FROM expenses
        WHERE id = ? AND user_id = ?
    ");

    $stmt->execute([$id, $user_id]);

    header("Location: expenses.php");
    exit;
}

/* =========================
   EDYCJA (GET)
========================= */
if (isset($_GET['edit'])) {

    $id = intval($_GET['edit']);

    $stmt = $db->prepare("
        SELECT *
        FROM expenses
        WHERE id = ? AND user_id = ?
    ");

    $stmt->execute([$id, $user_id]);
    $expenseToEdit = $stmt->fetch();
}

$isEditing = !empty($expenseToEdit);

/* =========================
   AKTUALIZACJA
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_expense'])) {

    $id = intval($_POST['expense_id']);
    $amount = floatval($_POST['amount']);
    $category = trim($_POST['category']);
    $date = $_POST['date'];
    $description = trim($_POST['description'] ?? '');

    if ($category === 'Inne' && !empty($_POST['custom_category'])) {
        $category = trim($_POST['custom_category']);
    }

    if ($amount > 0 && !empty($category) && !empty($date)) {

        $stmt = $db->prepare("
            UPDATE expenses
            SET amount = ?, category = ?, date = ?, description = ?
            WHERE id = ? AND user_id = ?
        ");

        $stmt->execute([
            $amount,
            $category,
            $date,
            $description,
            $id,
            $user_id
        ]);
    }

    header("Location: expenses.php");
    exit;
}

/* =========================
   FILTROWANIE
========================= */
$filter_cat = $_GET['filter_category'] ?? '';

$query = "SELECT * FROM expenses WHERE user_id = ?";
$params = [$user_id];

if (!empty($filter_cat)) {
    $query .= " AND category = ?";
    $params[] = $filter_cat;
}

$query .= " ORDER BY date DESC, id DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$expenses = $stmt->fetchAll();

/* =========================
   KATEGORIE
========================= */
$categories = ['Jedzenie', 'Transport', 'Rachunki', 'Rozrywka', 'Studia', 'Inne'];
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Wydatki</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <link rel="stylesheet" href="style.css">
</head>

<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">

        <a class="navbar-brand" href="dashboard.php">
            Twój portfel (Witaj, <?= htmlspecialchars($_SESSION['username']) ?>)
        </a>

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

    <!-- FORM -->
    <div class="col-md-4 mb-4">

        <div class="card p-3 shadow-sm">

            <h5>
                <?= $isEditing ? 'Edytuj wydatek' : 'Dodaj wydatek' ?>
            </h5>

            <form method="POST">

                <?php if ($isEditing): ?>
                    <input type="hidden" name="update_expense" value="1">
                    <input type="hidden" name="expense_id" value="<?= $expenseToEdit['id'] ?>">
                <?php else: ?>
                    <input type="hidden" name="add_expense" value="1">
                <?php endif; ?>

                <div class="mb-2">
                    <label class="form-label">Kwota (zł)</label>
                    <input type="number"
                           step="0.01"
                           min="0.01"
                           name="amount"
                           class="form-control"
                           value="<?= $isEditing ? $expenseToEdit['amount'] : '' ?>"
                           required>
                </div>

                <div class="mb-2">
                    <label class="form-label">Kategoria</label>

                    <select id="category"
                            name="category"
                            class="form-control"
                            required>

                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat ?>"
                                <?= ($isEditing && $expenseToEdit['category'] === $cat) ? 'selected' : '' ?>>
                                <?= $cat ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div id="customCategoryDiv"
                     class="mb-2"
                     style="display:none;">

                    <label class="form-label">Własna kategoria</label>

                    <input type="text"
                           name="custom_category"
                           class="form-control"
                           placeholder="Np. Kino, Hobby, Prezent">
                </div>

                <div class="mb-2">
                    <label class="form-label">Data</label>

                    <input type="date"
                           name="date"
                           class="form-control"
                           value="<?= $isEditing ? $expenseToEdit['date'] : date('Y-m-d') ?>"
                           required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Opis</label>

                    <input type="text"
                           name="description"
                           class="form-control"
                           value="<?= $isEditing ? htmlspecialchars($expenseToEdit['description']) : '' ?>">
                </div>

                <button class="btn btn-primary w-100">
                    <?= $isEditing ? 'Zapisz zmiany' : 'Dodaj' ?>
                </button>

            </form>

        </div>

    </div>

    <!-- TABLE -->
    <div class="col-md-8">

        <div class="card p-3 shadow-sm mb-3">

            <form method="GET" class="row g-2 align-items-center">

                <div class="col-auto">
                    <h6>Filtr:</h6>
                </div>

                <div class="col-auto">
                    <select name="filter_category" class="form-select">

                        <option value="">Wszystkie</option>

                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat ?>" <?= $filter_cat === $cat ? 'selected' : '' ?>>
                                <?= $cat ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="col-auto">
                    <button class="btn btn-secondary btn-sm">Filtruj</button>
                </div>

            </form>

        </div>

        <div class="card p-3 shadow-sm">

            <table class="table table-striped">

                <thead>
                <tr>
                    <th>Data</th>
                    <th>Kategoria</th>
                    <th>Kwota</th>
                    <th>Opis</th>
                    <th>Akcja</th>
                </tr>
                </thead>

                <tbody>

                <?php foreach ($expenses as $e): ?>
                    <tr>

                        <td><?= $e['date'] ?></td>

                        <td>
                            <span class="badge bg-secondary">
                                <?= htmlspecialchars($e['category']) ?>
                            </span>
                        </td>

                        <td>
                            <strong><?= number_format($e['amount'], 2, ',', ' ') ?> zł</strong>
                        </td>

                        <td><?= htmlspecialchars($e['description']) ?></td>

                        <td>

                            <a href="expenses.php?edit=<?= $e['id'] ?>"
                               class="btn btn-warning btn-sm">
                                Edytuj
                            </a>

                            <a href="expenses.php?delete=<?= $e['id'] ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Na pewno usunąć?')">
                                Usuń
                            </a>

                        </td>

                    </tr>
                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</div>

<script>
document.getElementById('category').addEventListener('change', function () {

    const div = document.getElementById('customCategoryDiv');

    if (this.value === 'Inne') {
        div.style.display = 'block';
    } else {
        div.style.display = 'none';
    }
});
</script>

</body>
</html>