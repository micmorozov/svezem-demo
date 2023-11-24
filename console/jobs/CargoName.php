<?php

namespace console\jobs;

use common\models\Cargo;
use console\helpers\tomita\TomitaHelper;
use console\jobs\jobData\CargoNameData;
use Yii;
use yii\base\InvalidConfigException;
use yii\di\NotInstantiableException;

class CargoName extends BaseQueueJob
{

    /**
     * @param CargoNameData $job
     * @return bool|mixed
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    protected function run($job)
    {
        $cargo = Cargo::findOne($job->cargo_id);

        if ( !$cargo) {
            Yii::error('Груз не найден '.print_r($job->cargo_id, 1), 'CargoName');
            return false;
        }

        /** @var TomitaHelper $tomita */
        $tomita = Yii::$container->get(TomitaHelper::class);

        $parser = $tomita->parseCargoName($cargo->description);

        $words = $parser->getWords();

        if ( !$words) {
            Yii::error('Не удалось распарсить название груза '.print_r($job->cargo_id, 1), 'CargoName');
            return false;
        }

        $cargo->name = mb_strtolower($words[0]['im']);
        $cargo->name_rod = mb_strtolower($words[0]['rod']);
        $cargo->name_vin = mb_strtolower($words[0]['vin']);

        if( !$cargo->save() ){
            Yii::error('Не удалось сохранить имя груза '.print_r($job->cargo_id, 1), 'CargoName');
            return false;
        }

        return true;
    }
}
