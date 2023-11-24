<?php
/**
 * По ИД изменившегося груза обходим страницы и строим кэш
 */

namespace console\jobs;

use common\models\Cargo;
use common\models\CargoCategory;
use common\models\FastCity;
use common\models\Transport;
use console\helpers\PageCacheHelper;
use console\jobs\jobData\BuildPageCacheData;
use frontend\modules\tk\models\Tk;
use GearmanJob;
use micmorozov\yii2\gearman\Dispatcher;
use Svezem\Services\MatrixContentService\MatrixContentService;
use Yii;
use yii\helpers\Url;

class BuildPageCache extends BaseQueueJob
{
    /** @var MatrixContentService  */
    private $matrixContentService;

    public function __construct($id, $module, MatrixContentService $matrixContentService, $config = [])
    {
        $this->matrixContentService = $matrixContentService;

        parent::__construct($id, $module, $config);
    }
    
    /**
     * @param BuildPageCacheData $job
     * @return mixed
     */
    protected function run($job)
    {
        // TODO не создаем кэш страниц
        return true;

        $city_from_id = $city_to_id = null;
        $categories = [];

        // Определяем город по грузу
        if (isset($job->cargo_id)) {
            $cargo = Cargo::findOne($job->cargo_id);
            if ($cargo) {
                $city_from_id = $cargo->city_from;
                $city_to_id = $cargo->city_to;
                $categories = $cargo->categories;

                $this->cargoStaticPage($cargo);

                // Страницу предыдущего груза тоже надо обновить, что бы там обновились ссылки на предыдущую и следующую страницы
                $nextCargo = $cargo->getNext($cargo->cityFrom);
                if($nextCargo)
                    $this->cargoStaticPage($nextCargo);
            }
            // Определяем город по перевозчику
        } elseif (isset($job->transport_id)) {
            $transport = Transport::findOne($job->transport_id);
            if ($transport) {
                $city_from_id = $transport->city_from;
                $city_to_id = $transport->city_to;
                $categories = $transport->categories;

                $this->transporterStaticPage($transport);

                // Страницу предыдущего перевозчика тоже надо обновить, что бы там обновились ссылки на предыдущую и следующую страницы
                $nextProfile = $transport->profile->getNext($transport->profile->city_id);
                if($nextProfile)
                    $this->fetchUrl($nextProfile->url);
            }
        } elseif (isset($job->tk_id)) {
            $tk = Tk::findOne($job->tk_id);
            $this->tkStaticPage($tk);

            // Страницу предыдущей ТК тоже надо обновить, что бы там обновились ссылки на предыдущую и следующую страницы
            $nextTk = $tk->getNext();
            if($nextTk)
                $this->tkStaticPage($nextTk);
        }

        if ( !$city_from_id || !$city_to_id) {
            return;
        }

        ////////////////////////////////////////////////////
        //============== Главный домен ===================//
        $mainDomainUrl = 'https://'.Yii::getAlias('@domain');
        $this->fetchUrl($mainDomainUrl, Dispatcher::HIGH);

        // cargo/transportation/<категория>
        $this->cargoTransportation($mainDomainUrl, $categories);
        /////////////////////////////////////////////////////

        /////////////////////////////////////////////////////
        //============== Город отправки ===================//
        $fastCityFrom = FastCity::findOne(['cityid' => $city_from_id]);
        $fastCityTo = FastCity::findOne(['cityid' => $city_to_id]);
        if ($fastCityFrom) {
            // Поддомен
            $subDomainUrl = 'https://'.Yii::getAlias('@domain').'/'.$fastCityFrom->code.'/';
            $this->fetchUrl($subDomainUrl, Dispatcher::HIGH);

            // cargo/transportation/<категория>
            $this->cargoTransportation($subDomainUrl, $categories, $fastCityFrom);

            // intercity
            $this->fetchUrl($subDomainUrl.Url::toRoute(["/intercity"]));
            if ($fastCityTo &&
                $this->matrixContentService->isEnoughContent('intercity-view', $fastCityFrom, $fastCityTo)) {

                $this->intercity($subDomainUrl, $fastCityFrom, $fastCityTo, $categories);
            }
        }
        /////////////////////////////////////////////////////

        /////////////////////////////////////////////////////
        //============== Город доставки ===================//
        if ($fastCityTo && $city_from_id != $city_to_id) {
            $subDomainUrl = 'https://'.Yii::getAlias('@domain').'/'.$fastCityTo->code.'/';
            $this->fetchUrl($subDomainUrl, Dispatcher::HIGH);

            // cargo/transportation/<категория>
            $this->cargoTransportation($subDomainUrl, $categories, $fastCityTo);
        }
        /////////////////////////////////////////////////////
    }

    /**
     * @param $baseUrl
     * @param CargoCategory[] $categories
     * @param FastCity|null $fastCity
     */
    protected function cargoTransportation($baseUrl, $categories, FastCity $fastCity = null)
    {
        // cargo/transportation
        $this->fetchUrl($baseUrl.Url::toRoute(["/cargo/transportation"]));

        foreach ($categories as $cat) {
            if ($cat->create_tag) {
                // =====/cargo/transportation/perevozka-zerna/=====
                //определяем кол-во контента по указанной ссылке
                if ($fastCity) {
                    $isEnough = $this->matrixContentService->isEnoughContentAnyDirection('cargo-transportation-view',
                        $fastCity->city, $cat);
                } else {
                    $isEnough = $this->matrixContentService->isEnoughContent('cargo-transportation-view', 0, 0, $cat);
                }

                if ($isEnough) {
                    $this->fetchUrl($baseUrl.Url::toRoute(["/cargo/transportation/{$cat->slug}"]), Dispatcher::HIGH);
                }
            }

            foreach ($cat->parents as $parent) {
                if ($parent->create_tag) {
                    if ($fastCity) {
                        $isEnough = $this->matrixContentService->isEnoughContentAnyDirection('cargo-transportation-view', $fastCity, $parent);
                    } else {
                        $isEnough = $this->matrixContentService->isEnoughContent('cargo-transportation-view', 0, 0, $parent);
                    }

                    if ($isEnough) {
                        $this->fetchUrl($baseUrl.Url::toRoute(["/cargo/transportation/{$parent->slug}"]),
                            Dispatcher::HIGH);
                    }
                }
            }
        }
    }

    /**
     * @param $baseUrl
     * @param FastCity $fastCityFrom
     * @param FastCity $fastCityTo
     * @param $categories
     */
    private function intercity($baseUrl, $fastCityFrom, $fastCityTo, $categories)
    {
        // Исключаем пересечение с самим собой
        if($fastCityFrom->cityid == $fastCityTo->cityid)
            return;

        $this->fetchUrl($baseUrl.Url::toRoute(["/intercity/{$fastCityTo->code}"]));
        $this->fetchUrl($baseUrl.Url::toRoute(["/intercity/{$fastCityTo->code}/all"]));

        foreach ($categories as $category) {
            if ($category->create_tag && $this->matrixContentService->isEnoughContent('intercity-category-view', $fastCityFrom, $fastCityTo, $category)) {

                $this->fetchUrl($baseUrl.Url::toRoute([
                        '/intercity/default/search',
                        'cityTo' => $fastCityTo->code,
                        'slug' => $category->slug
                    ]));
            }

            foreach ($category->parents as $parent) {

                if ($parent->create_tag && $this->matrixContentService->isEnoughContent('intercity-category-view', $fastCityFrom, $fastCityTo, $parent)) {

                    $this->fetchUrl($baseUrl.Url::toRoute([
                            '/intercity/default/search',
                            'cityTo' => $fastCityTo->code,
                            'slug' => $parent->slug
                        ]));
                }
            }
        }
    }

    /**
     * Добавляем урл в очередь на загрузку
     * @param $url
     * @param int $priority
     */
    private function fetchUrl($url, $priority = Dispatcher::NORMAL)
    {
        return PageCacheHelper::fetchUrl($url, $priority);
    }

    /**
     * Создание/удаление статики
     *
     * @param Cargo $cargo
     */
    private function cargoStaticPage(Cargo $cargo)
    {
        $url = $cargo->url;

        $this->fetchUrl($url);
    }

    /**
     * @param Transport $transport
     */
    private function transporterStaticPage(Transport $transport)
    {
        $url = $transport->url;

        $this->fetchUrl($url);
    }

    /**
     * @param Tk $tk
     */
    private function tkStaticPage(Tk $tk)
    {
        $url = $tk->internalUrl;

        if ($tk->status == Tk::STATUS_ACTIVE) {
            $this->fetchUrl($url);
        }
    }
}
