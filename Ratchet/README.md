# Websocket

`WebSocket` — протокол для обмена сообщениями между браузером и веб-сервером в режиме реального времени. Т.е. он позволяет увеличить скорость обмена данными между сервером и клиентом за счет более легкого, нежели HTTP протокола и организации постоянного соединения. Читать [википедию](https://ru.wikipedia.org/wiki/WebSocket) или доки мозиллы ([En](https://developer.mozilla.org/en-US/docs/Web/API/WebSockets_API) или [Ru](https://developer.mozilla.org/ru/docs/WebSockets)). Проверить поддержку WebSocket в браузере можно введением названия одноименного конструктора в консоли веб-клиента, большинство браузеров найдут существующий объект. Основные задачи использования сокетов – задачи реального времени. Чаты, уведомления, игровые клиенты, онлайн слежение за показателями.

### Создание WebSocket клиента

[![youtu.be](https://img.youtube.com/vi/AEpq8gggwLk/0.jpg)](https://www.youtube.com/watch?v=AEpq8gggwLk)

Давайте создадим клиента (**WebSocket-клиента**) для работы на сокетах.

Создадим `index.html` со стандартным набором тегов и HTML-форму внутри. При помощи Emmet можно развернуть строку (Посмотреть [как работает Emmet](http://htmllab.ru/emmet/)):

```html
!>form[name=messages]>.row*3>input^^#status
```

Получится HTML-код

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Document</title>
</head>
<body>
  <form action="" name="messages">
    <div class="row"><input type="text"></div>
    <div class="row"><input type="text"></div>
    <div class="row"><input type="text"></div>
  </form>
  <div id="status"></div>
</body>
</html>
```

Поправим форму, внеся название полям и изменяя последнее однострочное текстовое поле на кнопку отправки формы.

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Document</title>
</head>
<body>
  <h1>Пример работы с WebSocket</h1>
  <form action="" name="messages">
    <div class="row">Имя: <input type="text"></div>
    <div class="row">Текст: <input type="text"></div>
    <div class="row"><input type="submit" value="Поехали"></div>
  </form>
  <div id="status"></div>
  <script>
    
  </script>
</body>
</html>
```

В разделе `script` создадим переменную `socket` [на основе конструктора WebSocket](https://developer.mozilla.org/ru/docs/WebSockets/Writing_WebSocket_client_applications#Примеры). В качестве аргумента передадим URL с протоколом «ws» (а для использования защищенного соединения используется wss, при этом нужно не забывать о SSL сертификате).

```js
var socket = new WebSocket("ws://echo.websocket.org");
```

Адрес ws://echo.websocket.org указывает на расположение Websoket-сервера. Сейчас это эхо-сервер, он отвечает сообщением, которые мы будем ему отправлять.

Получим ссылку на HTML-элемент с идентификатором «status» для отображения дальнейшего статуса работы (Посмотреть [как работает document.querySelectorAll()](http://htmllab.ru/queryselectorall/)  ):

```js
var status = document.querySelector("#status");
```

У объекта socket есть четыре основных события:

* `onopen` — при установке/открытии соединения
* `onclose` – при закрытии соединения
* `onmessage` – при получении данных
* `onerror` – при возникновении ошибки

Закрытие соединения может происходить по плану, когда мы явно отключаемся от сокета, или не запланированно, при проблемах связи или прекращении работы сокет-сервера. Выяснить было ли соединение закрыто по плану или нет, можно при помощи свойства `event.wasClean` объекта события.

Примечания: работу по созданию соединения нужно проводить после полной загрузки окна, иначе можно при отправке сообщения на тестовый сокет-сервер можно получить **Uncaught DOMException: Failed to execute ‘send’ on ‘WebSocket’: Still in CONNECTING state**. CONNECTING – это одна из констант, которые назначаются свойству `readyState` объекта `WebSocket`.

| Type					   | Code | Desc |
| ------------------------ |:----:| ---- |
| CONNECTING (ПОДКЛЮЧЕНИЕ) | 0	  | Подключение еще не открыто. |
| OPEN (ОТКРЫТО)		   | 1	  | Соединение открыто и готово к работе. |
| CLOSING (ЗАКРЫТИЕ)	   | 2	  | Соединение находится в процессе закрытия. |
| CLOSED (ЗАКРЫТО)		   | 3	  | Соединение закрыто или не может быть открыто. |

Полное содержимое раздела `script`, при котором должна появиться строка статуса «соединение установлено».

```js
window.onload = function(){
  var socket = new WebSocket("ws://echo.websocket.org");
  var status = document.querySelector("#status");

  socket.onopen = function() {
    status.innerHTML = "cоединение установлено";
  };

  socket.onclose = function(event) {
    if (event.wasClean) {
      status.innerHTML = 'cоединение закрыто';
    } else {
      status.innerHTML = 'соединения как-то закрыто';
    }
    status.innerHTML += '<br>код: ' + event.code + ' причина: ' + event.reason;
  };

  socket.onmessage = function(event) {
    status.innerHTML = "пришли данные " + event.data;
  };

  socket.onerror = function(event) {
    status.innerHTML = "ошибка " + event.message;
  };
}
```

Для отправки сообщения в вебсокет, используется метод send объекта WebSocket. Метод принимает строковый аргумент. Добавим имена нашим текстовым полям:

```html
<form action="" name="messages">
  <div class="row">Имя: <input type="text" name="fname"></div>
  <div class="row">Текст: <input type="text" name="msg"></div>
  <div class="row"><input type="submit" value="Поехали"></div>
</form>
```

и пропишем реакцию на отправку формы, помещая в send() имя и текст отправителя:

```js
//в рамках onload
document.forms["messages"].onsubmit = function(){
  let fname = this.fname.value;
  let msg   = this.msg.value;
  socket.send(`${fname} ${msg}`);
  return false;
}
```

let, обратные косые кавычки и знак доллара с фигурными скобками – это все стиль JavaScript2015. Если вы ещё не сталкивались с ним, то код var вместо let также будет  работать (let – оператор объявления переменной с блочным уровнем видимости, обратные косые кавычки позволяют создавать мультистроки, т.е. строки с переносами строки при редактировании текста, ${} – возможность подстановки переменных в строку без указания бесчисленного количества операторов склеивания +):

```js
document.forms["messages"].onsubmit = function(){
  var fname = this.fname.value;
  var msg   = this.msg.value;
  socket.send(fname + ' ' + msg);
  return false;
}
```

Но в нашем примере останется первый способ.

Теперь при отправке данных из формы, ниже будет появляться текст «пришли данные» и далее будет идти фрагмент самими данными с эхо-сервера.

### Отправка данных в формате JSON
При разработке часто требуется отправлять и принимать структурированные данные. Даже в нашей простой форме при отправке имени пользователя и текста сообщения, не структурируя данные, мы чувствуем неудобство: как сервер будет их использовать при необходимости вставить данные в базу. А если это будут координаты элемента на странице, как в случае с играми? Этот и другие вопросы заставляют нас обратить внимание на формат JSON.

Перепишем код нашего клиента так, чтобы он отправлял данные в этом формате, а при получении строки от эхо-сервера, конвертировал её снова в JSON.

Будем использовать методы встроенного в JavaScript объекта JSON:

* `JSON.stringify(obj)` конвертация JavaScript-объекта (на самом деле и массив можно) в строку
* `JSON.parse(string)` получение из строки объекта в JSON-нотации

```js
document.forms["messages"].onsubmit = function(){
  let message = {
    name:this.fname.value,
    msg: this.msg.value
  }

  socket.send(JSON.stringify(message));
    return false;
  }

  socket.onmessage = function(event) {
    let message = JSON.parse(event.data);
    status.innerHTML = `пришли данные: <b>${message.name}</b>: ${message.msg}`;
  };
```

Теперь на эхо-сервер отправляется JSON сериализованный в строку, а назад получается строка, из которой мы опять получаем JS-объект. Посмотреть реализацию вебсокета на Codepen.



# Websocket Ratchet

В [материале о websocket](http://htmllab.ru/websocket/) мы рассмотрели построение вебсокет-клиента на JavaScript. В качестве Websocket-сервера использовался сторонний эхо-сервер. Для создания собственного WebSocket сервера, нам понадобиться решение на PHP или JavaScript (или на С++, Java, Scala и т.д.)

На работающей сборке **OpenServer** (можно использовать WAMP или др. сборку), создадим хост `websocket.host` и перезапустим сборку для обновления файла `hosts` (`C:/Windows/System32/Drivers/etc/hosts`), чтобы в браузер «увидел» наш сайт.

Познакомимся с Ratchet, решением для организации работы с вебсокетами на PHP.

### Создание WebSocket-сервера на Ratchet

[![youtu.be](https://img.youtube.com/vi/-rdZi_yDPLQ/0.jpg)](https://www.youtube.com/watch?v=-rdZi_yDPLQ)

Для установки **Ratchet** нам понадобиться установленный [Composer](https://getcomposer.org/). Если вы не знакомы с `Composer`, срочно восполните этот пробел: перейдите по адресу [https://getcomposer.org/download/](https://getcomposer.org/download/), выполните команды начинающиеся с «php», а потом пропишите адрес к утилите в системной переменной PATH. Или [скачайте composer.phar](https://getcomposer.org/download/). Создайте файл `composer.json` внутри папки `websocket.host` и заполните его зависимостями, как указано тут:

```
{
  "autoload": {
    "psr-0": {
      "MyApp": "src"
    }
  },
  "require": {
    "cboden/ratchet": "0.3.*"
  }
}
```

После установки `Composer` и описания файла `.json`, нужно запустить установку `Ratchet` через командную строку: `php composer.phar install`

В результате появится папка `vendor` с необходимыми компонентами/библиотеками. Если интересно, можно посмотреть подробней об [автозагрузке классов в PHP](http://htmllab.ru/oop-php/) и [работе с  пространствами имён в PHP](http://htmllab.ru/php-namespace/).

В корне сайта создадим `src/MyApp/Chat.php`:

```php
namespace MyApp;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

 
class Chat implements MessageComponentInterface {

public function onOpen(ConnectionInterface $conn) {    }

public function onMessage(ConnectionInterface $from, $msg) {    }

public function onClose(ConnectionInterface $conn) {    }

public function onError(ConnectionInterface $conn, \Exception $e){ }
}
```

И в корне создадим `chat-server.php`:


```php
use Ratchet\Server\IoServer;
use MyApp\Chat;
require '/vendor/autoload.php';

$server = IoServer::factory(
  new Chat(),
  8080
);
$server->run();
```

Сам же чат обновим кодом:

```php
namespace MyApp;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
 
class Chat implements MessageComponentInterface {
  protected $clients;

  public function __construct() {
    $this->clients = new \SplObjectStorage;
  }

  public function onOpen(ConnectionInterface $conn) {
    // Store the new connection to send messages to later
    $this->clients->attach($conn);
    echo "New connection! ({$conn->resourceId})\n";
  }

  public function onMessage(ConnectionInterface $from, $msg) {
    $numRecv = count($this->clients) - 1;
    echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
    , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');

    foreach ($this->clients as $client) {
      if ($from !== $client) {
      // The sender is not the receiver, send to each client connected
      $client->send($msg);
      }
    }
  }

  public function onClose(ConnectionInterface $conn) {
    // The connection is closed, remove it, as we can no longer send it messages
    $this->clients->detach($conn);
    echo "Connection {$conn->resourceId} has disconnected\n";
  }

  public function onError(ConnectionInterface $conn, \Exception $e) {
    echo "An error has occurred: {$e->getMessage()}\n";
    $conn->close();
  }
}
```

При запуске в консоли  «php char-server.php» консоль будет «висеть» — сервер работает.

Остановим сервер Ctrl+C и изменим файл сервера:

``php
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use MyApp\Chat;

require  '/vendor/autoload.php';

$server = IoServer::factory(
  new HttpServer(
    new WsServer(
      new Chat()
    )
  ),
 8080
);

$server->run();
```

Настало время проверить работу. Поменяем адрес websocket сервера:

```js
var socket = new WebSocket("ws://localhost:8080");
```

Запустим `php char-server.php` и в форме внесём тестовые данные. В консольном окне мы должны увидеть приходящие сообщения. Если запустить вторую вкладку браузера, то станет возможным переписываться между двумя вкладками, одновременно видеть переписку в виде строк с JSON в консоли!

Чтобы сообщения накапливались подобно чату, нужно поменять строку в методе onmessage:

```js
status.innerHTML += `пришли данные: <b>${message.name}</b>: ${message.msg}<br>`;
```

Если вы захотите сделать так, чтобы и отправитель тоже видел свои сообщения, нужно будет закомментировать фрагмент кода Chat.php:

```js
foreach ($this->clients as $client) {
  // if ($from !== $client) {
  // Отправитель не является получателем, отправляем каждому подключенному клиенту
    $client->send($msg);
  // }
}
```

Поздравляю! У нас есть работающий **Websocket Ratchet** сервер!
