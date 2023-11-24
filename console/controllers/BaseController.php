<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 15.06.18
 * Time: 11:12
 */

namespace console\controllers;

use console\helpers\CronLocker;
use Svezem\Services\LockerService\Locker\RedisLocker;
use Svezem\Services\LockerService\LockerService;
use yii\console\Controller;
use Yii;

abstract class BaseController extends Controller
{
    /** @var LockerService  */
    private $lockerService;

    public function __construct($id, $module, $config = [])
    {
        $this->lockerService = Yii::$container->get('cronLockerService');

        parent::__construct($id, $module, $config);
    }

    public function beforeAction($action)
    {
        if( !parent::beforeAction($action) )
            return false;

        $lockName = get_called_class().':'.$action->id;
        if(!$this->lockerService->acquire($lockName, 30000)){
            echo 'Process already running. Stop working!' . PHP_EOL;
            Yii::info("Не удалось запустить команду: {$action->id}. Другой крон работает");
            return false;
        }

        return true;
    }

    public function afterAction($action, $result)
    {
        $result = parent::afterAction($action, $result);

        $this->lockerService->release();

        return $result;
    }

    public static function progress_bar($done, $total, $info="", $width=50) {
        $perc = round(($done * 100) / $total);
        $bar = round(($width * $perc) / 100);
        return sprintf("%s%%[%s>%s]%s\r", $perc, str_repeat("=", $bar), str_repeat(" ", $width-$bar), $info);
    }
}
