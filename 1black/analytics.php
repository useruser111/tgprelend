<?php
session_start();

// --------------------
// Настройки авторизации
// --------------------

// Заранее заданные учетные данные
define('AUTH_USERNAME', 'rut1'); // Замените на желаемое имя пользователя
define('AUTH_PASSWORD', 'rut2'); // Замените на желаемый пароль

// Обработка выхода из системы
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    // Уничтожение сессии
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}

// Обработка входа в систему
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $input_username = trim($_POST['username']);
    $input_password = trim($_POST['password']);

    // Проверка учетных данных
    if ($input_username === AUTH_USERNAME && $input_password === AUTH_PASSWORD) {
        // Успешный вход, установка сессионной переменной
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = AUTH_USERNAME;
    } else {
        $login_error = "Неверное имя пользователя или пароль.";
    }
}

// Проверка, вошёл ли пользователь
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // Форма входа
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Вход в панель управления</title>
        <style>
    /* Общие стили */
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        /* Голубоватый градиент в стиле Telegram */
        background: linear-gradient(135deg, #0088cc, #00aaff);
        height: 100vh;
        margin: 0;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    /* Контейнер формы */
    .login-container {
        background-color: #ffffff;
        padding: 40px;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        width: 350px;
        box-sizing: border-box;
    }

    .login-container h2 {
        text-align: center;
        margin-bottom: 30px;
        color: #333333;
    }

    /* Стили полей ввода */
    .login-container input[type="text"],
    .login-container input[type="password"] {
        width: 100%;
        padding: 12px 20px;
        margin: 8px 0 20px 0;
        display: inline-block;
        border: 1px solid #ccc;
        border-radius: 5px;
        box-sizing: border-box;
        transition: border-color 0.3s;
    }

    .login-container input[type="text"]:focus,
    .login-container input[type="password"]:focus {
        border-color: #0088cc; /* Цвет акцента Telegram */
        outline: none;
    }

    /* Стили кнопки */
    .login-container input[type="submit"] {
        width: 100%;
        background-color: #0088cc; /* Основной цвет Telegram */
        color: white;
        padding: 14px 20px;
        margin: 8px 0;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        transition: background-color 0.3s;
    }

    .login-container input[type="submit"]:hover {
        background-color: #006699; /* Темнее основной цвет при наведении */
    }

    /* Стили сообщения об ошибке */
    .error-message {
        color: #e74c3c;
        background-color: #fceae9;
        border: 1px solid #e74c3c;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 20px;
        text-align: center;
    }
</style>

    </head>
    <body>
        <div class="login-container">
            <h2>Вход в Панель</h2>
            <?php 
            if(!empty($login_error)){
                echo '<div class="error-message">' . htmlspecialchars($login_error) . '</div>';
            }        
            ?>
            <form method="post" action="">
                <input type="text" name="username" placeholder="Имя пользователя" required>
                <input type="password" name="password" placeholder="Пароль" required>
                <input type="submit" name="login" value="Войти">
            </form>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// --------------------
// Основной функционал приложения
// --------------------

// Ваш существующий код начинается здесь

$json_file = 'tracking_data.json';
$projects_file = 'projects.json';

function loadJSON($file) {
    if (!file_exists($file)) {
        file_put_contents($file, json_encode([]));
    }
    return json_decode(file_get_contents($file), true);
}

// Обработка удаления проекта
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_project'])) {
    $project_key_to_delete = $_POST['delete_project'];

    // Удаляем проект из данных отслеживания
    $tracking_data = loadJSON($json_file);
    unset($tracking_data[$project_key_to_delete]);
    file_put_contents($json_file, json_encode($tracking_data));

    // Удаляем проект из списка проектов
    $projects = loadJSON($projects_file);
    unset($projects[$project_key_to_delete]);
    file_put_contents($projects_file, json_encode($projects));

    // Перенаправление на ту же страницу, чтобы предотвратить повторную отправку формы
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Загрузка данных отслеживания и проектов
$tracking_data = loadJSON($json_file);
$projects = loadJSON($projects_file);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Аналитика Telegram FBpix</title>
    <!-- Установка фавикона -->
    <link rel="icon" type="image/png" href="mlogo.jpg">
    <!-- Стили -->
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #e9f5ff;
            margin: 0;
            padding: 0;
            color: #333;
        }

        header {
            background: linear-gradient(90deg, #1e90ff 0%, #1c75bc 100%);
            color: #fff;
            padding: 20px;
            text-align: center;
            position: relative;
        }

        header img {
            position: absolute;
            left: 20px;
            top: 15px;
            height: 50px;
        }

        h1 {
            margin: 0;
            font-size: 2em;
        }

        .highlighted-text {
            border: 2px dashed #fff;
            padding: 5px 10px;
            border-radius: 5px;
            background-color: rgba(255, 255, 255, 0.2);
            display: inline-block;
            margin-left: 10px;
        }

        main {
            padding: 40px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            margin-top: 20px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        th, td {
            padding: 15px;
            text-align: left;
        }

        th {
            background-color: #1e90ff;
            color: #fff;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        tr:nth-child(even) {
            background-color: #f2f8ff;
        }

        tr:hover {
            background-color: #d0e7ff;
            transition: background-color 0.3s;
        }

        a {
            color: #1e90ff;
            text-decoration: none;
            font-weight: bold;
        }

        a:hover {
            text-decoration: underline;
        }

        /* Кнопка возврата на главную */
        .back-button {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #1e90ff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .back-button:hover {
            background-color: #1c75bc;
        }

        /* Адаптивный дизайн */
        @media (max-width: 768px) {
            main {
                padding: 20px 10px;
            }

            header img {
                height: 40px;
                top: 10px;
            }

            th, td {
                padding: 12px;
            }
        }

        /* Стили для кнопок действий */
        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .delete-button, .open-link-button {
            padding: 5px 10px;
            background-color: #1e90ff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }

        .delete-button:hover {
            background-color: #ff4d4d;
        }

        .open-link-button:hover {
            background-color: #1c75bc;
        }
    </style>
</head>
<body>
    <header>
        <!-- Логотип -->
        <img src="mlogo.jpg" alt="Логотип">
        <h1><span class="highlighted-text">Аналитика Telegram FBpix</span></h1>
    </header>
    <main>
        <table>
            <thead>
               <tr>
                    <th>Номер проекта</th>
                    <th>Имя проекта</th>
                    <th>Количество посещений</th>
                    <th>Количество кликов</th>
                    <th>Аналитика кликов</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tracking_data as $project_key => $data): ?>
                    <tr>
                          <!-- Номер проекта (project_key) -->
                        <td data-label="Номер проекта">
                            <?php echo htmlspecialchars($project_key); ?>
                        <td data-label="Имя проекта">
                            <?php 
                                $project_name = $project_key === 'index.php' 
                                    ? 'Главная страница' 
                                    : htmlspecialchars($projects[$project_key]['form_data']['channel_name'] ?? 'Неизвестный проект'); 
                                echo $project_name;
                                
                            ?>
                        </td>
                        <td data-label="Количество посещений"><?php echo $data['page_visits']; ?></td>
                        <td data-label="Количество кликов"><?php echo $data['button_clicks']; ?></td>
                        <td data-label="Аналитика кликов">
                            <a href="click_analysis.php?project_id=<?php echo urlencode($project_key); ?>">Посмотреть страны и IP-адреса</a>
                        </td>
                        <td data-label="Действия">
                            <div class="action-buttons">
                                <!-- Кнопка открытия проекта -->
                                <?php if ($project_key !== 'index.php'): ?>
                                    <?php
                                        // Формируем имя файла проекта
                                        $project_file = 'index_' . $project_key . '.php';
                                        // Проверяем, существует ли файл
                                        if (file_exists($project_file)):
                                    ?>
                                        <a href="<?php echo htmlspecialchars($project_file); ?>" target="_blank" class="open-link-button">Открыть проект</a>
                                    <?php else: ?>
                                        <span style="color: red;">Файл не найден</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <!-- Форма удаления проекта -->
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="delete_project" value="<?php echo htmlspecialchars($project_key); ?>">
                                    <button type="submit" class="delete-button" onclick="return confirm('Вы уверены, что хотите удалить этот проект?');">Удалить</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <!-- Кнопка возврата на главную -->
        <a href="admin.php" class="back-button">Вернуться на главную</a>
    </main>
</body>
</html>
