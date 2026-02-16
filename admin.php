<?php
session_start();

define('AUTH_USERNAME', 'Danya'); 
define('AUTH_PASSWORD', 'pkm5kdMw6YOY'); 


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

$projectsFile = 'projects.json';
$templatePath = 'index.php';
$message = "";
$uniqueFilePath = 'index.php';
$newAvatarUrl = '';
$uniqueId = '';

// Инициализируем $formData как пустой массив
$formData = [];

// Получаем сообщения и переменные из сессии
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

if (isset($_SESSION['uniqueFilePath'])) {
    $uniqueFilePath = $_SESSION['uniqueFilePath'];
    unset($_SESSION['uniqueFilePath']);
}

if (isset($_SESSION['zipFilePath'])) {
    $zipFilePath = $_SESSION['zipFilePath'];
    unset($_SESSION['zipFilePath']);
}

if (isset($_SESSION['uniqueId'])) {
    $uniqueId = $_SESSION['uniqueId'];
    unset($_SESSION['uniqueId']);
}

// Если есть project_id в GET-параметрах, загружаем проект
if (isset($_GET['project_id']) && !empty($_GET['project_id'])) {
    $uniqueId = $_GET['project_id'];
    $project = loadProject($uniqueId);
    if ($project) {
        $formData = $project['form_data'];
        $uniqueFilePath = $project['file_path'];
    }
}

function addFilesToZip($zip, $folder, $relativePath = '') {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($files as $file) {
        if (!$file->isDir()) {
            // Добавляем файл с относительным путем внутри архива
            $filePath = $file->getRealPath();
            $relativeFilePath = $relativePath . '/' . $file->getFilename();
            $zip->addFile($filePath, $relativeFilePath);
        }
    }
}

function createZipArchive($uniqueFilePath, $avatarPath) {
    // Определяем путь для сохранения ZIP-архива
    $zipFilePath = str_replace(".php", ".zip", $uniqueFilePath);
    $zip = new ZipArchive();

    if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE) {
        // Добавляем index.php файл в архив с именем "index.php"
        $zip->addFile($uniqueFilePath, 'index.php');

        // Добавляем аватарку, если она существует
        if (file_exists($avatarPath)) {
            $zip->addFile($avatarPath, basename($avatarPath));
        }

        // Добавляем отдельные файлы
        $singleFiles = ['background_colors.txt', 'style.css', 'style.css.map', 'projects.json', 'style.scss', 'favicon.ico'];
        foreach ($singleFiles as $file) {
            if (file_exists($file)) {
                $zip->addFile($file, basename($file));
            }
        }

        // Добавляем директории с файлами
        $directories = ['favicon_ion', 'img'];
        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                addFilesToZip($zip, $dir, $dir);
            }
        }

        $zip->close();
        return $zipFilePath;
    } else {
        return false;
    }
}

function saveProject($uniqueId, $uniqueFilePath, $formData) {
    global $projectsFile;

    $projects = [];
    if (file_exists($projectsFile)) {
        $projects = json_decode(file_get_contents($projectsFile), true);
    }

    // Сохраняем is_main, если он уже установлен
    $isMain = $projects[$uniqueId]['is_main'] ?? false;

    $projects[$uniqueId] = [
        'file_path' => $uniqueFilePath,
        'form_data' => $formData,
        'is_main' => $isMain
    ];

    file_put_contents($projectsFile, json_encode($projects));
}

function deleteProject($uniqueId) {
    global $projectsFile;

    if (file_exists($projectsFile)) {
        $projects = json_decode(file_get_contents($projectsFile), true);

        if (isset($projects[$uniqueId])) {
            // Удаление файла, связанного с проектом
            $filePath = $projects[$uniqueId]['file_path'];
            if (file_exists($filePath) && $filePath !== 'index.php') {
                unlink($filePath);
            }

            // Удаление аватарки, если она существует
            if (!empty($projects[$uniqueId]['form_data']['avatar'])) {
                $avatarPath = $projects[$uniqueId]['form_data']['avatar'];
                if (file_exists($avatarPath)) {
                    unlink($avatarPath);
                }
            }

            // Удаление проекта из списка
            unset($projects[$uniqueId]);
            file_put_contents($projectsFile, json_encode($projects));
            return true;
        }
    }

    return false;
}

function getProjects() {
    global $projectsFile;
    if (file_exists($projectsFile)) {
        return json_decode(file_get_contents($projectsFile), true);
    }
    return [];
}

function loadProject($uniqueId) {
    global $projectsFile;
    if (file_exists($projectsFile)) {
        $projects = json_decode(file_get_contents($projectsFile), true);
        if (isset($projects[$uniqueId])) {
            return $projects[$uniqueId];
        }
    }
    return null;
}

// Обработка POST-запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_project_id'])) {
        // Обработка удаления проекта
        $deleteId = $_POST['delete_project_id'];
        if (deleteProject($deleteId)) {
            $_SESSION['message'] = "Проект успешно удален!";
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $message = "Ошибка при удалении проекта!";
        }
    } elseif (isset($_POST['set_main_project_id'])) {
        // Обработка установки главного проекта
        $mainProjectId = $_POST['set_main_project_id'];
        $projects = getProjects();

        if (isset($projects[$mainProjectId])) {
            // Получаем данные выбранного проекта
            $project = $projects[$mainProjectId];
            $projectFilePath = $project['file_path'];

            // Путь к основному index.php
            $mainIndexPath = 'index.php';

            // Ищем текущий главный проект и снимаем флаг
            foreach ($projects as $id => &$proj) {
                if (isset($proj['is_main']) && $proj['is_main']) {
                    // Снимаем флаг is_main
                    $proj['is_main'] = false;
                    $originalFilePath = "index_{$id}.php";
                    // Переименовываем index.php обратно в исходное имя файла проекта
                    if (file_exists($mainIndexPath)) {
                        rename($mainIndexPath, $originalFilePath);
                    }
                    $proj['file_path'] = $originalFilePath;
                }
            }
            unset($proj); // Размыкаем ссылку

            // Устанавливаем новый главный проект
            $projects[$mainProjectId]['is_main'] = true;

            // Переименовываем файл проекта в index.php
            if (file_exists($projectFilePath)) {
                rename($projectFilePath, $mainIndexPath);
            }
            $projects[$mainProjectId]['file_path'] = $mainIndexPath;

            // Сохраняем обновленные данные проектов
            file_put_contents($projectsFile, json_encode($projects));

            $_SESSION['message'] = "Проект успешно установлен как главный index.php!";
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $message = "Проект не найден!";
        }
    } elseif (isset($_POST['pixel_id'])) {
        // Обработка сохранения проекта

        if (isset($_POST['project_id']) && !empty($_POST['project_id'])) {
            $uniqueId = $_POST['project_id'];
            $project = loadProject($uniqueId);
            if ($project) {
                $content = file_get_contents($project['file_path']);
                $uniqueFilePath = $project['file_path'];
                $formData = $project['form_data']; // Инициализируем $formData существующими данными проекта
            } else {
                // Если проект не найден, создаем новый
                $content = file_get_contents($templatePath);
                $uniqueId = uniqid();
                $uniqueFilePath = "index_{$uniqueId}.php";
                $formData = []; // Инициализируем пустой массив для новых данных
            }
        } else {
            // Если project_id не установлен, создаем новый проект
            $content = file_get_contents($templatePath);
            $uniqueId = uniqid();
            $uniqueFilePath = "index_{$uniqueId}.php";
            $formData = []; // Инициализируем пустой массив для новых данных
        }

        // Обновляем $formData новыми данными из формы
        $formData['pixel_id'] = $_POST['pixel_id'];
        $formData['channel_name'] = $_POST['channel_name'];
        $formData['channel_description'] = $_POST['channel_description'];
        $formData['subscribers_count'] = $_POST['subscribers_count'];
        $formData['telegram_link'] = $_POST['telegram_link'];
        $formData['button_text'] = $_POST['button_text'];
        $formData['background_colors'] = $_POST['background_colors'] ?? '';
        
// Обработка замены цветов фона
if (!empty($formData['background_colors'])) {
    $colorsArray = explode(',', $formData['background_colors']);
    if (count($colorsArray) >= 9) {
        $color1 = "rgb({$colorsArray[0]}, {$colorsArray[1]}, {$colorsArray[2]})";
        $color2 = "rgb({$colorsArray[3]}, {$colorsArray[4]}, {$colorsArray[5]})";
        $color3 = "rgb({$colorsArray[6]}, {$colorsArray[7]}, {$colorsArray[8]})";
        // Заменяем значения CSS-переменных в контенте
        $content = preg_replace('/--bg-color1:.*?;/', "--bg-color1: {$color1};", $content);
        $content = preg_replace('/--bg-color2:.*?;/', "--bg-color2: {$color2};", $content);
        $content = preg_replace('/--bg-color3:.*?;/', "--bg-color3: {$color3};", $content);
    }
}
        // Обработка замены пикселя
       // Определение допустимых событий пикселя
$allowedEvents = ['PageView', 'Lead', 'Subscribe', 'Purchase']; // Добавьте другие события по необходимости

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Обработка замены пикселя
    if (!empty($_POST['pixel_id']) && !empty($_POST['pixel_event'])) {
        // Получение и очистка данных
        $newPixelId = htmlspecialchars($_POST['pixel_id'], ENT_QUOTES, 'UTF-8');
        $newPixelEvent = htmlspecialchars($_POST['pixel_event'], ENT_QUOTES, 'UTF-8');

        // Валидация выбранного события
        if (in_array($newPixelEvent, $allowedEvents)) {
            // Замена ID пикселя
            $content = preg_replace('/tr\?id=\d+&/', "tr?id={$newPixelId}&", $content);
            $content = preg_replace('/fbq\(\'init\', \'[0-9]+\'\);/', "fbq('init', '{$newPixelId}');", $content);
            $content = preg_replace('/https:\/\/www.facebook.com\/tr\?id=\d+&ev=[A-Za-z]+&noscript=1/',
                                    "https://www.facebook.com/tr?id={$newPixelId}&ev={$newPixelEvent}&noscript=1", $content);
            
            // Дополнительная замена события в JavaScript (если требуется)
            // Например, если событие используется в других местах, добавьте соответствующие замены
            // Пример:
            // $content = preg_replace('/fbq\(\'track\', \'[A-Za-z]+\'\);/', "fbq('track', '{$newPixelEvent}');", $content);
        } else {
            // Обработка ошибки: недопустимое событие
            echo "Выбранное событие пикселя недействительно.";
        }
    } else {
        // Обработка ошибки: недостающие поля
        echo "Пожалуйста, заполните все необходимые поля.";
    }

    // Обработка других полей формы
    // Например, обработка загрузки аватарки, обновление названия канала и т.д.
    // ...
}

        // Обработка загрузки аватарки
        if (!empty($_FILES['avatar']['name'])) {
            $fileType = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            $allowedTypes = ["jpg", "jpeg", "png", "gif"];
            if (in_array($fileType, $allowedTypes)) {
                $avatarPath = 'ava_' . uniqid() . '.' . $fileType;
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $avatarPath)) {
                    $newAvatarUrl = $avatarPath . "?t=" . time();
                    $content = preg_replace('/<img src="[^"]+"/', '<img src="' . $newAvatarUrl . '"', $content);
                    $content = preg_replace('/<meta property="og:image" content="[^"]+">/',
                                            '<meta property="og:image" content="' . $newAvatarUrl . '">', $content);
                    $formData['avatar'] = $avatarPath; // Сохраняем новый путь к аватару в $formData
                } else {
                    $message .= "Ошибка при загрузке файла. ";
                }
            } else {
                $message .= "Допускаются только JPG, JPEG, PNG или GIF файлы. ";
            }
        } else {
            // Если аватар не был загружен, используем существующий
            if (!empty($formData['avatar'])) {
                $newAvatarUrl = $formData['avatar'] . "?t=" . time();
                $content = preg_replace('/<img src="[^"]+"/', '<img src="' . $newAvatarUrl . '"', $content);
                $content = preg_replace('/<meta property="og:image" content="[^"]+">/',
                                        '<meta property="og:image" content="' . $newAvatarUrl . '">', $content);
            }
        }

        // Обработка изменения названия канала
        if (!empty($_POST['channel_name'])) {
            $newTitle = htmlspecialchars($_POST['channel_name'], ENT_QUOTES, 'UTF-8');
            $content = preg_replace_callback('/(<div class="nik">)([^<]*)(<svg.*<\/svg>)/s', function($matches) use ($newTitle) {
                return $matches[1] . $newTitle . ' ' . $matches[3];
            }, $content);
            $content = preg_replace('/<meta property="og:title" content="[^"]+">/', '<meta property="og:title" content="' . $newTitle . '">', $content);
        // Регулярное выражение для замены второго <strong> внутри div.text-additional
    $pattern = '/(<div class="text-additional">.*?<br><strong>)([^<]+)(<\/strong>.*?<\/div>)/s';
    $replacement = '${1}' . $newTitle . '${3}';
    
    $content = preg_replace($pattern, $replacement, $content);
}
        // Обработка изменения количества подписчиков
        if (!empty($_POST['subscribers_count'])) {
            $newSubscribersCount = htmlspecialchars($_POST['subscribers_count'], ENT_QUOTES, 'UTF-8');
            $content = preg_replace('/<div class="name">[^<]* subscribers<\/div>/', '<div class="name">' . $newSubscribersCount . ' subscribers</div>', $content);
        }

        // Обработка изменения описания канала
        if (!empty($_POST['channel_description'])) {
            $newDescription = htmlspecialchars($_POST['channel_description'], ENT_QUOTES, 'UTF-8');
            $content = preg_replace('/<div class="coment">[^<]*<\/div>/', '<div class="coment">' . $newDescription . '</div>', $content);
            $content = preg_replace('/<meta property="og:description" content="[^"]+">/', '<meta property="og:description" content="' . $newDescription . '">', $content);
        }

      // Обработка изменения ссылки Telegram
if (!empty($_POST['telegram_link'])) {
    $telegramLink = htmlspecialchars($_POST['telegram_link'], ENT_QUOTES, 'UTF-8');

    // Если это ссылка формата https://t.me/+inviteCode
    if (preg_match('/https:\/\/t\.me\/\+([a-zA-Z0-9_-]+)/', $telegramLink, $matches)) {
        $telegramUrl = 'tg://join?invite=' . $matches[1];

    // Если это ссылка формата https://t.me/username?text=...
    } elseif (preg_match('/https:\/\/t\.me\/([a-zA-Z0-9_]+)\?text=([^"]+)/', $telegramLink, $matches)) {
        // $matches[1] — это username
        // $matches[2] — это закодированный текст после ?text=
        $telegramUrl = 'tg://resolve?domain=' . $matches[1] . '&text=' . $matches[2];

    // Если это обычная ссылка формата https://t.me/username
    } elseif (preg_match('/https:\/\/t\.me\/([a-zA-Z0-9_]+)/', $telegramLink, $matches)) {
        $telegramUrl = 'tg://resolve?domain=' . $matches[1];

    // Если не подходит ни под один из паттернов, оставляем как есть
    } else {
        $telegramUrl = $telegramLink;
    }

    // Ниже — блок обновления ссылок в контенте
    // Обновление ссылок с классом btn-card
    $content = preg_replace(
        '/<a class="btn-card" href="tg:\/\/[^"]+"/',
        '<a class="btn-card" href="' . $telegramUrl . '"',
        $content
    );

    // Обновление ссылок внутри div class="cont"
    $content = preg_replace(
        '/<a href="tg:\/\/resolve\?domain=[^"]+"/',
        '<a href="' . $telegramUrl . '"',
        $content
    );

    $content = preg_replace(
        '/<meta property="al:ios:url" content="tg:\/\/[^"]+">/',
        '<meta property="al:ios:url" content="' . $telegramUrl . '">',
        $content
    );
    $content = preg_replace(
        '/<meta property="al:android:url" content="tg:\/\/[^"]+">/',
        '<meta property="al:android:url" content="' . $telegramUrl . '">',
        $content
    );
    $content = preg_replace(
        '/<meta name="twitter:app:url:iphone" content="tg:\/\/[^"]+">/',
        '<meta name="twitter:app:url:iphone" content="' . $telegramUrl . '">',
        $content
    );
    $content = preg_replace(
        '/<meta name="twitter:app:url:ipad" content="tg:\/\/[^"]+">/',
        '<meta name="twitter:app:url:ipad" content="' . $telegramUrl . '">',
        $content
    );
    $content = preg_replace(
        '/<meta name="apple-itunes-app" content="app-id=686449807, app-argument: tg:\/\/[^"]+">/',
        '<meta name="apple-itunes-app" content="app-id=686449807, app-argument: ' . $telegramUrl . '">',
        $content
    );

    // Для Twitter/Android-ссылки оставляем исходный https://t.me/..., 
    // так как Telegram внутри Play Market обычно обрабатывает такие ссылки
    $content = preg_replace(
        '/<meta name="twitter:app:url:googleplay" content="https:\/\/t\.me\/[^"]+">/',
        '<meta name="twitter:app:url:googleplay" content="' . $telegramLink . '">',
        $content
    );
}

        // Обработка изменения текста кнопки
        if (!empty($_POST['button_text'])) {
            $newButtonText = htmlspecialchars($_POST['button_text'], ENT_QUOTES, 'UTF-8');
            $content = preg_replace('/<a class="btn-card" href="[^"]+" onclick="loadFacebookPixel\(\)">[^<]*<\/a>/', '<a class="btn-card" href="' . $telegramUrl . '" onclick="loadFacebookPixel()">' . $newButtonText . '</a>', $content);
        }

        // Сохраняем обновленный контент обратно в файл проекта
        if (file_put_contents($uniqueFilePath, $content) !== false) {
            saveProject($uniqueId, $uniqueFilePath, $formData); // Сохраняем проект с обновленным $formData

            // Создаем ZIP-архив
            $zipFilePath = createZipArchive($uniqueFilePath, $formData['avatar']);

            // Сохраняем сообщения и данные в сессии
            $_SESSION['message'] = "Проект успешно сохранен!";
            $_SESSION['uniqueFilePath'] = $uniqueFilePath;
            $_SESSION['zipFilePath'] = $zipFilePath;
            $_SESSION['uniqueId'] = $uniqueId;

            // Перенаправляем на ту же страницу с параметром project_id
            header('Location: ' . $_SERVER['PHP_SELF'] . '?project_id=' . $uniqueId);
            exit();
        } else {
            $message .= "Ошибка при записи файла!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель</title>
    <link rel="icon" href="mlogo.jpg" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&display=swap&subset=cyrillic" rel="stylesheet">
    <style>
        /* Общие стили */
        * {
            box-sizing: border-box;
        }
        .icon {
            width: 100px;
            height: 100px;
        }
        body, html {
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            font-family: Montserrat, sans-serif;
        }
        .wrapper {
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }
        .sidebar {
            width: 300px;
            background: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-right: 1px solid #ccc;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100vh;
            overflow-y: auto;
            position: sticky;
            top: 0;
        }
        .content {
            flex-grow: 1;
            padding: 20px;
            display: flex;
            flex-direction: column;
            height: auto;
            overflow-y: auto;
            box-sizing: border-box;
        }
        h1, h2 {
            color: #54a9eb;
            text-align: center;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            margin-bottom: 10px;
            font-weight: bold;
        }
    input[type="text"], input[type="file"], textarea {
    margin-bottom: 20px;
    padding: 12px 16px;
    border: 1px solid #ccc;
    border-radius: 6px;
    width: 100%;
    box-sizing: border-box;
    background-color: #f9f9f9;
    font-size: 16px;
    transition: all 0.3s ease;
}

input[type="text"]:focus, input[type="file"]:focus, textarea:focus {
    border-color: #5a9bff;
    background-color: #e9f3ff;
    outline: none;
}

/* Стиль для поля выбора файла */
input[type="file"] {
    padding: 8px 12px;
    font-size: 14px;
    background-color: #f7f7f7;
    border: 1px solid #ccc;
    border-radius: 4px;
    width: 100%;
    box-sizing: border-box;
    cursor: pointer;
    transition: all 0.3s ease;
}

input[type="file"]:hover {
    border-color: #007bff;
    background-color: #e9f7ff;
}

input[type="file"]:focus {
    border-color: #0056b3;
    background-color: #e0f3ff;
    outline: none;
}

/* Стиль для кнопки */
button {
    padding: 12px 18px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s ease, transform 0.3s ease;
}

button:hover {
    background-color: #0056b3;
    transform: translateY(-2px); /* Легкое поднятие кнопки */
}

button:active {
    background-color: #00408d;
    transform: translateY(0); /* Снижение эффекта при нажатии */
}

        .iframe-container {
            flex-grow: 1;
            width: 100%;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 100%;
            box-sizing: border-box;
        }
        iframe {
            flex-grow: 1;
            border: none;
            width: 100%;
            height: 100%;
        }
        .author {
            text-align: center;
            margin-top: 20px;
        }
        .author a {
            color: #007bff;
            text-decoration: none;
        }
        .author a:hover {
            text-decoration: underline;
        }
        .success-message {
            text-align: center;
            color: green;
            margin-top: 20px;
        }
        
        .unique-link {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            display: inline-block;
            margin-top: 10px;
        }
        .unique-link:hover {
            background-color: #0056b3;
        }
        .project-selector {
            text-align: center;
            margin-top: 20px;
        }
       /* Стиль для селектора (select) */
.project-selector select {
    padding: 12px 16px;
    font-size: 14px;
    background-color: #f9f9f9;
    border: 1px solid #ccc;
    border-radius: 6px;
    width: 100%;
    box-sizing: border-box;
    cursor: pointer;
    transition: all 0.3s ease;
}

.project-selector select:hover {
    border-color: #66ccff;
    background-color: #f0faff;
}

.project-selector select:focus {
    border-color: #007bff;
    background-color: #e6f7ff;
    outline: none;
}

/* Стиль для изображения аватара */
.avatar-preview {
    display: block;
    margin-bottom: 20px;
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    border: 2px solid #ddd;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.avatar-preview:hover {
    transform: scale(1.05); /* Легкое увеличение изображения при наведении */
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15); /* Увеличение тени для глубины */
}

        .background-selector {
            margin-top: 20px;
        }
        .background-selector h2 {
            margin-bottom: 10px;
        }
        .color-options {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .color-option {
            border: none;
            width: 30px;
            height: 30px;
            cursor: pointer;
            border-radius: 50%;
        }
        @media (max-width: 768px) {
            .wrapper {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                height: auto;
                border-right: none;
                border-bottom: 1px solid #ccc;
                position: relative;
            }
            .content {
                padding: 10px;
                overflow-x: hidden;
            }
            .iframe-container {
                height: auto;
            }
            iframe {
                height: 500px;
                width: 97vw;
            }
        }
        label {
  font-family: Arial, sans-serif; /* Шрифт текста */
  font-size: 16px; /* Размер шрифта */
  color: #333; /* Цвет текста */
  display: inline-block; /* Блок для управления отступами */
  margin-bottom: 8px; /* Отступ снизу */
  cursor: pointer; /* Курсор меняется при наведении */
}

/* Стили для контейнера события пикселя (если будет input) */
#pixel_event {
  font-size: 14px; /* Размер шрифта для input */
  padding: 8px; /* Внутренний отступ */
  border: 1px solid #ccc; /* Рамка */
  border-radius: 4px; /* Скругление углов */
  width: 100%; /* Полная ширина */
  box-sizing: border-box; /* Учёт границ и отступов */
}


    </style>
</head>
<body>
  <div class="wrapper">
    <div class="sidebar">
        <div style="text-align: center;">
            <h1>Telegram FBpix</h1>
            <img src="pix.svg" alt="pixLogo" width="60" height="60">
            <form method="post" enctype="multipart/form-data" id="projectForm">
                <input type="hidden" name="project_id" id="project_id" value="<?php echo htmlspecialchars($uniqueId); ?>">

                <!-- Поле выбора пикселя -->
                
                <label for="pixel_id">Введите новый номер пикселя:</label>
                <select id="pixel_event" name="pixel_event" required>
                    <option value="" disabled <?php echo empty($formData['pixel_event']) ? 'selected' : ''; ?>>-- Выберите событие --</option>
                    <option value="PageView" <?php echo (isset($formData['pixel_event']) && $formData['pixel_event'] === 'PageView') ? 'selected' : ''; ?>>PageView</option>
                    <option value="Lead" <?php echo (isset($formData['pixel_event']) && $formData['pixel_event'] === 'Lead') ? 'selected' : ''; ?>>Lead</option>
                    <option value="Subscribe" <?php echo (isset($formData['pixel_event']) && $formData['pixel_event'] === 'Subscribe') ? 'selected' : ''; ?>>Subscribe</option>
                    <option value="Purchase" <?php echo (isset($formData['pixel_event']) && $formData['pixel_event'] === 'Purchase') ? 'selected' : ''; ?>>Purchase</option>
                    <!-- Добавьте другие события по необходимости -->
                </select>
                <input type="text" id="pixel_id" name="pixel_id" pattern="\d*" inputmode="numeric" required value="<?php echo htmlspecialchars($formData['pixel_id'] ?? ''); ?>">

                <!-- Новое поле выбора события пикселя -->
               
                

                <!-- Остальные поля формы остаются без изменений -->
                <label for="avatar">Загрузите новую аватарку (JPG, JPEG, PNG, GIF):</label>
                <?php if (!empty($formData['avatar'])): ?>
                    <img id="avatarPreview" src="<?php echo htmlspecialchars($formData['avatar']); ?>" alt="Preview" class="avatar-preview">
                <?php else: ?>
                    <img id="avatarPreview" src="#" alt="Preview" class="avatar-preview" style="display:none;">
                <?php endif; ?>
                <input type="file" id="avatar" name="avatar" accept=".jpg,.jpeg,.png,.gif">

                <label for="channel_name">Введите новое название канала:</label>
                <input type="text" id="channel_name" name="channel_name" required value="<?php echo htmlspecialchars($formData['channel_name'] ?? ''); ?>">

                <label for="channel_description">Введите новое описание канала:</label>
                <textarea id="channel_description" name="channel_description" rows="4" required><?php echo htmlspecialchars($formData['channel_description'] ?? ''); ?></textarea>

                <label for="subscribers_count">Введите количество подписчиков:</label>
                <input type="text" id="subscribers_count" name="subscribers_count" required value="<?php echo htmlspecialchars($formData['subscribers_count'] ?? ''); ?>">

                <label for="telegram_link">Введите новую ссылку Telegram:</label>
                <input type="text" id="telegram_link" name="telegram_link" required value="<?php echo htmlspecialchars($formData['telegram_link'] ?? ''); ?>">

                <label for="button_text">Введите текст кнопки:</label>
                <input type="text" id="button_text" name="button_text" required value="<?php echo htmlspecialchars($formData['button_text'] ?? ''); ?>">

                <button type="submit">Обновить</button>
            </form>
        
                <!-- Блок выбора фона -->
                

                <!-- Блок выбора проекта -->
                <div class="project-selector">
                    <h2>Выбор проекта:</h2>
                    <select id="projectSelect">
                        <option value="">Выберите проект...</option>
                        <?php 
                        $projects = getProjects();
                        foreach ($projects as $id => $project) {
                            $label = isset($project['is_main']) && $project['is_main'] ? 'Главный индекс' : "Проект $id";
                            echo "<option value='$id' " . ($uniqueId == $id ? 'selected' : '') . ">$label</option>";
                        }
                        ?>
                    </select>

   <!-- Кнопка аналитики -->
<!-- Кнопка аналитики с обработчиком onclick -->
<!-- Кнопка аналитики с открытием в новой вкладке -->
<button id="analyticsButton" class="analytics-button" title="Статистика кликов и посещений проектов" onclick="window.open('analytics.php', '_blank');">
    <img src="https://img.icons8.com/ios-filled/50/ffffff/combo-chart--v1.png" alt="Analytics" class="icon">
</button>



<button id="deleteProjectButton" class="vector-delete-button" title="Удалить проект">
    <img src="https://img.icons8.com/ios-filled/50/ffffff/trash.png" alt="Delete" class="icon">
</button>

<!-- Кнопка установки главного index.php -->
<button id="setMainIndexButton" class="set-main-index-button" title="Сделать главным index.php">
    <svg xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 24 24">
        <path d="M20.285,2.858l-11.3,11.3l-4.285-4.285L3,12.15l6,6l12-12L20.285,2.858z"/>
    </svg>
</button>

                </div>
                <style>
                    /* Общие стили для иконок кнопок */
.vector-delete-button,
.set-main-index-button {
    margin-top: 30px; /* Согласованный отступ сверху */
    padding: 10px;
    background-color: #f44336; /* Цвет кнопки удаления */
    border: none;
    border-radius: 50%; /* Круглая форма */
    cursor: pointer;
    display: inline-flex;
    justify-content: center;
    align-items: center;
    width: 50px;
    height: 50px;
    transition: background-color 0.3s, transform 0.2s;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}
/* Стили для кнопки аналитики */
.analytics-button {
    background-color: #007bff;
    border: none;
    cursor: pointer;
    padding: 5px;
}

.analytics-button .icon {
    width: 31px;
    height: 31px;
    opacity: 0.8; /* Иконка сразу отображается с уменьшенной непрозрачностью */
}

/* Если не нужно менять эффект при наведении, можно убрать следующий блок */
.analytics-button:hover .icon {
    opacity: 0.8; /* Оставляем непрозрачность такой же при наведении */
}


.set-main-index-button {
    background-color: #4CAF50; /* Зеленый цвет для главного индекса */
}

.vector-delete-button:hover {
    background-color: #d32f2f; /* Темнее при наведении для удаления */
}

.set-main-index-button:hover {
    background-color: #45a049; /* Темнее при наведении для главного индекса */
    transform: translateY(-2px);
}

.vector-delete-button:active,
.set-main-index-button:active {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

/* Стили для иконок внутри кнопок */
.icon {
    width: 24px;
    height: 24px;
    fill: white; /* Для SVG иконок */
}

.vector-delete-button .icon {
    width: 24px;
    height: 24px;
}

.set-main-index-button .icon {
    width: 24px;
    height: 24px;
}

                </style>
            </div>

            <div class="author">
                <p>Автор: <a href="https://t.me/adseu" target="_blank">https://t.me/adseu</a></p>
            </div>
        </div>

        <div class="content">
            <h2>Демонстрация результата:</h2>
            <div class="iframe-container">
                <iframe src="<?php echo htmlspecialchars($uniqueFilePath); ?>" id="resultFrame"></iframe>
            </div>
            <div class="author">
                <p>Автор: <a href="https://t.me/adseu" target="_blank">https://t.me/adseu</a></p>
            </div>
            <?php if ($message): ?>
                <div class="success-message">
                    <?php echo $message; ?>
                    <?php if (!empty($uniqueFilePath)): ?>
                        <a href="<?php echo htmlspecialchars($uniqueFilePath); ?>" target="_blank" class="unique-link">СМОТРЕТЬ</a>
                    <?php endif; ?>
                    <?php if (!empty($zipFilePath)): ?>
                        <a href="<?php echo htmlspecialchars($zipFilePath); ?>" class="unique-link" download>СКАЧАТЬ ZIP</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        document.getElementById('setMainIndexButton').addEventListener('click', function() {
            const selectedProjectId = document.getElementById('projectSelect').value;
            if (selectedProjectId) {
                if (confirm('Вы уверены, что хотите сделать этот проект главным index.php?')) {
                    const formData = new FormData();
                    formData.append('set_main_project_id', selectedProjectId);

                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        alert('Проект успешно установлен как главный index.php!');
                        location.reload(); // Перезагрузка страницы
                    })
                    .catch(error => {
                        alert('Ошибка при установке главного проекта');
                        console.error('Error:', error);
                    });
                }
            } else {
                alert('Пожалуйста, выберите проект для установки.');
            }
        });
    </script>
    <script>
        document.getElementById('projectSelect').addEventListener('change', function() {
            const selectedProjectId = this.value;
            const projects = <?php echo json_encode(getProjects()); ?>;

            if (selectedProjectId) {
                if (projects[selectedProjectId]) {
                    const project = projects[selectedProjectId];
                    const formData = project.form_data;

                    document.getElementById('pixel_id').value = formData.pixel_id;
                    document.getElementById('channel_name').value = formData.channel_name;
                    document.getElementById('channel_description').value = formData.channel_description;
                    document.getElementById('subscribers_count').value = formData.subscribers_count;
                    document.getElementById('telegram_link').value = formData.telegram_link;
                    document.getElementById('button_text').value = formData.button_text;
                    document.getElementById('project_id').value = selectedProjectId;

                    if (formData.avatar) {
                        const avatarPreview = document.getElementById('avatarPreview');
                        avatarPreview.src = formData.avatar;
                        avatarPreview.style.display = 'block';
                    } else {
                        document.getElementById('avatarPreview').style.display = 'none';
                    }

                    const iframe = document.getElementById('resultFrame');
                    iframe.src = project.file_path;

                    const uniqueLink = document.querySelector('.unique-link');
                    if (uniqueLink) {
                        uniqueLink.href = project.file_path;
                        uniqueLink.textContent = "СМОТРЕТЬ";
                    } else {
                        const newLink = document.createElement('a');
                        newLink.href = project.file_path;
                        newLink.target = "_blank";
                        newLink.className = "unique-link";
                        newLink.textContent = "СМОТРЕТЬ";
                        document.querySelector('.success-message').appendChild(newLink);
                    }
                }
            } else {
                // Сбрасываем форму и iframe при выборе "Выберите проект..."
                document.getElementById('project_id').value = '';
                document.getElementById('pixel_id').value = '';
                document.getElementById('channel_name').value = '';
                document.getElementById('channel_description').value = '';
                document.getElementById('subscribers_count').value = '';
                document.getElementById('telegram_link').value = '';
                document.getElementById('button_text').value = '';
                document.getElementById('avatarPreview').style.display = 'none';

                const iframe = document.getElementById('resultFrame');
                iframe.src = '<?php echo $templatePath; ?>';

                const uniqueLink = document.querySelector('.unique-link');
                if (uniqueLink) {
                    uniqueLink.href = '';
                    uniqueLink.textContent = '';
                }
            }
        });

        document.getElementById('avatar').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const avatarPreview = document.getElementById('avatarPreview');
                    avatarPreview.src = e.target.result;
                    avatarPreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        document.getElementById('deleteProjectButton').addEventListener('click', function() {
            const selectedProjectId = document.getElementById('projectSelect').value;
            if (selectedProjectId) {
                if (confirm('Вы уверены, что хотите удалить этот проект?')) {
                    const formData = new FormData();
                    formData.append('delete_project_id', selectedProjectId);

                    fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        alert('Проект успешно удален!');
                        location.reload(); // Перезагрузка страницы для обновления списка проектов
                    })
                    .catch(error => {
                        alert('Ошибка при удалении проекта');
                        console.error('Error:', error);
                    });
                }
            } else {
                alert('Пожалуйста, выберите проект для удаления.');
            }
        });

       document.addEventListener('DOMContentLoaded', function() {
            const newAvatarUrl = '<?php echo $newAvatarUrl; ?>';
            if (newAvatarUrl) {
                const iframe = document.getElementById('resultFrame');
                iframe.addEventListener('load', function() {
                    const iframeDocument = iframe.contentDocument || iframe.contentWindow.document;
                    const avatarImg = iframeDocument.querySelector('.tgme_page_photo_image');
                    if (avatarImg) {
                        avatarImg.src = newAvatarUrl;
                    }

                    // **Обновление ссылки внутри div class="cont"**
                    const contLink = iframeDocument.querySelector('div.cont a[href^="tg://resolve?domain="]');
                    if (contLink) {
                        contLink.href = '<?php echo $telegramUrl; ?>';
                    }
                });
            }
        });
    </script>
</body>
</html>
