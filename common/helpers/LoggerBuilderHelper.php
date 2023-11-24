<?php
namespace common\helpers;

use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Yii;

class LoggerBuilderHelper
{
    /**
     * Инициализирует Monolog/Logger для логов платежей
     * @param $channelName Имя канала
     * @param $filename Имя файла для лога
     * @return Logger
     * @throws Exception
     */
    public static function getPaymentLogger($channelName, $filename = 'payment.log'): Logger
    {
        // Уровень логгирования
        $logLevel = YII_ENV_DEV ? Logger::DEBUG : Logger::WARNING;

        $logger = new Logger($channelName, [
            new StreamHandler(Yii::getAlias("@runtime/logs/{$filename}"),  $logLevel)
        ]);

        return $logger;
    }

    /**
     * Инициализирует Monolog/Logger для логов MatrixContent
     * @param $channelName Имя канала
     * @param $filename Имя файла для лога
     * @return Logger
     * @throws Exception
     */
    public static function getMatrixContentLogger($channelName, $filename = 'matrix-content.log'): Logger
    {
        // Уровень логгирования
        $logLevel = YII_ENV_DEV ? Logger::DEBUG : Logger::WARNING;

        $logger = new Logger($channelName, [
            new StreamHandler(Yii::getAlias("@runtime/logs/{$filename}"),  $logLevel)
        ]);

        return $logger;
    }
}