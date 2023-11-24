<?php

namespace console\jobs;

use common\helpers\CategoryHelper;
use common\models\CargoCategory;
use common\models\Transport;
use frontend\modules\subscribe\models\Subscribe;
use frontend\modules\subscribe\models\SubscribeRules;
use GearmanJob;
use micmorozov\yii2\gearman\JobBase;
use Yii;

class CreateSubscribeRule extends JobBase
{
    /**
     * @param GearmanJob|null $job
     * @return mixed
     */
    public function execute(GearmanJob $job = null)
    {
        $workload = $this->getWorkload($job);
        if ( !$workload) {
            return;
        }

        $transport_id = $workload['transport_id'];

        $transport = Transport::findOne($transport_id);

        if ( !$transport) {
            Yii::error('Транспорт не найден '.print_r($workload, 1), 'CreateSubscribeRule');
            return false;
        }

        /** @var Subscribe $subscribe */
        $subscribe = Subscribe::findOne(['userid' => $transport->created_by]);

        if ( !$subscribe) {
            $subscribe = Subscribe::createFree([
                'userid' => $transport->created_by,
                'phone' => $transport->createdBy->phone,
                'email' => $transport->createdBy->email
            ]);
        }

        $rule = new SubscribeRules();
        $rule->locationFrom = $rule->region_from = $transport->region_from;
        $rule->locationTo = $rule->region_to = $transport->region_to;
        $rule->locationFromType = SubscribeRules::LOCATION_TYPE_REGION;
        $rule->locationToType = SubscribeRules::LOCATION_TYPE_REGION;

        $cargoCategories = CategoryHelper::transportToCargo($transport->cargoCategoryIds);

        $rule->categoriesId = array_map(function ($cat){
            /** @var $cat CargoCategory */
            return $cat->id;
        }, $cargoCategories);

        $rule->subscribe_id = $subscribe->id;

        if ( !$rule->save()) {
            Yii::error('Не удалось оформить бесплатную подписку для пользователя ID: '.$subscribe->userid."\n"
                .print_r($rule->getErrors(), 1), 'CreateSubscribeRule');
        }
    }
}