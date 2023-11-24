<?php
/**
 * Получение позиции транспорта
 */

namespace console\jobs;

use common\models\CargoCategory;
use common\models\Transport;
use frontend\modules\transport\models\TransportSearch;
use GearmanJob;
use micmorozov\yii2\gearman\JobBase;
use Redis;
use Yii;

class TransportPosition extends JobBase
{
    const TTL = 3600*3;
    
    /** @var $redis Redis */
    public $redis;
    
    public function execute(GearmanJob $job = null){
        $workload = $this->getWorkload($job);
        if( !$workload) return;

        $transport_id = $workload['transport_id'];

        $transport = Transport::findOne($transport_id);
        $trCityFrom = $transport->cityFrom;
        $trCityTo = $transport->cityTo;

        if( !$transport)
            return false;

        $this->redis = Yii::$app->redisTemp;

        //Из Красноярска
        $searcher = (new TransportSearch())
            ->setPageSize(15)
            ->setSortPatternType(TransportSearch::SORT_PATTERN_SEARCH)
            ->setLocationFrom($trCityFrom);

        $page = $this->pageDefine($transport->id, $searcher, 20);

        $this->redis->setex($transport->positionKeyFrom(), self::TTL, $page);

        //Из Красноярска в Москву
        $searcher = (new TransportSearch())
            ->setPageSize(15)
            ->setSortPatternType(TransportSearch::SORT_PATTERN_SEARCH)
            ->setLocationFrom($trCityFrom)
            ->setLocationTo($trCityTo);

        $page = $this->pageDefine($transport->id, $searcher, 20);

        $this->redis->setex($transport->positionKeyFromTo(), self::TTL, $page);

        foreach($transport->cargoCategoryIds as $cat){
            $searcher = (new TransportSearch())
                ->setPageSize(15)
                ->setSortPatternType(TransportSearch::SORT_PATTERN_SEARCH)
                ->setLocationFrom($trCityFrom)
                ->setLocationTo($trCityTo)
                ->setCargoCategoryIds($cat);

            $page = $this->pageDefine($transport->id, $searcher, 20);

            $this->redis->zAdd($transport->positionKeyCat(), $page, $cat);
        }

        $this->redis->expire($transport->positionKeyCat(), self::TTL);

        //На главной странице
        //для главной страницы выбираем категорию поиска транспорта и тк
        $mainCategory = CargoCategory::findOne(['slug'=>'glavnaya']);
        $searcher = (new TransportSearch())
            ->setPageSize(8)
            ->setSortPatternType(TransportSearch::SORT_PATTERN_MAIN_WITHOUT_CATEGORY)
            ->setDirection(true)
            ->setLocationFrom($trCityFrom)
            ->setCargoCategories($mainCategory);

        $page = $this->pageDefine($transport->id, $searcher, 1);
        $this->redis->setex($transport->positionKeyMainCity(), self::TTL, $page);

        //Страница рекомендованых
        $searcher = (new TransportSearch())
            ->setPageSize(8)
            ->setSortPatternType(TransportSearch::SORT_PATTERN_RECOMMENDATION)
            ->setLocationFrom($trCityFrom)
            ->setLocationTo($trCityTo)
            ->setCargoCategoryIds($transport->cargoCategoryIds);

        $page = $this->pageDefine($transport->id, $searcher, 1);
        $this->redis->setex($transport->positionKeyRecommend(), self::TTL, $page);
    }

    /**
     * Определение страницы поисковой выдачи
     *
     * @param int $transport_id
     * @param TransportSearch $searcher
     * @param int $maxPage
     * @return int
     */
    protected function pageDefine($transport_id, $searcher, $maxPage){
        for($page = 1; $page <= $maxPage; $page++){
            $searcher->page = $page;
            $dataProvider = $searcher->search([]);

            $ids = array_map(function($transport){
                /** @var Transport $transport */
                return $transport->id;
            }, $dataProvider->models);

            if(in_array($transport_id, $ids)){
                return $page;
            }
        }

        return 100;
    }
}
