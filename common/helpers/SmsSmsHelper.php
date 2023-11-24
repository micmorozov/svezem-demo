<?php
/**
 * Хелпер для работы с СМС агрегатором SMS.ru
 */

namespace common\helpers;

use GuzzleHttp\Client;
use Yii;

class SmsSmsHelper
{
    //Запрос выполнен или сообщение находится в нашей очереди
    const CODE_COMPLETED = 100;
    //Неправильно указан номер телефона получателя, либо на него нет маршрута
    const CODE_INCORRECT_NUMBER = 202;
    //Вы добавили этот номер (или один из номеров) в стоп-лист
    const CODE_STOP_LIST = 209;
    //Превышен лимит одинаковых сообщений на этот номер в минуту
    const CODE_EXCEEDED_LIMIT = 231;

    /**
     * Ключ авторизации. Берется в аккаунте
     * @var string
     */
    public $api_id = '';

    /**
     * Имя отправителя
     * @var string
     */
    public $from = '';

    /**
     * Имитирует отправку сообщения для тестирования ваших программ на правильность обработки ответов сервера.
     * @var int
     */
    public $test = 1;

    /**
     * Пишет сообщения в файл
     * @var int
     */
    public $toFile = 0;

    /**
     * Путь до папки с смс сообщениями
     * @var string
     */
    public $fileTransportPath = '@runtime/sms';

    private $api_url = 'http://sms.ru';

    /**
     * Инициализация клиента
     */
    private function getClient(){
        return new Client([
            // Base URI is used with relative requests
            'base_uri' => $this->api_url,
            // You can set any number of default request options.(sec)
            'timeout' => 30.0,
        ]);
    }

    /**
     * Отправляем СМС сообщене на номер
     * @param $to Номер получателя в федеральном формате
     * @param $message Текст сообщения
     * @param $translit Транслитерация сообщения. -1 автоопределение.
     * @param $time Время, когда отправить сообщение.  Указывается в формате UNIX TIME.  Должно быть не больше 7 дней с момента подачи запроса
     */
    public function smsSend($to, $message, $translit = 0, $time = 0){
        // Если автоопределение транслита
        if($translit == -1) $translit = mb_strlen($message) > 70 ? 1 : 0;

        $query = [
            'api_id' => $this->api_id,
            'to' => $to,
            'text' => $message,
            'from' => $this->from,
            'time' => $time,
            'translit' => $translit,
            'test' => $this->test
        ];
        // Если надо писать в файл
        if($this->toFile){
            $this->smsSaveToFile($query);

            return true;
        }

        $client = $this->getClient();
        $response = $client->post('/sms/send', ['query' => $query]);
        $resp = explode("\n", $response->getBody()->getContents());
        if($resp && $resp[0] == self::CODE_COMPLETED){
            return true;
        } else{
            $this->log($resp, $to, $message);
        }

        return false;
    }

    /**
     * Запись СМС в файл вместо отправки
     */
    private function smsSaveToFile($query){
        $path = Yii::getAlias($this->fileTransportPath);
        if( !is_dir($path)){
            mkdir($path, 0777, true);
        }

        $file = $path.'/'.time().'-'.rand(0, 99999).'.sms';

        file_put_contents($file, print_r($query, 1));
    }

    /**
     * @param $resp
     * @param $to
     * @param $message
     */
    private function log($resp, $to, $message){
        $logMsg = "Не удалось отправить СМС на номер {$to}\nтекст:'{$message}'\nОтвет сервера:".print_r($resp, 1);

        switch($resp[0]){
            case self::CODE_STOP_LIST: Yii::info($logMsg); break;
            case self::CODE_INCORRECT_NUMBER:
            case self::CODE_EXCEEDED_LIMIT: Yii::warning($logMsg); break;
            default: Yii::error($logMsg);
        }
    }
}