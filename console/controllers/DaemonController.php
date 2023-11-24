<?php
/**
 * Created by PhpStorm.
 * User: Морозов Михаил
 * Date: 12.07.2016
 * Time: 12:21
 */

namespace console\controllers;

use yii\console\Controller;

abstract class DaemonController extends Controller
{

	/**
	 * Путь до pid файла, который может устанавливаться из командной строки через параметр  --pidFile=путь
	 * @var string
	 */
	public $pidFile;

	public function options($actionID)
	{
		return ['pidFile'];
	}

	public function init(){
		parent::init();

		$pid = pcntl_fork();
		if ($pid < 0){ // ошибка
			echo 'Fork is not created';
			exit;
		}elseif ($pid) // родитель
			exit;
		else{ // дочерний поток
			$sid = posix_setsid();

			if ($sid < 0){
				echo 'posix_setsid is not work';
				exit;
			}
		}

		// Если ПИД файл не указан, пишем его в директорию по умолчанию
		if(!$this->pidFile){
			$className = strtolower(str_replace('\\', '_', get_called_class()));
			$this->pidFile = "/var/run/php-fpm/{$className}.pid";
		}
	}

	/**
	 * Устанавливаем обработчик для сигналов завершения
	 */
	public function beforeAction($action){
		//записываем PID процесса
		file_put_contents($this->pidFile, getmypid());

		return true;
	}

	public function afterAction($action, $exitCode=0){
		//Удаляем PID процесса
		unlink($this->pidFile);
		return true;
	}
}