<?php

namespace worker;

require_once __DIR__ . '/../vendor/autoload.php';

// Подключаем библиотеку Workerman
use Workerman\Lib\Timer;
use Workerman\Worker;

require_once __DIR__ . '/../config/config.php';

class Chat {

    public static $websocket = PROTOCOL."://".IP_LISTEN.":".PORT;
    public static $worker;
    public static $connections = []; // сюда будем складывать все подключения

    /**
    * You can use the proc_ functions to get better control.
    * You will find it in the manual. Below you find code you might find useful.
    * It works only under windows, you need a different kill routine on linux.
    * he script terminates the (else endless running) ping process after approximatly 5 seconds.
    */
    public static function kill(int $pid, $process, array $pipes): string {
        $return_value = proc_terminate($process);
        //$return_value = stripos(php_uname('s'), 'win') > -1 ? exec("taskkill /F /T /PID $pid") : exec("kill -9 $pid");
        fclose($pipes[0]);
        //fclose($pipes[1]);
        $return_value2 = proc_close($process);
        return "$return_value, $return_value2";
    }

    public static function proc(string $cmd) {
        $descriptorspec = array(
           0 => array("pipe", "r"),  // stdin - канал, из которого дочерний процесс будет читать
           //1 => array("pipe", "w"),  // stdout - канал, в который дочерний процесс будет записывать
           //2 => array("file", "error-output.txt", "a+") // stderr - файл для записи
        );

        // Рабочая директория команды. Это должен быть абсолютный путь к директории или null, если требуется использовать директорию по умолчанию (рабочая директория текущего процесса PHP).
        $cwd = '/';
        // Массив переменных окружения для запускаемой команды или null, если требуется использовать то же самое окружение, что и у текущего PHP-процесса.
        $env = array('some_option' => 'aeiou');

        $process = proc_open($cmd, $descriptorspec, $pipes);

        //$terminate_after = 5; // seconds after process is terminated
        //usleep($terminate_after * 1000000); // wait for 5 seconds

        if (is_resource($process)) {
            // $pipes теперь выглядит так:
            // 0 => записывающий обработчик, подключённый к дочернему stdin
            // 1 => читающий обработчик, подключённый к дочернему stdout
            // Вывод сообщений об ошибках будет добавляться в error-output.txt

            fwrite($pipes[0], '<?php print_r($_ENV); ?>');
            //echo stream_get_contents($pipes[1]);
            //echo '<br>';

            $pstatus = proc_get_status($process);
            echo '$pstatus: ';
            echo '<br>';
            print_r($pstatus);
            $PID = $pstatus['pid'];

            // Важно закрывать все каналы перед вызовом proc_close во избежание мёртвой блокировки
            $return_value = self::kill($PID, $process, $pipes); // вместо proc_terminate($process);

            echo '<br>';
            echo "команда вернула $return_value\n";

            // terminate the process
            $time = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
            echo '<br>Process terminated after: '.$time;

            /*
            Результатом выполнения данного примера будет что-то подобное:
            Array
            (
                [some_option] => aeiou
                [PWD] => /tmp
                [SHLVL] => 1
                [_] => /usr/local/bin/php
            )
            команда вернула 0
            */
        }
    }

    public static function start(): void {
        exec('php '.SERVER_PATH); // server.php
        //self::proc('php');
        //self::proc('php '.SERVER_PATH);
    }

    public static function stop(): void {
        passthru("ps ax | grep ".SERVER_PATH, $output); // server.php
        $ar = preg_split('/ /', $output);
        print_r($ar);
        if (in_array('/usr/bin/php', $ar)) {
            $pid = (int) $ar[0];
            echo $pid;
            //posix_kill($pid, SIGKILL);
        }
    }

    public static function run() {
        self::$worker = new Worker(self::$websocket);
        self::onWorkerStart(self::$connections);
        self::onConnect(self::$connections);
        self::onClose(self::$connections);
        self::onMessage(self::$connections);
        Worker::runAll();
    }

    public static function onWorkerStart(&$connections) {
        self::$worker->onWorkerStart = function($worker) use (&$connections) {
            $interval = 5; // пингуем каждые 5 секунд
            Timer::add($interval, function() use (&$connections) {
                foreach ($connections as $c) {
                    // Если ответ не пришел 3 раза, то удаляем соединение из списка
                    // и оповещаем всех участников об "отвалившемся" пользователе
                    if ($c->pingWithoutResponseCount >= 3) {
                        unset($connections[$c->id]);
                
                        $messageData = [
                            'action' => 'ConnectionLost',
                            'userId' => $c->id,
                            'userName' => $c->userName,
                            'gender' => $c->gender,
                            'userColor' => $c->userColor
                        ];
                        $message = json_encode($messageData);
                
                        $c->destroy(); // уничтожаем соединение
                
                        foreach ($connections as $c) {
                            $c->send($message);
                        }
                    }
                    else {
                        $c->send('{"action":"Ping"}');
                        $c->pingWithoutResponseCount++; // увеличиваем счетчик пингов
                    }
                }
            });
        };
    }

    public static function onConnect(&$connections) {
        self::$worker->onConnect = function($connection) use (&$connections) {
            // Эта функция выполняется при подключении пользователя к WebSocket-серверу
            $connection->onWebSocketConnect = function($connection) use (&$connections) {
                // Достаём имя пользователя, если оно было указано
                if (isset($_GET['userName'])) {
                    $originalUserName = preg_replace('/[^a-zA-Zа-яА-ЯёЁ0-9\-\_ ]/u', '', trim($_GET['userName']));
                }
                else {
                    $originalUserName = 'Инкогнито';
                }
        
                // Половая принадлежность, если указана
                // 0 - Неизвестный пол
                // 1 - М
                // 2 - Ж
                if (isset($_GET['gender'])) {
                    $gender = (int) $_GET['gender'];
                }
                else {
                    $gender = 0;
                }
        
                if ($gender != 0 && $gender != 1 && $gender != 2) 
                    $gender = 0;
        
                // Цвет пользователя
                if (isset($_GET['userColor'])) {
                    $userColor = $_GET['userColor'];
                }
                else {
                    $userColor = "#000000";
                }
                
                // Проверяем уникальность имени в чате
                $userName = $originalUserName;
        
                $num = 2;
                do {
                    $duplicate = false;
                    foreach ($connections as $c) {
                        if ($c->userName == $userName) {
                            $userName = "$originalUserName ($num)";
                            $num++;
                            $duplicate = true;
                            break;
                        }
                    }
                } 
                while($duplicate);
        
                // Добавляем соединение в список
                $connection->userName = $userName;
                $connection->gender = $gender;
                $connection->userColor = $userColor;
                $connection->pingWithoutResponseCount = 0; // счетчик безответных пингов
        
                $connections[$connection->id] = $connection;
        
                // Собираем список всех пользователей
                $users = [];
                foreach ($connections as $c) {
                    $users[] = [
                        'userId' => $c->id,
                        'userName' => $c->userName, 
                        'gender' => $c->gender,
                        'userColor' => $c->userColor
                    ];
                }
        
                // Отправляем пользователю данные авторизации
                $messageData = [
                    'action' => 'Authorized',
                    'userId' => $connection->id,
                    'userName' => $connection->userName,
                    'gender' => $connection->gender,
                    'userColor' => $connection->userColor,
                    'users' => $users
                ];
                $connection->send(json_encode($messageData));
        
                // Оповещаем всех пользователей о новом участнике в чате
                $messageData = [
                    'action' => 'Connected',
                    'userId' => $connection->id,
                    'userName' => $connection->userName,
                    'gender' => $connection->gender,
                    'userColor' => $connection->userColor
                ];
                $message = json_encode($messageData);
        
                foreach ($connections as $c) {
                    $c->send($message);
                }
            };
        };
    }

    public static function onClose(&$connections) {
        self::$worker->onClose = function($connection) use (&$connections) {
            // Эта функция выполняется при закрытии соединения
            if (!isset($connections[$connection->id])) {
                return;
            }
    
            // Удаляем соединение из списка
            unset($connections[$connection->id]);
    
            // Оповещаем всех пользователей о выходе участника из чата
            $messageData = [
                'action' => 'Disconnected',
                'userId' => $connection->id,
                'userName' => $connection->userName,
                'gender' => $connection->gender,
                'userColor' => $connection->userColor
            ];
            $message = json_encode($messageData);
    
            foreach ($connections as $c) {
                $c->send($message);
            }
        };
    }

    public static function onMessage(&$connections) {
        self::$worker->onMessage = function($connection, $message) use (&$connections) {
            $messageData = json_decode($message, true);
            $toUserId = isset($messageData['toUserId']) ? (int) $messageData['toUserId'] : 0;
            $action = isset($messageData['action']) ? $messageData['action'] : '';
    
            if ($action == 'Pong') {
                // При получении сообщения "Pong", обнуляем счетчик пингов
                $connection->pingWithoutResponseCount = 0;
            }
            else {
                // Дополняем сообщение данными об отправителе
                $messageData['userId'] = $connection->id;
                $messageData['userName'] = $connection->userName;
                $messageData['gender'] = $connection->gender;
                $messageData['userColor'] = $connection->userColor;
        
                // Преобразуем специальные символы в HTML-сущности в тексте сообщения
                $messageData['text'] = htmlspecialchars($messageData['text']);
                // Заменяем текст заключенный в фигурные скобки на жирный
                $messageData['text'] = preg_replace('/\{(.*)\}/u', '<b>\\1</b>', $messageData['text']);
        
                if ($toUserId == 0) {
                    // Отправляем сообщение всем пользователям
                    $messageData['action'] = 'PublicMessage';
                    foreach ($connections as $c) {
                        $c->send(json_encode($messageData));
                    }
                }
                else {
                    $messageData['action'] = 'PrivateMessage';
                    if (isset($connections[$toUserId])) {
                        // Отправляем приватное сообщение указанному пользователю
                        $connections[$toUserId]->send(json_encode($messageData));
                        // и отправителю
                        $connections->send(json_encode($messageData));
                    }
                    else {
                        $messageData['text'] = 'Не удалось отправить сообщение выбранному пользователю';
                        $connection->send(json_encode($messageData));
                    }
                }
            }
        };
    }

}
