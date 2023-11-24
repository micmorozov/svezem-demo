<?php
/**
 * При добавлении груза необходимо добавить новый город
 * для выбора пользователя
 */

namespace console\jobs;

use common\helpers\Utils;
use console\helpers\CronLocker;
use Exception;
use GearmanJob;
use GuzzleHttp\Client;
use micmorozov\yii2\gearman\JobBase;
use Yii;
use yii\helpers\FileHelper;
use zz\Html\HTMLMinify;

class FetchUrl extends JobBase
{
    /** @var  CronLocker */
    protected $_locker;

    /**
     * @param GearmanJob|null $job
     * @return bool|mixed|void
     * @throws \yii\base\Exception
     */
    public function execute(GearmanJob $job = null)
    {
        $workload = $this->getWorkload($job);
        if ( !$workload || !isset($workload['url'])) {
            return;
        }

        $url = trim($workload['url']);

        //Для локального тестирования чтобы избежать ошибок SSL
        //$url = preg_replace('/^https/', 'http', $url);

        if ( !$url) {
            return;
        }

        $pathToSave = $workload['pathToSave']??null;

        if ( !$pathToSave) {
            // Делаем запрос на урл. Достаточно первых 100 байт
            @file_get_contents($url, false, null, 0, 100);
        } else {
            //Блокируем доступ к файлам другим потокам
            $locker = new CronLocker(Yii::$app->redisTemp, $pathToSave);

            if ( !$locker->acquire(10000)) {
                return false;
            }

            //Путь сохранения сжатого файла
            $pathGz = dirname($pathToSave).'/'.basename($pathToSave).'.gz';

            //Удаление статического файла
            if (file_exists($pathToSave)) {
                FileHelper::unlink($pathToSave);
            }
            if (file_exists($pathGz)) {
                FileHelper::unlink($pathGz);
            }

            try{
                $client = new Client([
                    'headers' => [
                        'User-Agent' => 'SvezemFetchUrlAgent',
                    ]
                ]);
                $response = $client->get($url);
            } catch (Exception $e){
                $locker->release();

                Yii::error($e->getMessage(), 'console.jobs.FetchUrl');
                Yii::getLogger()->flush();

                return false;
            }

            $content = $response->getBody()->__toString();

            $content = HTMLMinify::minify($content, [
                'optimizationLevel' => HTMLMinify::OPTIMIZATION_ADVANCED
            ]);

            FileHelper::createDirectory(dirname($pathToSave));
            file_put_contents($pathToSave, $content);
            Utils::gzipFile($pathToSave, $pathGz);

            $locker->release();
        }
    }
}