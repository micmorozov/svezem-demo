<?php

namespace common\components\targets;

use common\components\version\Version;
use Exception;
use Monolog\Logger;
use Yii;
use yii\base\InvalidConfigException;
use yii\di\NotInstantiableException;
use yii\log\Target;
use yii\web\HttpException;

/**
 * Class ElkTarget
 * @package common\components\targets
 */
class ElkTarget extends Target
{
    public $prefixName;

    /**
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    public function export()
    {
        $systemInfo = [
            'server' => $_SERVER,
            'post' => $_POST,
            'get' => $_GET,
            'cookie' => $_COOKIE
        ];

        if (isset($_SESSION)) {
            $systemInfo['session'] = $_SESSION;
        }
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $systemInfo['ip'] = $_SERVER['REMOTE_ADDR'];
        }
        if (isset($this->prefixName)) {
            $systemInfo['prefix'] = $this->prefixName;
        }

        /** @var Logger $logger */
        $logger = Yii::$container->get(Logger::class);
        $logger = $logger->withName('log');

        $messages = $this->messages;

        //Последнее сообщение это системная информация
        //поэтому удаляем
        array_pop($messages);

        foreach ($messages as $index => $message) {
            $text = $message[0];
            $level = $message[1];
            $timestamp = $message[3];

            if ($text instanceof Exception) {
                /** @var Exception $message */
                $error = [
                    'code' => $text->getCode(),
                    'msg' => $text->getMessage(),
                    'file' => $text->getFile(),
                    'line' => $text->getLine(),
                    'trace' => $text->getTraceAsString()
                ];

                if( $text instanceof HttpException){
                    $error['statusCode'] = $text->statusCode;
                }
            } elseif (is_array($text)) {
                $error = [
                    'msg' => $text
                ];
            } else {
                $error = [
                    'msg' => (string)$text//$this->formatMessage($message)
                ];
            }

            $log = array_merge($systemInfo, [
                'error' => $error,
                'timestamp' => date('c', $timestamp)
            ]);

            switch ($level) {
                case \yii\log\Logger::LEVEL_ERROR:
                    $exec = 'error';
                    break;
                case \yii\log\Logger::LEVEL_INFO:
                    $exec = 'info';
                    break;
                case \yii\log\Logger::LEVEL_WARNING:
                    $exec = 'warning';
                    break;
            }

            call_user_func([$logger, $exec], null, $log);
        }
    }
}
