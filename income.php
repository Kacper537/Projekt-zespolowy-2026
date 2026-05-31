<?php
require_once 'database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$incomeToEdit = null;

/* =========================
   DODAWANIE
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_income'])) {

    $amount = floatval($_POST['amount']);
    $source = trim($_POST['source']);
    $date = $_POST['date'];
    $description = trim($_POST['description'] ?? '');

    if ($amount > 0 && !empty($source) && !empty($date)) {

        $stmt = $db->prepare("
            INSERT INTO incomes (user_id, amount, source, date, description)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $user_id,
            $amount,
            $source,
            $date,
            $description
        ]);
    }

    header("Location: income.php");
    exit;
}

/* =========================
   UPDATE
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_income'])) {

    $income_id = intval($_POST['income_id']);
    $amount = floatval($_POST['amount']);
    $source = trim($_POST['source']);
    $date = $_POST['date'];
    $description = trim($_POST['description'] ?? '');

    if ($amount > 0 && !empty($source) && !empty($date)) {

        $stmt = $db->prepare("
            UPDATE incomes
            SET amount = ?, source = ?, date = ?, description = ?
            WHERE id = ? AND user_id = ?
        ");

        $stmt->execute([
            $amount,
            $source,
            $date,
            $description,
            $income_id,
            $user_id
        ]);
    }

    header("Location: income.php");
    exit;
}

/* =========================
   DELETE
========================= */
if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);

    $stmt = $db->prepare("
        DELETE FROM incomes
        WHERE id = ? AND user_id = ?
    ");

    $stmt->execute([$id, $user_id]);

    header("Location: income.php");
    exit;
}

/* =========================
   EDIT MODE
========================= */
if (isset($_GET['edit'])) {

    $id = intval($_GET['edit']);

    $stmt = $db->prepare("
        SELECT *
        FROM incomes
        WHERE id = ? AND user_id = ?
    ");

    $stmt->execute([$id, $user_id]);
    $incomeToEdit = $stmt->fetch();
}

$isEditing = !empty($incomeToEdit);

/* =========================
   LISTA
========================= */
$stmt = $db->prepare("
    SELECT *
    FROM incomes
    WHERE user_id = ?
    ORDER BY date DESC, id DESC
");

$stmt->execute([$user_id]);
$incomes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Przychody</title>

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
            <a class="nav-link" href="expenses.php">Wydatki</a>
            <a class="nav-link active" href="income.php">Przychody</a>
            <a class="nav-link" href="budgets.php">Budżety</a>
            <a class="nav-link text-danger" href="logout.php">Wyloguj</a>
        </div>

    </div>
</nav>

<div class="container">

<div class="row">

    <!-- FORM -->
    <div class="col-md-4 mb-4">

        <div class="card p-4 shadow-sm">

            <h5>
                <?= $isEditing ? 'Edytuj przychód' : 'Dodaj przychód' ?>
            </h5>

            <form method="POST">

                <?php if ($isEditing): ?>
                    <input type="hidden" name="update_income" value="1">
                    <input type="hidden" name="income_id" value="<?= $incomeToEdit['id'] ?>">
                <?php else: ?>
                    <input type="hidden" name="add_income" value="1">
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Kwota (zł)</label>
                    <input type="number"
                           step="0.01"
                           name="amount"
                           class="form-control"
                           value="<?= $isEditing ? $incomeToEdit['amount'] : '' ?>"
                           required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Źródło</label>
                    <input type="text"
                           name="source"
                           class="form-control"
                           placeholder="np. Pensja, Freelance, Sprzedaż..."
                           value="<?= $isEditing ? htmlspecialchars($incomeToEdit['source']) : '' ?>"
                           required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Data</label>
                    <input type="date"
                           name="date"
                           class="form-control"
                           value="<?= $isEditing ? $incomeToEdit['date'] : date('Y-m-d') ?>"
                           required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Opis</label>
                    <textarea name="description"
                              class="form-control"><?= $isEditing ? htmlspecialchars($incomeToEdit['description']) : '' ?></textarea>
                </div>

                <button class="btn btn-success w-100">
                    <?= $isEditing ? 'Zapisz zmiany' : 'Dodaj przychód' ?>
                </button>

            </form>

        </div>

    </div>

    <!-- TABLE -->
    <div class="col-md-8">

        <div class="card p-4 shadow-sm">

            <h5>Historia przychodów</h5>

            <table class="table table-striped">

                <thead>
                <tr>
                    <th>Data</th>
                    <th>Źródło</th>
                    <th>Kwota</th>
                    <th>Opis</th>
                    <th>Akcja</th>
                </tr>
                </thead>

                <tbody>

                <?php foreach ($incomes as $i): ?>
                    <tr>
                        <td><?= $i['date'] ?></td>

                        <td>
                            <span class="badge bg-success">
                                <?= htmlspecialchars($i['source']) ?>
                            </span>
                        </td>

                        <td>
                            <strong class="text-success">
                                +<?= number_format($i['amount'], 2, ',', ' ') ?> zł
                            </strong>
                        </td>

                        <td><?= htmlspecialchars($i['description']) ?></td>

                        <td>
                            <a href="income.php?edit=<?= $i['id'] ?>"
                               class="btn btn-warning btn-sm">
                                Edytuj
                            </a>

                            <a href="income.php?delete=<?= $i['id'] ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Usunąć?')">
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

</body>
</html>