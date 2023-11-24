<?php


namespace console\controllers;

use common\helpers\SqlHelper;
use common\helpers\TemplateHelper;
use common\models\FastCity;
use common\models\FastCityTags;
use Svezem\Services\MatrixContentService\MatrixContentService;
use Yii;

class FastCityController extends BaseController
{
    //блокировка на 30 мин
    protected $actionTTL = 60*30;

    /** @var MatrixContentService  */
    private $matrixContentService;

    public function __construct($id, $module, MatrixContentService $matrixContentService, $config = [])
    {
        $this->matrixContentService = $matrixContentService;

        parent::__construct($id, $module, $config);
    }
    
    /**
     * Генерирует теги на главные доменов
     */
    public function actionTagsGenerate()
    {

        // Генерим теги, зависящие от городов
        $batchInsert = [];
        $tag = new FastCityTags();
        $batchAttributes = $tag->attributes();

        //Грузоперевозки по России
        // БЕЗ подстановок
        $tpl = TemplateHelper::findTemplate('main');
        if( $tpl /*&& $this->matrixContentService->isEnoughContent('main', 0, 0)*/ ){
            $tpl = TemplateHelper::fillTemplate($tpl, [], ['tag_name']);

            array_push($batchInsert, [
                'id' => null,
                'name' => $tpl->tag_name,
                'cityid' => null,
                'url' => "https://".Yii::getAlias('@domain')
            ]);
        }

        // Грузоперевозки по городам
        /** @var FastCity $fastCity */
        foreach(FastCity::find()->each() as $fastCity){
            $city = $fastCity->city;

            $tpl = TemplateHelper::findTemplate('main', $city);
            if ($tpl && $this->matrixContentService->isEnoughContent('main', $city, $city)) {
                $tpl = TemplateHelper::fillTemplate($tpl, [
                    'city' => $city->getTitle()
                ], ['tag_name']);

                array_push($batchInsert, [
                    'id' => null,
                    'name' => $tpl->tag_name,
                    'cityid' => $city->getId(),
                    'url' => "https://" . Yii::getAlias('@domain') . "/{$city->getCode()}/"
                ]);
            }
        }

        if(count($batchInsert)>=1000) {
            $sql = SqlHelper::buildBatchInsertQuery(FastCityTags::tableName(), $batchAttributes, $batchInsert, true);
            Yii::$app->db
                ->createCommand($sql)
                ->execute();

            $batchInsert = [];
        }

        if(count($batchInsert)) {
            $sql = SqlHelper::buildBatchInsertQuery(FastCityTags::tableName(), $batchAttributes, $batchInsert, true);
            Yii::$app->db
                ->createCommand($sql)
                ->execute();
        }
    }

}