<?php

namespace frontend\modules\tk\models;

use common\models\CargoCategory;
use common\models\City;
use common\models\LocationCategorySearch;
use common\models\Region;
use Yii;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\data\DataProviderInterface;
use yii\sphinx\MatchExpression;
use yii\web\NotFoundHttpException;

class TkSearch extends LocationCategorySearch
{

    public function init()
    {
        parent::init();

        //Есть ограничение выдачи Sphinx. Если оно превышено, то выдаем 404 ошибку
        if($this->page > Yii::$app->params['sphinx_max_matches']/$this->pageSize){
            throw new NotFoundHttpException('Страница не найдена');
        }
    }

    /**
     * @return void|ArrayDataProvider
     * @throws NotFoundHttpException
     */
    protected function prepare(): DataProviderInterface
    {
        $this->fillCategoryByFilter();

        $sphinxQuery = SphinxTk::find();
        //если категория не задана, выбираем категорию "Главная"
        //для главной страницы выбираем категорию поиска транспорта и тк
        if ( !$this->cargoCategoryIds) {
            $mainCategory = CargoCategory::findOne(['slug' => 'glavnaya']);
            $this->cargoCategoryIds = [$mainCategory->id];
        }

        $categoryQuery = CargoCategory::createSphinxQuery($this->cargoCategoryIds);
        $sphinxMatch = '';
        if (trim($categoryQuery) != '') {
            $sphinxMatch .= "@describe $categoryQuery";
        }

        if ($this->locationFrom) {
            if ($this->locationFrom instanceof City) {
                $sphinxMatch .= " @city {$this->locationFrom->getId()}";

                if ($this->locationTo instanceof City) {
                    $sphinxMatch .= ' '.$this->locationTo->getId();
                }
            }

            if ($this->locationFrom instanceof Region) {
                $sphinxMatch .= " @region {$this->locationFrom->getId()}";

                if ($this->locationTo instanceof Region) {
                    $sphinxMatch .= ' '.$this->locationTo->getId();
                }
            }
        }

        $sphinxQuery->match(new MatchExpression($sphinxMatch))
            ->options(['max_matches' => Yii::$app->params['sphinx_max_matches']])
            ->snippetByModel()
            ->snippetOptions([
                "before_match" => '',
                'after_match' => '',
                'around' => 10
            ]);

        return new ActiveDataProvider([
            'query' => $sphinxQuery,
            'pagination' => [
                'pageParam' => 'page',
                'page' => $this->page - 1,
                'defaultPageSize' => $this->pageSize,
                'pageSizeLimit' => false,
                'forcePageParam' => false,
            ],
        ]);
    }
}
