<?php

namespace console\jobs;

use common\models\Cargo;
use common\models\Transport;
use console\helpers\tomita\TomitaHelper;
use console\jobs\jobData\AutoCategoryData;
use GearmanJob;
use Yii;
use yii\base\InvalidConfigException;
use yii\di\NotInstantiableException;

class AutoCategory extends BaseQueueJob
{
    /**
     * @param AutoCategoryData $job
     * @return mixed|void
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    public function run($job = null)
    {
        if (isset($job->transport_id)) {
            $object = Transport::findOne($job->transport_id);
        } elseif (isset($job->cargo_id)) {
            $object = Cargo::findOne($job->cargo_id);
        }
        if ( !$object) {
            Yii::error('Не удалось найти объект '.print_r($job, 1), 'AutoCategory');
            return;
        }

        /** @var TomitaHelper $helper */
        $helper = Yii::$container->get(TomitaHelper::class);

        $parser = $helper->parseCategories($object->description);
        $catIds = $parser->getCategoriesId();

        if ( !$catIds) {
            Yii::error("Не удалось сделать разбор текста. Текст:\n".$object->description, 'AutoCategory');
            return;
        }

        if ($object instanceof Transport) {
            $object->autoCategories = $catIds;

            $highlights = $this->getHighlights($parser->process());
            $object->highlights = json_encode($highlights);
            $object->save();
        }

        if ($object instanceof Cargo) {
            $saveCategories = $job->saveCategories??false;
            $object->setAutoCategories($catIds, $saveCategories);
        }
    }

    /**
     * @param $parsed
     * @param $description
     * @return array
     */
    private function getHighlights($parsed)
    {
        $result = [];
        foreach ($parsed['categories'] as $category) {
            $word = $category['words'][0];

            $tesxtStart = $word['TextPos'];
            $textLen = $word['TextLen'];

            $result[$category['id']] = [
                'start' => $tesxtStart,
                'len' => $textLen
            ];
        }

        return $result;
    }
}
