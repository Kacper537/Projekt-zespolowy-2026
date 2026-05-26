<?php
try {
    // Łączymy się z MySQL w XAMPP, wskazując bazę budget_planner
    $db = new PDO('mysql:host=localhost;dbname=budget_planner;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Tabele stworzyłeś już bezpośrednio w phpMyAdmin za pomocą kodu SQL, 
    // więc tutaj nie musimy pisać kodu tworzącego tabele przez PHP.

} catch (PDOException $e) {
    die("Błąd połączenia z bazą danych: " . $e->getMessage());
}
?>