<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 16.11.17
 * Time: 14:31
 */

namespace console\jobs;

use GearmanJob;
use micmorozov\yii2\gearman\JobBase;
use common\models\Cargo;
use common\models\CargoCategory;

class ChangeCategorySlug extends JobBase
{
    public function execute(GearmanJob $job = null)
    {
        $workload = $this->getWorkload($job);
        if (!$workload) return;

        //получаем все грузы с категорией category_id

        /** @var Cargo[] $cargos */
        $cargos = Cargo::find()
            ->joinWith('categories')
            ->where([CargoCategory::tableName().'.id'=>$workload['category_id']])
            ->all();

        //вызываем генерацию тегов
        foreach($cargos as $cargo){
            $cargo->generateTags();
        }
    }
}