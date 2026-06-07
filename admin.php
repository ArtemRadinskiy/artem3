<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: login.php");
    exit();
}

require_once 'db.php';
$database = new Database();
$db = $database->getConnection();

$status_updated = false;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $booking_id = intval($_POST['booking_id']);
    $new_status = $_POST['status'];

    if (in_array($new_status, ['Новая', 'Банкет назначен', 'Банкет завершен'])) {
        $query = "UPDATE bookings SET status = :status WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $new_status);
        $stmt->bindParam(':id', $booking_id);
        if ($stmt->execute()) {
            $status_updated = true;
        }
    }
}

$sort_order = isset($_GET['sort']) && $_GET['sort'] == 'asc' ? 'ASC' : 'DESC';

$query = "SELECT b.id, u.fio, u.phone, p.name AS premise_name, b.event_date, b.payment_method, b.status 
          FROM bookings b 
          JOIN users u ON b.user_id = u.id 
          JOIN premises p ON b.premise_id = p.id 
          ORDER BY b.event_date $sort_order";
$stmt = $db->query($query);
$all_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора — Банкетам.Нет</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 0; }
        body { background-color: #f4f6f9; color: #333; }
        .navbar { background-color: #dc3545; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; color: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .navbar-brand { font-size: 20px; font-weight: 700; color: white; text-decoration: none; }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        h2 { margin-bottom: 15px; font-weight: 700; color: #212529; }
        .toolbar { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .filter-group, .sort-group { display: flex; align-items: center; gap: 10px; }
        .toolbar label { font-size: 14px; font-weight: 600; color: #495057; text-transform: uppercase; }
        .select-custom { padding: 8px 12px; border: 1px solid #ced4da; border-radius: 8px; background-color: #f8f9fa; outline: none; font-size: 14px; }
        .btn-sort { padding: 8px 16px; background: #212529; color: white; text-decoration: none; border-radius: 8px; font-size: 14px; font-weight: 600; text-align: center; }
        .table-card { background: white; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 15px; }
        th { background-color: #212529; color: white; padding: 14px; font-weight: 600; }
        td { padding: 14px; border-bottom: 1px solid #dee2e6; }
        tr:hover { background-color: #f8f9fa; }
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
        .badge.status-new { background-color: #fff3cd; color: #664d03; }
        .badge.status-assigned { background-color: #cff4fc; color: #055160; }
        .badge.status-completed { background-color: #d1e7dd; color: #0f5132; }
        .form-status { display: flex; gap: 8px; }
        .btn-ok { padding: 6px 12px; background: #dc3545; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px; }
        .toast-container { position: fixed; top: 20px; right: 20px; z-index: 1000; }
        .toast { background: #198754; color: white; padding: 16px 24px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); font-weight: 600; display: flex; align-items: center; gap: 10px; transform: translateY(-50px); opacity: 0; transition: transform 0.4s, opacity 0.4s; }
        .toast.show { transform: translateY(0); opacity: 1; }
        @media (max-width: 768px) {
            .toolbar { flex-direction: column; align-items: stretch; }
            table, thead, tbody, th, td, tr { display: block; }
            thead { display: none; }
            tr { margin-bottom: 15px; border: 1px solid #dee2e6; border-radius: 8px; padding: 10px; background: white; }
            td { border: none; padding: 8px 4px; display: flex; justify-content: space-between; align-items: center; }
            td::before { content: attr(data-label); font-weight: 700; font-size: 13px; color: #6c757d; }
        }
    </style>
</head>
<body>

<div class="toast-container">
    <div id="statusToast" class="toast">
        ✓ Статус заявки успешно обновлен!
    </div>
</div>

<nav class="navbar">
    <a href="#" class="navbar-brand">Панель Администратора | Банкетам.Нет</a>
</nav>

<div class="container">
    <h2>Управление заявками клиентов</h2>

    <!-- Новая независимая кнопка выхода, которая не ломается -->
    <button type="button" onclick="location.href='login.php?forced_logout=1'" style="background-color: #dc3545; color: white; padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; font-size: 14px; margin-bottom: 20px; display: block;">Выйти из системы</button>

    <div class="toolbar">
        <div class="filter-group">
            <label for="statusFilter">Фильтр по статусу:</label>
            <select id="statusFilter" class="select-custom" onchange="filterTable()">
                <option value="all">Все заявки</option>
                <option value="Новая">Новая</option>
                <option value="Банкет назначен">Банкет назначен</option>
                <option value="Банкет завершен">Банкет завершен</option>
            </select>
        </div>
        
        <div class="sort-group">
            <label>Сортировка по дате:</label>
            <?php if($sort_order == 'DESC'): ?>
                <a href="admin.php?sort=asc" class="btn-sort">Сначала старые ↑</a>
            <?php else: ?>
                <a href="admin.php?sort=desc" class="btn-sort">Сначала новые ↓</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-card">
        <?php if(count($all_bookings) == 0): ?>
            <div style="padding: 30px; text-align: center; color: #6c757d;">Заявок пока нет.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Клиент / Телефон</th>
                        <th>Помещение</th>
                        <th>Дата банкета</th>
                        <th>Оплата</th>
                        <th>Статус</th>
                        <th>Действие</th>
                    </tr>
                </thead>
                <tbody id="bookingsTableBody">
                    <?php foreach($all_bookings as $b): ?>
                        <tr class="booking-row" data-status="<?php echo $b['status']; ?>">
                            <td data-label="№"><strong><?php echo $b['id']; ?></strong></td>
                            <td data-label="Клиент">
                                <?php echo htmlspecialchars($b['fio']); ?><br>
                                <small style="color: #6c757d;"><?php echo htmlspecialchars($b['phone']); ?></small>
                            </td>
                            <td data-label="Помещение"><?php echo htmlspecialchars($b['premise_name']); ?></td>
                            <td data-label="Дата"><?php echo date('d.m.Y H:i', strtotime($b['event_date'])); ?></td>
                            <td data-label="Оплата"><?php echo htmlspecialchars($b['payment_method']); ?></td>
                            <td data-label="Статус">
                                <?php 
                                    $class = 'status-new';
                                    if ($b['status'] == 'Банкет назначен') $class = 'status-assigned';
                                    if ($b['status'] == 'Банкет завершен') $class = 'status-completed';
                                ?>
                                <span class="badge <?php echo $class; ?>"><?php echo $b['status']; ?></span>
                            </td>
                            <td data-label="Действие">
                                <form method="POST" action="" class="form-status">
                                    <input type="hidden" name="update_status" value="1">
                                    <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                    <select name="status" class="select-custom" style="padding: 4px 8px; font-size: 13px;">
                                        <option value="Новая" <?php echo $b['status']=='Новая'?'selected':''; ?>>Новая</option>
                                        <option value="Банкет назначен" <?php echo $b['status']=='Банкет назначен'?'selected':''; ?>>Банкет назначен</option>
                                        <option value="Банкет завершен" <?php echo $b['status']=='Банкет завершен'?'selected':''; ?>>Банкет завершен</option>
                                    </select>
                                    <button type="submit" class="btn-ok">ОК</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
function filterTable() {
    const filterValue = document.getElementById('statusFilter').value;
    const rows = document.querySelectorAll('.booking-row');
    rows.forEach(row => {
        const rowStatus = row.getAttribute('data-status');
        if (filterValue === 'all' || rowStatus === filterValue) {
row.style.display = '';} else {row.style.display = 'none';}});}const toast = document.getElementById('statusToast');toast.classList.add('show');setTimeout(() => { toast.classList.remove('show'); }, 4000);