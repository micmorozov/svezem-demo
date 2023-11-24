<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 14.11.17
 * Time: 9:14
 *
 * Пример: php yii gearman-console -j=.... -w=console\\jobs\\...
 * --j - Наименование очереди с задачами
 * --w - Класс, который обрабатывает эту очередь
 * --m - Количество задач, которое будет обработано за один запуск скрипта. По умолчанию 1000
 */

namespace console\controllers;

use micmorozov\yii2\gearman\JobBase;
use micmorozov\yii2\gearman\Worker;
use Yii;
use yii\console\Controller;
use GearmanWorker;
use GearmanJob;

use yii\console\ExitCode;
use yii\helpers\Console;

class GearmanConsoleController extends Controller
{
    /** @var $worker GearmanWorker */
    protected $worker;

    /** @var $job JobBase */
    protected $job;

    /** @var  $jobName string */
    public $jobName;

    /** @var  $workerClass string */
    public $workerClass;

    /** @var $maxTasks integer */
    public $maxTasks = 1000;

    /** @var  $remainTask integer */
    protected $remainTask;

    public function init(){
        parent::init();

        $worker = new Worker(Yii::$app->gearman->getConfig());
        $this->worker = $worker->getWorker();
        $this->worker->addOptions(GEARMAN_WORKER_NON_BLOCKING);
    }

    public function options($actionID)
    {
        return ['jobName', 'workerClass', 'maxTasks'];
    }

    public function optionAliases()
    {
        return [
            'j' => 'jobName',
            'w' => 'workerClass',
            'm' => 'maxTasks'
        ];
    }

    public function afterAction($action, $result){
        if( $result = parent::afterAction($action, $result) )
            return $result;

        $this->remainTask = $this->maxTasks;

        $job = $this->job;
        $this->worker->addFunction($this->jobName, function (GearmanJob $gearmanJob) use ($job){
            $retval = $job->execute($gearmanJob);
            return serialize($retval);
        });

        $worker = $this->worker;
        while( $worker->work() ||
            $worker->returnCode() == GEARMAN_NO_JOBS ||
            $worker->returnCode() == GEARMAN_IO_WAIT
        ){
            if( $worker->returnCode() == GEARMAN_SUCCESS ){
                $this->remainTask--;

                if( !$this->remainTask ){
                    echo "Выполнено заданий: {$this->maxTasks}. Выход\n";
                    break;
                }
            }

            if ($worker->returnCode() == GEARMAN_NO_JOBS){
                echo "Выполнено заданий: ".($this->maxTasks-$this->remainTask)."\n";
                echo "Нет заданий\n";
                break;
            }

            @$worker->wait();
        }

        return $result;
    }

    public function actionIndex(){
        if( !isset($this->workerClass) ){
            $opt = $this->ansiFormat('опция -w', Console::FG_GREEN);
            $this->stdout("Не указан класс воркера $opt\n", Console::BOLD, Console::FG_RED);
            return ExitCode::USAGE;
        }

        if( !isset($this->jobName) ){
            $opt = $this->ansiFormat('опция -j', Console::FG_GREEN);
            $this->stdout("Не указано имя задания $opt\n", Console::BOLD, Console::FG_RED);
            return ExitCode::USAGE;
        }

        if( !class_exists($this->workerClass) ){
            $this->stdout("Класс '{$this->workerClass}' не найден\n", Console::BOLD, Console::FG_RED);
            return ExitCode::USAGE;
        }

        $this->job = new $this->workerClass;

        return ExitCode::OK;
    }
}