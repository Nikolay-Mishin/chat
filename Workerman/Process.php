<?php

namespace worker;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

class Process {
    
    public int $terminate_after = 5; // seconds after process is terminated
    public $process;
    public ?string $pkey;
    public int $pid;
    public array $pstatus;

    public string $cmd;
    public array $descriptorspec = array(
        0 => array("pipe", "r"),  // stdin - канал, из которого дочерний процесс будет читать
        //1 => array("pipe", "w"),  // stdout - канал, в который дочерний процесс будет записывать
        //2 => array("file", "error-output.txt", "a+") // stderr - файл для записи
    );
    public ?array $pipes;
    // Рабочая директория команды. Это должен быть абсолютный путь к директории или null, если требуется использовать директорию по умолчанию (рабочая директория текущего процесса PHP).
    public string $cwd = '/tmp';
    // Массив переменных окружения для запускаемой команды или null, если требуется использовать то же самое окружение, что и у текущего PHP-процесса.
    public array $env = array('some_option' => 'aeiou');

    public string $output = '';
    public string $result = '';

    public function __construct(string $cmd, ?string $key = null, ?array $descriptorspec = null, ?string $cwd = null, ?array $env = null, ?int $terminate_after = null) {
        $this->pkey = $key;
        $this->cmd = $cmd;
        $this->descriptorspec = $descriptorspec ?? $this->descriptorspec;
        $this->cwd = $cwd ?? $this->cwd;
        $this->env = $env ?? $this->env;
        $this->terminate_after = $this->terminate_after ?? $terminate_after;

        $this->process = proc_open($this->cmd, $this->descriptorspec, $this->pipes, /*$this->cwd, $this->env*/);

        debug($this->process);

        //usleep($this->terminate_after * 1000000); // wait for 5 seconds

        if (is_resource($this->process)) {
            // $pipes теперь выглядит так:
            // 0 => записывающий обработчик, подключённый к дочернему stdin
            // 1 => читающий обработчик, подключённый к дочернему stdout
            // Вывод сообщений об ошибках будет добавляться в error-output.txt

            fwrite($this->pipes[0], '<?php print_r($_ENV); ?>');
            //$this->output = stream_get_contents($pipes[1])
            //debug($this->output);

            $this->pstatus = proc_get_status($this->process);
            debug('$pstatus: ');
            debug($this->pstatus);
            $this->pid = $this->pstatus['pid'];

            // terminate the process
            $time = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
            debug('Process terminated after: '.$time);
            
            /*
            Результатом выполнения данного примера будет что-то подобное:
            Array
            (
                [some_option] => aeiou
                [PWD] => /tmp
                [SHLVL] => 1
                [_] => /usr/local/bin/php
            )
            */
        }
    }

    public static function add(string $cmd, ?string $key = null, ?array $descriptorspec = null, ?string $cwd = null, ?array $env = null, ?int $terminate_after = null): self {
        $process = new self($cmd, $key, $descriptorspec, $cwd, $env, $terminate_after);
        if (!isset($_SESSION['process'])) {
            $_SESSION['process'] = [];
        }
        return $_SESSION['process'][$process->getPkey()] = $process;
    }
    
    public static function killProc() {
        if (isset($_SESSION['process']) && isset($_SESSION['process'][$pkey])) {
            $process = $_SESSION['process'][$pkey];
            $process->kill();
            debug("команда вернула $process->result");
        }
    }

    /**
    * You can use the proc_ functions to get better control.
    * You will find it in the manual. Below you find code you might find useful.
    * It works only under windows, you need a different kill routine on linux.
    * he script terminates the (else endless running) ping process after approximatly 5 seconds.
    */
    public function kill(): string {
        $return_value = proc_terminate($this->process);
        //$return_value = stripos(php_uname('s'), 'win') > -1 ? exec("taskkill /F /T /PID $this->pid") : exec("kill -9 $this->pid"); // вместо proc_terminate($this->process);
        // Важно закрывать все каналы перед вызовом proc_close во избежание мёртвой блокировки
        foreach ($this->pipes as $pipe) {
            fclose($pipe);
        }
        $return_value2 = proc_close($this->process);
        if (isset($_SESSION['process']) && isset($_SESSION['process'][$this->getPkey()])) {
            unset($_SESSION['process'][$this->getPkey()]);
        }
        return $this->result = "$return_value, $return_value2";
    }
    
    public function getPkey() {
        return $this->pkey ?? $this->pid;
    }

}
