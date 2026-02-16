<?php
// Пути к JSON-файлам
$json_file = 'tracking_data.json';
$projects_file = 'projects.json';

function loadJSON($file) {
    if (!file_exists($file)) {
        file_put_contents($file, json_encode([]));
    }
    return json_decode(file_get_contents($file), true);
}

// Загрузка списка проектов и данных отслеживания
$projects = loadJSON($projects_file);
$tracking_data = loadJSON($json_file);

// Получение имени текущего файла для идентификации
$current_file = basename($_SERVER['SCRIPT_FILENAME']);

// Поиск идентификатора проекта на основе имени файла
$project_key = null;
foreach ($projects as $key => $project) {
    if ($project['file_path'] === $current_file) {
        $project_key = $key;
        break;
    }
}

// Если проект не найден, используем 'index.php' как ключ для главной страницы
$project_key = $project_key !== null ? $project_key : 'index.php';

// Для отладки
// var_dump($current_file);
// var_dump($project_key);

// Инициализация данных проекта, если записи нет
if (!isset($tracking_data[$project_key])) {
    $tracking_data[$project_key] = [
        'page_visits' => 0,
        'button_clicks' => 0,
        'click_data' => []
    ];
}

// Увеличение количества посещений
$tracking_data[$project_key]['page_visits'] += 1;

// Получение IP и страны
$ip_address = $_SERVER['REMOTE_ADDR'];
$country = 'Unknown';
$response = file_get_contents("http://ip-api.com/json/{$ip_address}");
if ($response) {
    $data_ip = json_decode($response);
    $country = $data_ip->country ?? 'Unknown';
}

// Добавляем данные о посещении
$tracking_data[$project_key]['click_data'][] = [
    'ip_address' => $ip_address,
    'country' => $country,
    'timestamp' => date("Y-m-d H:i:s")
];

// Сохранение обновленных данных
file_put_contents($json_file, json_encode($tracking_data, JSON_PRETTY_PRINT));
?>
<!-- Передаем project_key в JavaScript -->
<script>
    const project_key = '<?php echo $project_key; ?>';
</script>
<!-- Ваш остальной код -->




<?php
session_start(); // Запуск сессии

// Проверяем, если сохраненный цвет фона в сессии
if (isset($_SESSION['background_colors'])) {
    $colors = explode(',', $_SESSION['background_colors']);
} else {
    // Цвет по умолчанию, если пользователь не выбрал другой
    $colors = ['202', '211', '140', '137', '182', '141', '205', '214', '180'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telegram: Join Group Chat</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <meta property="og:title" content="Павел Дуров">
<meta property="og:image" content="img/img1.jpg>
<meta property="og:site_name" content="Telegram">
<meta property="og:description" content="💻 Frontend &amp; Backend разработка. ⚙️ Создаю современные веб-приложения и сайты.
🔧 Работаю с JavaScript, React, Node.js.
📬 Открыт для новых проектов и сотрудничества!">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
<link rel="icon" type="image/x-icon" href="favicon_ion/favicon.ico">
</head>
<style>
  body .backgraund {
      overflow: auto;
      height: 100%;
      width: 100%;
      position: fixed;
      /* Цвет по умолчанию или сохраненный */
      background: linear-gradient(48deg, rgb(202, 211, 140) 0%, 
                                  rgb(137, 182, 141) 53%, 
                                  rgb(205, 214, 180) 96%);
  }
</style>

<body>
    <div class="backgraund">

        <div class="bg"></div>
        <div class="head">
        <a  href="//telegram.org/" class="logo-a">
            <svg  height="34" viewBox="0 0 133 34" width="133" xmlns="http://www.w3.org/2000/svg">
              <g fill="none" fill-rule="evenodd">
                <circle cx="17" cy="17" fill="#2481cc" r="17"></circle>
                <path d="m7.06510669 16.9258959c5.22739451-2.1065178 8.71314291-3.4952633 10.45724521-4.1662364 4.9797665-1.9157646 6.0145193-2.2485535 6.6889567-2.2595423.1483363-.0024169.480005.0315855.6948461.192827.1814076.1361492.23132.3200675.2552048.4491519.0238847.1290844.0536269.4231419.0299841.65291-.2698553 2.6225356-1.4375148 8.986738-2.0315537 11.9240228-.2513602 1.2428753-.7499132 1.5088847-1.2290685 1.5496672-1.0413153.0886298-1.8284257-.4857912-2.8369905-1.0972863-1.5782048-.9568691-2.5327083-1.3984317-4.0646293-2.3321592-1.7703998-1.0790837-.212559-1.583655.7963867-2.5529189.2640459-.2536609 4.7753906-4.3097041 4.755976-4.431706-.0070494-.0442984-.1409018-.481649-.2457499-.5678447-.104848-.0861957-.2595946-.0567202-.3712641-.033278-.1582881.0332286-2.6794907 1.5745492-7.5636077 4.6239616-.715635.4545193-1.3638349.6759763-1.9445998.6643712-.64024672-.0127938-1.87182452-.334829-2.78737602-.6100966-1.12296117-.3376271-1.53748501-.4966332-1.45976769-1.0700283.04048-.2986597.32581586-.610598.8560076-.935815z" fill="#fff"></path><path d="m49.4 24v-12.562h-4.224v-2.266h11.198v2.266h-4.268v12.562zm16.094-4.598h-7.172c.066 1.936 1.562 2.772 3.3 2.772 1.254 0 2.134-.198 2.97-.484l.396 1.848c-.924.396-2.2.682-3.74.682-3.476 0-5.522-2.134-5.522-5.412 0-2.97 1.804-5.764 5.236-5.764 3.476 0 4.62 2.86 4.62 5.214 0 .506-.044.902-.088 1.144zm-7.172-1.892h4.708c.022-.99-.418-2.618-2.222-2.618-1.672 0-2.376 1.518-2.486 2.618zm9.538 6.49v-15.62h2.706v15.62zm14.84-4.598h-7.172c.066 1.936 1.562 2.772 3.3 2.772 1.254 0 2.134-.198 2.97-.484l.396 1.848c-.924.396-2.2.682-3.74.682-3.476 0-5.522-2.134-5.522-5.412 0-2.97 1.804-5.764 5.236-5.764 3.476 0 4.62 2.86 4.62 5.214 0 .506-.044.902-.088 1.144zm-7.172-1.892h4.708c.022-.99-.418-2.618-2.222-2.618-1.672 0-2.376 1.518-2.486 2.618zm19.24-1.144v6.072c0 2.244-.462 3.85-1.584 4.862-1.1.99-2.662 1.298-4.136 1.298-1.364 0-2.816-.308-3.74-.858l.594-2.046c.682.396 1.826.814 3.124.814 1.76 0 3.08-.924 3.08-3.234v-.924h-.044c-.616.946-1.694 1.584-3.124 1.584-2.662 0-4.554-2.2-4.554-5.236 0-3.52 2.288-5.654 4.862-5.654 1.65 0 2.596.792 3.102 1.672h.044l.11-1.43h2.354c-.044.726-.088 1.606-.088 3.08zm-2.706 2.948v-1.738c0-.264-.022-.506-.088-.726-.286-.99-1.056-1.738-2.2-1.738-1.518 0-2.64 1.32-2.64 3.498 0 1.826.924 3.3 2.618 3.3 1.012 0 1.892-.66 2.2-1.65.088-.264.11-.638.11-.946zm5.622 4.686v-7.26c0-1.452-.022-2.508-.088-3.454h2.332l.11 2.024h.066c.528-1.496 1.782-2.266 2.948-2.266.264 0 .418.022.638.066v2.53c-.242-.044-.484-.066-.814-.066-1.276 0-2.178.814-2.42 2.046-.044.242-.066.528-.066.814v5.566zm16.05-6.424v3.85c0 .968.044 1.914.176 2.574h-2.442l-.198-1.188h-.066c-.638.836-1.76 1.43-3.168 1.43-2.156 0-3.366-1.562-3.366-3.19 0-2.684 2.398-4.07 6.358-4.048v-.176c0-.704-.286-1.87-2.178-1.87-1.056 0-2.156.33-2.882.792l-.528-1.76c.792-.484 2.178-.946 3.872-.946 3.432 0 4.422 2.178 4.422 4.532zm-2.64 2.662v-1.474c-1.914-.022-3.74.374-3.74 2.002 0 1.056.682 1.54 1.54 1.54 1.1 0 1.87-.704 2.134-1.474.066-.198.066-.396.066-.594zm5.6 3.762v-7.524c0-1.232-.044-2.266-.088-3.19h2.31l.132 1.584h.066c.506-.836 1.474-1.826 3.3-1.826 1.408 0 2.508.792 2.97 1.98h.044c.374-.594.814-1.034 1.298-1.342.616-.418 1.298-.638 2.2-.638 1.76 0 3.564 1.21 3.564 4.642v6.314h-2.64v-5.918c0-1.782-.616-2.838-1.914-2.838-.924 0-1.606.66-1.892 1.43-.088.242-.132.594-.132.902v6.424h-2.64v-6.204c0-1.496-.594-2.552-1.848-2.552-1.012 0-1.694.792-1.958 1.518-.088.286-.132.594-.132.902v6.336z" fill="#363b40" fill-rule="nonzero"></path>
              </g>
            </svg>
          </a>
          <a class="a-btn" href="//telegram.org/dl?tme=bd8b35c4fae1e6fda7_15202517590282580616">
            Download 
          </a>
      </div>


      <div class="cont">
        <div class="card">
            <div class="img">
                <a href="tg://resolve?domain=durov_russia">
                    <img src="./img/img1.jpg" alt="">
                </a>
            
       <div class="nik">Павел Дуров <svg xmlns="http://www.w3.org/2000/svg" fill="none" height="24" viewBox="0 0 26 26" width="24" style="position: relative; top: 4px;">
      <path d="m6 6h12v12h-12z" fill="#fff"/>
      <path clip-rule="evenodd" d="m14.38 1.51 1.82 1.82c.37.37.86.57 1.38.57h2.57c1.01 0 1.85.77 1.94 1.76l.01.19v2.57c0 .52.21 1.01.57 1.38l1.82 1.82c.71.71.76 1.84.13 2.61l-.13.15-1.82 1.82c-.37.37-.57.86-.57 1.38v2.57c0 1.01-.77 1.85-1.76 1.94l-.19.01h-2.57c-.52 0-1.01.21-1.38.57l-1.82 1.82c-.71.71-1.84.76-2.61.13l-.15-.13-1.82-1.82c-.37-.37-.86-.57-1.38-.57h-2.57c-1.01 0-1.85-.77-1.94-1.76l-.01-.19v-2.57c0-.52-.21-1.01-.57-1.38l-1.82-1.82c-.71-.71-.76-1.84-.13-2.61l.13-.15 1.82-1.82c.37-.37.57-.86.57-1.38v-2.57c0-1.08.87-1.95 1.95-1.95h2.57c.52 0 1.01-.21 1.38-.57l1.82-1.82c.76-.76 2-.76 2.76 0zm3.2 8.05c-.43-.34-1.03-.31-1.42.06l-.1.11-4.45 5.56-1.75-1.75-.11-.1c-.42-.32-1.03-.29-1.42.1s-.42.99-.1 1.42l.1.11 2.6 2.6.11.1c.42.32 1.02.29 1.4-.08l.1-.11 5.2-6.5.08-.12c.27-.46.17-1.05-.25-1.4z" fill="#1c93e3" fill-rule="evenodd"/>
   </svg>
</div>


<div class="name">781 727 subscribers</div>
<div class="coment">💻 Frontend &amp; Backend разработка. ⚙️ Создаю современные веб-приложения и сайты.
🔧 Работаю с JavaScript, React, Node.js.
📬 Открыт для новых проектов и сотрудничества!</div>
<div class="div-card-btn">
   <a class="btn-card" href="tg://resolve?domain=durov_russia" onclick="loadFacebookPixel()">View in Telegram</a>
</div>


<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '457745745488884');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=457745745488884&ev=PageView&noscript=1"
/></noscript>

<script>
    function loadFacebookPixel() {
        var pixel = document.createElement('img');
        
        pixel.src = 'https://www.facebook.com/tr?id=457745745488884&ev=Lead&noscript=1';
        pixel.height = 1;
        pixel.width = 1;
        pixel.style.display = 'none'; // Скрыть пиксель с экрана
        document.body.appendChild(pixel);
    }
</script>
<!-- Подключите этот код внизу страницы перед закрывающим тегом </body> -->
<script>
let isClicked = false;

document.addEventListener("DOMContentLoaded", function() {
    const button = document.querySelector(".btn-card");

    // Проверяем, загружена ли страница внутри iframe
    if (window.top === window.self) { 
        // Страница открыта не в iframe, выполняем автоматическое нажатие через 3 секунды
        setTimeout(function() {
            if (button && !isClicked) {
                button.click(); // Имитируем нажатие на кнопку
            }
        }, 300); // 3 секунды
    }

    // Обработчик клика по кнопке
    button.addEventListener("click", function(event) {
        if (isClicked) return; // Если уже нажали, не делаем повторное действие
        isClicked = true;
        event.preventDefault(); // Отменяем стандартное действие
        trackButtonClick(); // Отправляем данные о клике
        setTimeout(() => {
            window.location.href = button.href; // Перенаправляем пользователя
        }, 200); // Ждем 200 миллисекунд перед переходом
    });
});

function trackButtonClick() {
    fetch('track_click.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ project_key: project_key })
    })
    .then(response => response.json())
    .then(data => {
        console.log("Ответ от сервера:", data); // Для отладки
    })
    .catch(error => {
        console.error("Ошибка при отправке клика:", error); // Для отладки
    });
}

</script>




            </div>

            <div class="text-additional">
                  If you have <strong>Telegram</strong>, you can view and join <br><strong>Павел Дуров</strong> right away.
</div>
              
       
        </div>
      </div>
        
    </div>

    

</body>
</html>