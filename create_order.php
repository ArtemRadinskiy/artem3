<?php
session_start();
// Проверяем авторизацию. Если сессии нет — перекидываем на логин
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

require_once 'db.php';
$database = new Database();
$db = $database->getConnection();

$message = '';
$message_class = '';

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $premise_id = intval($_POST['premise_id']);
    $event_date = !empty($_POST['event_date']) ? date('Y-m-d H:i:s', strtotime($_POST['event_date'])) : '';
    $payment_method = trim($_POST['payment_method']);

    if (!empty($premise_id) && !empty($event_date) && !empty($payment_method)) {
        $query = "INSERT INTO bookings (user_id, premise_id, event_date, payment_method, status) VALUES (:user_id, :premise_id, :event_date, :payment_method, 'Новая')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->bindParam(':premise_id', $premise_id);
        $stmt->bindParam(':event_date', $event_date);
        $stmt->bindParam(':payment_method', $payment_method);
        
        if ($stmt->execute()) { 
            // Перенаправляем в личный кабинет index.php при успешной отправке
            header("Location: index.php?success=1"); 
            exit(); 
        } else { 
            $message = "Ошибка при сохранении в базу данных."; 
            $message_class = "error";
        }
    } else { 
        $message = "Пожалуйста, заполните все обязательные поля."; 
        $message_class = "warning";
    }
}

// Загружаем список типов помещений для выпадающего списка
$premises = $db->query("SELECT id, name FROM premises")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <!-- Мета-тег для правильного масштабирования на смартфонах -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оформление заявки — Банкетам.Нет</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 0; }
        body { background-color: #f0f2f5; color: #333; line-height: 1.5; }
        
        /* Шапка сайта */
        .navbar { background-color: #212529; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; color: white; }
        .navbar-brand { font-size: 20px; font-weight: 700; color: white; text-decoration: none; }
        .btn-back { background: #198754; color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: 0.2s; }
        .btn-back:hover { background: #157347; }

        /* Сетка для ПК: слайдер слева, форма справа */
        .main-container { max-width: 1200px; margin: 30px auto; padding: 0 20px; display: flex; gap: 30px; flex-direction: row; }

        /* Контейнер автоматического слайдера */
        .slider-section { flex: 1.5; min-width: 0; }
        .slider-wrapper { width: 100%; height: 400px; position: relative; overflow: hidden; background: #ddd; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .slides { display: flex; width: 400%; height: 100%; animation: slideAnimation 12s infinite; }
        .slide { width: 25%; height: 100%; }
        .slide img { width: 100%; height: 100%; object-fit: cover; }
        
        /* Переключение слайдов (4 штуки по 3 секунды) */
        @keyframes slideAnimation {
            0%, 20% { transform: translateX(0); }
            25%, 45% { transform: translateX(-25%); }
            50%, 70% { transform: translateX(-50%); }
            75%, 95% { transform: translateX(-75%); }
        }

        /* Контейнер формы */
        .form-section { flex: 1; background: #ffffff; padding: 30px; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); height: fit-content; }
        .form-section h3 { margin-bottom: 20px; font-size: 22px; font-weight: 700; color: #212529; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: #65676b; text-transform: uppercase; }
        .form-input { width: 100%; padding: 12px; border: 1px solid #ced4da; border-radius: 10px; background: #f8f9fa; font-size: 15px; outline: none; transition: 0.2s; }
        .form-input:focus { border-color: #198754; background: #fff; box-shadow: 0 0 0 3px rgba(25,135,84,0.15); }
        
        .btn-submit { width: 100%; padding: 14px; background: #212529; color: white; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-submit:hover { background: #1c1f23; }

        /* Стили уведомлений */
        .alert { padding: 12px; border-radius: 10px; font-size: 14px; margin-bottom: 20px; font-weight: 500; }
        .alert.error { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
        .alert.warning { background: #fff3cd; color: #664d03; border: 1px solid #ffecb5; }

        /* Адаптивные правила для мобильных устройств (ширина экрана меньше 768px) */
        @media (max-width: 768px) {
            .main-container { flex-direction: column; margin: 15px auto; gap: 20px; }
            .slider-wrapper { height: 220px; border-radius: 12px; }
            .form-section { padding: 20px; border-radius: 12px; }
            .form-section h3 { font-size: 18px; margin-bottom: 15px; }
            .form-input { padding: 10px; font-size: 14px; }
            .btn-submit { padding: 12px; font-size: 15px; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="navbar-brand">Портал «Банкетам.Нет»</a>
    <a href="index.php" class="btn-back">← В личный кабинет</a>
</nav>

<div class="main-container">
    <!-- Слайдер картинок -->
    <div class="slider-section">
        <div class="slider-wrapper">
            <div class="slides">
                <div class="slide"><img src="https://picsum.photos" alt="Зал"></div>
                <div class="slide"><img src="https://picsum.photos" alt="Ресторан"></div>
                <div class="slide"><img src="https://picsum.photos" alt="Веранда"></div>
                <div class="slide"><img src="https://picsum.photos" alt="Интерьер"></div>
            </div>
        </div>
    </div>

    <!-- Форма отправки заказа -->
    <div class="form-section">
        <h3>Оформление заявки</h3>

        <?php if($message): ?>
            <div class="alert <?=$message_class?>"><?=$message?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Тип помещения</label>
                <select name="premise_id" class="form-input" required>
                    <option value="">-- Выберите помещение --</option>
                    <?php foreach($premises as $p): ?>
                        <option value="<?=$p['id']?>"><?=htmlspecialchars($p['name'])?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Удобная дата и время</label>
                <input type="datetime-local" name="event_date" class="form-input" required>
            </div>

            <div class="form-group">
                <label>Способ оплаты</label>
                <select name="payment_method" class="form-input" required>
                    <option value="">-- Выберите способ --</option>
                    <option value="Наличные">Наличные</option>
                    <option value="Банковская карта">Банковская карта</option>
                    <option value="Безналичный расчет">Безналичный расчет</option>
                </select>
            </div>

            <button type="submit" class="btn-submit">Отправить на согласование</button>
        </form>
    </div>
</div>

</body>
</html>
