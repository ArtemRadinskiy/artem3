<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

require_once 'db.php';
$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

$msg = '';

// Обработка отправки отзыва
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_review'])) {
    $booking_id = intval($_POST['booking_id']);
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);

    if ($rating >= 1 && $rating <= 5 && !empty($comment)) {
        $query = "INSERT INTO reviews (user_id, booking_id, rating, comment) VALUES (:user_id, :booking_id, :rating, :comment)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':booking_id', $booking_id);
        $stmt->bindParam(':rating', $rating);
        $stmt->bindParam(':comment', $comment);
        if ($stmt->execute()) {
            $msg = "<div class='alert alert-success'>Спасибо за ваш отзыв!</div>";
        }
    }
}

// Получение списка заявок пользователя
$query = "SELECT b.id, p.name AS premise_name, b.event_date, b.payment_method, b.status,
          (SELECT COUNT(*) FROM reviews r WHERE r.booking_id = b.id) as has_review
          FROM bookings b 
          JOIN premises p ON b.premise_id = p.id 
          WHERE b.user_id = :user_id 
          ORDER BY b.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$my_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Личный кабинет — Банкетам.Нет</title>
    <link href="https://jsdelivr.net" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="#">Портал «Банкетам.Нет»</a>
        <div class="navbar-nav ms-auto">
            <span class="navbar-text text-white me-3">Вы вошли как: <strong><?=htmlspecialchars($_SESSION['fio'])?></strong></span>
            <a href="create_order.php" class="btn btn-success btn-sm me-2">Оформить заявку</a>
            <a href="logout.php" class="btn btn-outline-danger btn-sm">Выйти</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2>История ваших заявок</h2>
    <?=$msg?>
    <?php if(isset($_GET['success'])): ?> <div class="alert alert-success">Заявка успешно отправлена на согласование администратору!</div> <?php endif; ?>

    <div class="card shadow mt-3">
        <div class="card-body">
            <?php if(count($my_bookings) == 0): ?>
                <p class="text-muted text-center">У вас пока нет оформленных заявок. <a href="create_order.php">Оформить первую заявку</a></p>
            <?php else: ?>
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>№</th>
                            <th>Помещение</th>
                            <th>Дата банкета</th>
                            <th>Способ оплаты</th>
                            <th>Статус</th>
                            <th>Отзыв</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($my_bookings as $b): ?>
                            <tr>
                                <td><?=$b['id']?></td>
                                <td><?=htmlspecialchars($b['premise_name'])?></td>
                                <td><?=date('d.m.Y H:i', strtotime($b['event_date']))?></td>
                                <td><?=htmlspecialchars($b['payment_method'])?></td>
                                <td>
                                    <?php 
                                        $badge = 'bg-warning text-dark';
                                        if ($b['status'] == 'Банкет назначен') $badge = 'bg-primary';
                                        if ($b['status'] == 'Банкет завершен') $badge = 'bg-success';
                                    ?>
                                    <span class="badge <?=$badge?>"><?=$b['status']?></span>
                                </td>
                                <td>
                                    <?php if($b['status'] == 'Банкет завершен'): ?>
                                        <?php if($b['has_review'] > 0): ?>
                                            <span class="text-success text-muted">Отзыв оставлен</span>
                                        <?php else: ?>
                                            <!-- Кнопка вызова модального окна отзыва -->
                                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#reviewModal<?=$b['id']?>">Оставить отзыв</button>
                                            
                                            <!-- Модальное окно -->
                                            <div class="modal fade" id="reviewModal<?=$b['id']?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST" action="">
                                                            <div class="modal-header"><h5>Оставить отзыв к заявке №<?=$b['id']?></h5></div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="add_review" value="1">
                                                                <input type="hidden" name="booking_id" value="<?=$b['id']?>">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Оценка (1-5)</label>
                                                                    <select name="rating" class="form-select" required>
                                                                        <option value="5">5 — Отлично</option>
                                                                        <option value="4">4 — Хорошо</option>
                                                                        <option value="3">3 — Удовлетворительно</option>
                                                                        <option value="2">2 — Плохо</option>
                                                                        <option value="1">1 — Ужасно</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Ваш отзыв</label>
                                                                    <textarea name="comment" class="form-control" rows="3" required></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                                                                <button type="submit" class="btn btn-primary">Отправить</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <small class="text-muted">Доступно после завершения</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://jsdelivr.net"></script>
</body>
</html>
