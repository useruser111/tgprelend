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

// Функция для сохранения данных в JSON
function saveJSON($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Функция для получения страны по IP через ip-api.com
function getCountryByIP($ip) {
    // Проверка корректности IP
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return 'Некорректный IP';
    }

    $url = "http://ip-api.com/json/{$ip}?fields=status,country";

    $response = @file_get_contents($url);
    if ($response === FALSE) {
        return 'Неизвестно';
    }

    $data = json_decode($response, true);
    if ($data['status'] === 'success') {
        return $data['country'];
    } else {
        return 'Неизвестно';
    }
}

// Получение JSON-данных из POST-запроса
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Проверка наличия необходимых данных
if (!isset($data['project_key'])) {
    http_response_code(400);
    echo "Не указан ключ проекта.";
    exit;
}

$project_key = $data['project_key'];
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Неизвестно';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Неизвестно';
$timestamp = date('Y-m-d H:i:s');

// Определение страны на сервере
$country = getCountryByIP($ip_address);

// Загрузка текущих данных
$tracking_data = loadJSON($json_file);

// Инициализация данных проекта, если он не существует
if (!isset($tracking_data[$project_key])) {
    $tracking_data[$project_key] = [
        'page_visits' => 0,
        'button_clicks' => 0,
        'click_data' => []
    ];
}

// Увеличение счётчика кликов
$tracking_data[$project_key]['button_clicks']++;

// Добавление данных о клике
$tracking_data[$project_key]['click_data'][] = [
    'ip_address' => $ip_address,
    'country' => $country,
    'user_agent' => $user_agent,
    'timestamp' => $timestamp
];

// Сохранение обновлённых данных
saveJSON($json_file, $tracking_data);

// Ответ успешного сохранения
echo "Данные успешно сохранены.";
?>
