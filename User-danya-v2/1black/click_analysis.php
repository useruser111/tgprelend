<?php
// Включение отображения ошибок (для отладки)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Пути к JSON-файлам
$json_file = 'tracking_data.json';
$projects_file = 'projects.json';

// Функция для загрузки данных из JSON
function loadJSON($file) {
    if (!file_exists($file)) {
        file_put_contents($file, json_encode([]));
    }
    return json_decode(file_get_contents($file), true);
}

// Загрузка данных отслеживания и проектов
$tracking_data = loadJSON($json_file);
$projects = loadJSON($projects_file);

// Получение project_id из URL
$project_key = $_GET['project_id'] ?? 'index.php';

// Проверка существования проекта
if (!isset($tracking_data[$project_key])) {
    die("Проект не найден.");
}

// Данные кликов
$click_data = $tracking_data[$project_key]['click_data'];

// 1. Обратный порядок кликов, чтобы новые были первыми
$click_data = array_reverse($click_data);

// 2. Пагинация
$clicks_per_page = 20; // Количество кликов на странице
$total_clicks = count($click_data);
$total_pages = ceil($total_clicks / $clicks_per_page);

// Получение текущей страницы из URL, по умолчанию 1
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;

// Ограничение текущей страницы допустимыми значениями
if ($current_page < 1) {
    $current_page = 1;
} elseif ($current_page > $total_pages) {
    $current_page = $total_pages;
}

// Вычисление индексов для массива кликов
$start_index = ($current_page - 1) * $clicks_per_page;
$display_clicks = array_slice($click_data, $start_index, $clicks_per_page);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Аналитика кликов по проекту</title>
    <style>
        /* Ваши стили остаются без изменений */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #e9f5ff;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        h1 {
            text-align: center;
            color: #1e90ff;
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
            padding: 12px 15px;
            border-bottom: 1px solid #ddd;
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
        /* Стили для пагинации */
        .pagination {
            margin-top: 20px;
            text-align: center;
        }
        .pagination a, .pagination span {
            display: inline-block;
            margin: 0 5px;
            padding: 8px 12px;
            color: #1e90ff;
            text-decoration: none;
            border: 1px solid #1e90ff;
            border-radius: 4px;
            transition: background-color 0.3s, color 0.3s;
        }
        .pagination a:hover {
            background-color: #1e90ff;
            color: #fff;
        }
        .pagination .current {
            background-color: #1e90ff;
            color: #fff;
            border-color: #1e90ff;
            cursor: default;
        }
        .pagination .disabled {
            color: #ccc;
            border-color: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <h1>Детальная аналитика кликов для проекта <?php echo htmlspecialchars($project_key); ?></h1>
    <table>
        <tr>
            <th>IP-адрес</th>
            <th>Страна</th>
            <th>User Agent</th>
            <th>Время</th>
        </tr>
        <?php if (empty($display_clicks)): ?>
            <tr>
                <td colspan="4" style="text-align:center;">Нет данных для отображения.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($display_clicks as $click): ?>
                <tr>
                    <td><?php echo htmlspecialchars($click['ip_address']); ?></td>
                    <td><?php echo htmlspecialchars($click['country']); ?></td>
                    <td><?php echo htmlspecialchars($click['user_agent'] ?? 'Неизвестно'); ?></td>
                    <td><?php echo htmlspecialchars($click['timestamp'] ?? 'Неизвестно'); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>

    <!-- Пагинация -->
    <div class="pagination">
        <?php if ($current_page > 1): ?>
            <a href="?project_id=<?php echo urlencode($project_key); ?>&page=1">&laquo; Первая</a>
            <a href="?project_id=<?php echo urlencode($project_key); ?>&page=<?php echo $current_page - 1; ?>">&lt; Предыдущая</a>
        <?php else: ?>
            <span class="disabled">&laquo; Первая</span>
            <span class="disabled">&lt; Предыдущая</span>
        <?php endif; ?>

        <!-- Отображение номеров страниц (например, 5 страниц вокруг текущей) -->
        <?php
            $range = 2; // Количество страниц до и после текущей
            $start = max(1, $current_page - $range);
            $end = min($total_pages, $current_page + $range);

            if ($start > 1) {
                echo '<span>...</span>';
            }

            for ($i = $start; $i <= $end; $i++) {
                if ($i == $current_page) {
                    echo '<span class="current">' . $i . '</span>';
                } else {
                    echo '<a href="?project_id=' . urlencode($project_key) . '&page=' . $i . '">' . $i . '</a>';
                }
            }

            if ($end < $total_pages) {
                echo '<span>...</span>';
            }
        ?>

        <?php if ($current_page < $total_pages): ?>
            <a href="?project_id=<?php echo urlencode($project_key); ?>&page=<?php echo $current_page + 1; ?>">Следующая &gt;</a>
            <a href="?project_id=<?php echo urlencode($project_key); ?>&page=<?php echo $total_pages; ?>">Последняя &raquo;</a>
        <?php else: ?>
            <span class="disabled">Следующая &gt;</span>
            <span class="disabled">Последняя &raquo;</span>
        <?php endif; ?>
    </div>

    <a href="index.php" class="back-button">Вернуться на главную</a>
</body>
</html>
