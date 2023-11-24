<?php

namespace console\helpers\tomita\interfaces;

class LocalTomitaQuering implements TomitaQuering
{
    /**
     * @var string $execPath Расположение исполняемого файла
     */
    private $execPath;

    public function __construct($execPath)
    {
        $this->execPath = $execPath;
    }

    /**
     * @param $text
     * @param $config
     * @return string
     */
    public function query($text, $config):string
    {
        $cmd = "./tomita-parser $config";

        $descriptorspec = [
            0 => ["pipe", "r"],  // stdin is a pipe that the child will read from
            1 => ["pipe", "w"],  // stdout is a pipe that the child will write to
            2 => ["pipe", 'w']  // stderr
        ];

        $process = proc_open($cmd, $descriptorspec, $pipes, $this->execPath);

        $result = false;

        if (is_resource($process)) {
            fwrite($pipes[0], $text);
            fclose($pipes[0]);

            $result = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            proc_close($process);
        }

        return $result;
    }
}
