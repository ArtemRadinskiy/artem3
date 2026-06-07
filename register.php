<?php
session_start();
require_once 'db.php';
require_once 'User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fio = trim($_POST['fio']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $login = trim($_POST['login']);
    $password = $_POST['password'];

    if (empty($fio) || empty($phone) || empty($email) || empty($login) || empty($password)) {
        $error = "Все поля обязательны для заполнения.";
    } else {
        $result = $user->register($fio, $phone, $email, $login, $password);
        if ($result === true) {
            $success = "Регистрация успешна! Теперь вы можете войти.";
        } else {
            $error = $result; // Выводим ошибку валидации из класса User
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация — Банкетам.Нет</title>
    <link href="https://jsdelivr.net" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center"><h3>Регистрация</h3></div>
                <div class="card-body p-4">
                    <?php if($error): ?> <div class="alert alert-danger"><?=$error?></div> <?php endif; ?>
                    <?php if($success): ?> <div class="alert alert-success"><?=$success?></div> <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">ФИО</label>
                            <input type="text" name="fio" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Контактный телефон</label>
                            <input type="text" name="phone" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">E-mail</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Логин (мин. 6 символов, латиница и цифры)</label>
                            <input type="text" name="login" class="form-control" pattern="[a-zA-Z0-9]{6,}" title="Только латинские буквы и цифры, не менее 6 символов" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Пароль (минимум 8 символов)</label>
                            <input type="password" name="password" class="form-control" minlength="8" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Зарегистрироваться</button>
                    </form>
                    <div class="text-center mt-3">
                        <a href="login.php">Еще не зарегистрированы? Регистрация</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
