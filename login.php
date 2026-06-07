<?php
session_start();
if (isset($_GET['forced_logout'])) {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: login.php");
    exit();
}

if (isset($_POST['action']) && $_POST['action'] === 'logout') {
    session_unset();
    session_destroy();
    session_start(); // Перезапускаем чистую сессию для вывода формы
}

require_once 'db.php';
require_once 'User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$error = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = isset($_POST['login']) ? trim($_POST['login']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Жесткая проверка администратора из ТЗ с поиском реального ID в базе
    if ($login === 'Admin26' && $password === 'Demo20') {
        $stmt = $db->prepare("SELECT id, fio FROM users WHERE login = 'Admin26' LIMIT 1");
        $stmt->execute();
        $adminData = $stmt->fetch(PDO::FETCH_ASSOC);

        $_SESSION['user_id'] = $adminData ? $adminData['id'] : 1;
        $_SESSION['fio'] = 'Главный Администратор';
        $_SESSION['role_id'] = 2;
        header("Location: admin.php");
        exit();
    }

    // Проверка для обычных пользователей
    if (!empty($login) && !empty($password)) {
        $userData = $user->login($login, $password);
        if ($userData) {
            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['fio'] = $userData['fio'];
            $_SESSION['role_id'] = $userData['role_id'];
            
            if ($_SESSION['role_id'] == 2) {
                header("Location: admin.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $error = "Неверный логин или пароль.";
        }
    } else {
        $error = "Заполните все поля.";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход — Банкетам.Нет</title>
    <link href="https://jsdelivr.net" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-header bg-success text-white text-center"><h3>Вход в систему</h3></div>
                <div class="card-body p-4">
                    <?php if(!empty($error)): ?> 
                        <div class="alert alert-danger"><?php echo $error; ?></div> 
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Логин</label>
                            <input type="text" name="login" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Пароль</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Войти</button>
                    </form>
                    <div class="text-center mt-3">
                        <a href="register.php">Еще не зарегистрированы? Регистрация</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
