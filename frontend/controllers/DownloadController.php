<?php

namespace frontend\controllers;

use Monolog\Logger;
use Yii;
use yii\base\InvalidConfigException;
use yii\di\NotInstantiableException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class DownloadController extends Controller
{
    /**
     * @param string $file - Файл для скачивания
     * @return \yii\console\Response|Response
     * @throws NotFoundHttpException
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    public function actionIndex($file)
    {
        $path = Yii::getAlias('@common/downloads/') . $file;

        if (file_exists($path)) {
            $log = Yii::$container->get(Logger::class);

            $log->withName('download')
                ->info(null, [
                    'file' => $file,
                    'server' => $_SERVER,
                    'session' => $_SESSION,
                    'ip' => Yii::$app->request->remoteIP
                ]);

            return Yii::$app->response->SendFile($path);
        }

        throw new NotFoundHttpException();
    }
}