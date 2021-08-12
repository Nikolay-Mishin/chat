<?php

namespace worker;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

// Подключаем библиотеку Workerman
use Workerman\Lib\Timer;
use Workerman\Worker;

class Chat {

    public static string $websocket = PROTOCOL."://".IP_LISTEN.":".PORT;
    public static Worker $worker;
    public static array $connections = []; // сюда будем складывать все подключения
    public static ?Process $process = null;
    public static string $pkey = 'chat';

    public static function start(): void {
        //exec('php '.SERVER_PATH); // server.php
        Process::add('php', self::$pkey);
        //new Process('php '.SERVER_PATH);
        debug($_SESSION['process']);
    }

    public static function stop(): void {
        passthru("ps ax | grep ".SERVER_PATH, $output); // server.php
        $ar = preg_split('/ /', $output);
        debug($ar);
        if (in_array('/usr/bin/php', $ar)) {
            $pid = (int) $ar[0];
            echo $pid;
            //posix_kill($pid, SIGKILL);
        }
        if (isset($_SESSION['process']) && isset($_SESSION['process'][self::$pkey])) {
            unset($_SESSION['process'][self::$pkey]);
        }
    }

    public static function run(): void {
        self::$worker = new Worker(self::$websocket);
        self::onWorkerStart(self::$connections);
        self::onConnect(self::$connections);
        self::onClose(self::$connections);
        self::onMessage(self::$connections);
        Worker::runAll();
    }

    public static function onWorkerStart(array &$connections): void {
        self::$worker->onWorkerStart = function(Worker $worker) use (&$connections): void {
            $interval = 5; // пингуем каждые 5 секунд
            Timer::add($interval, function() use (&$connections): void {
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

    public static function onConnect(array &$connections): void {
        self::$worker->onConnect = function(Worker $connection) use (&$connections): void {
            // Эта функция выполняется при подключении пользователя к WebSocket-серверу
            $connection->onWebSocketConnect = function(Worker $connection) use (&$connections) {
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
                while ($duplicate);
        
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

    public static function onClose(array &$connections): void {
        self::$worker->onClose = function(Worker $connection) use (&$connections) {
            // Эта функция выполняется при закрытии соединения
            if (!isset($connections[$connection->id])) {
                return null;
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

    public static function onMessage(array &$connections) {
        self::$worker->onMessage = function(Worker $connection, string $message) use (&$connections): void {
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
