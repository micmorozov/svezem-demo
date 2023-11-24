<?php

namespace console\jobs;

use common\models\Cargo;
use common\models\CategoryFilter;
use common\models\City;
use common\models\FastCity;
use common\models\FetchPhoneLog;
use common\models\Profile;
use common\models\Region;
use common\models\Transport;
use frontend\modules\cargo\models\CargoPassing;
use frontend\modules\cargo\models\CargoSearch;
use frontend\modules\tk\models\Tk;
use frontend\modules\tk\models\TkCompareSearch;
use frontend\modules\tk\models\TkSearch;
use frontend\modules\transport\models\TransportSearch;
use GearmanJob;
use micmorozov\yii2\gearman\JobBase;
use Monolog\Logger;
use Yii;
use yii\base\InvalidConfigException;
use yii\di\NotInstantiableException;

class ElkLog extends JobBase
{
    private $data;

    /**
     * @param GearmanJob|null $job
     * @return mixed|void
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    public function execute(GearmanJob $job = null)
    {
        $workload = $this->getWorkload($job);
        if ( !$workload) {
            return;
        }

        $model = $workload['model']??null;
        $this->data = $workload['data']??[];

        if (isset($workload['cargo_id'])) {
            $model = Cargo::findOne($workload['cargo_id']);
        }
        if (isset($workload['transport_id'])) {
            $model = Transport::findOne($workload['transport_id']);
        }
        if (isset($workload['tk_id'])) {
            $model = Tk::findOne($workload['tk_id']);
        }
        if (isset($workload['profile_id'])) {
            $model = Profile::findOne($workload['profile_id']);
        }

        if ( !$model) {
            if ( !isset($workload['channel'])) {
                Yii::error("Не указан параметр channel\n".print_r($workload, 1), 'ElkLog');
            }

            $this->sendElk($workload['channel']);
        } elseif ($model instanceof CargoSearch) {
            $this->cargoSearchLog($model);
        } elseif ($model instanceof CargoPassing) {
            $this->cargoPassingLog($model);
        } elseif ($model instanceof TransportSearch) {
            $this->transportSearchLog($model);
        } elseif ($model instanceof TkSearch) {
            $this->tkSearchLog($model);
        } elseif ($model instanceof TkCompareSearch) {
            $this->tkCompareSearchLog($model);
        } elseif ($model instanceof Cargo) {
            $this->updateCargo($model);
        } elseif ($model instanceof Transport) {
            $this->updateTransport($model);
        } elseif ($model instanceof Tk) {
            $this->updateTk($model);
        } elseif ($model instanceof FetchPhoneLog) {
            $this->pushFetchPhoneLog($model);
        } elseif ($model instanceof Profile) {
            $this->updateProfile($model);
        }
    }

    /**
     * @param $channel
     * @param array $data
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    private function sendElk($channel, $data = [])
    {
        $log = Yii::$container->get(Logger::class);

        $data = array_merge($data, $this->data);

        $log->withName($channel)
            ->info(null, $data);
    }

    /**
     * @param City|Region|null $location
     * @return string
     */
    private function getLocationName($location = null)
    {
        $locationName = 'Любой город';
        if ($location) {
            $locationName = $location->getFullTitle();
        }

        return $locationName;
    }

    /**
     * @param $filterIds
     * @return array|string
     */
    private function getFilterName($filterIds)
    {
        $filterName = 'Без фильтра';
        if ($filterIds) {
            $filters = CategoryFilter::findAll(['id' => $filterIds]);
            $filterName = array_map(function ($model){
                /* @var CategoryFilter $model */
                return $model->title;
            }, $filters);

            $filterName = implode(', ', $filterName);
        }

        return $filterName;
    }

    /**
     * @param CargoSearch $model
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    private function cargoSearchLog(CargoSearch $model)
    {
        // TODO Надо переделать
        /*$this->sendElk('cargo-search', [
            'from' => $model->locationFrom,
            'fromType' => $model->locationFromType,
            'fromName' => $this->getLocationName($model->getCityFrom()),

            'to' => $model->locationTo,
            'toType' => $model->locationToType,
            'toName' => $this->getLocationName($model->getCityTo()),

            'filter' => $model->categoryFilter,
            'filterName' => $this->getFilterName($model->categoryFilter),

            'page' => $model->page,
        ]);*/
    }

    /**
     * @param CargoPassing $model
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    private function cargoPassingLog(CargoPassing $model)
    {
        $this->sendElk('cargo-passing', [
            'from' => $model->city_from,
            'fromName' => $this->getLocationName($model->getCityFrom()),

            'to' => $model->city_to,
            'toName' => $this->getLocationName($model->getCityTo()),

            'filter' => $model->categoryFilter,
            'filterName' => $this->getFilterName($model->categoryFilter),

            'page' => $model->page,
        ]);
    }

    /**
     * @param TransportSearch $model
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    private function transportSearchLog(TransportSearch $model)
    {
        // TODO надо переделать
        /*$this->sendElk('transport-search', [
            'from' => $model->locationFrom,
            'fromType' => $model->locationFromType,
            'fromName' => $this->getLocationName($model->getCityFrom()),

            'to' => $model->locationTo,
            'toType' => $model->locationToType,
            'toName' => $this->getLocationName($model->getCityTo()),

            'filter' => $model->categoryFilter,
            'filterName' => $this->getFilterName($model->categoryFilter),

            'page' => $model->page,
        ]);*/
    }

    /**
     * @param TkSearch $model
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    private function tkSearchLog(TkSearch $model)
    {
        // TODO Надо переделать
        /*$this->sendElk('tk-search', [
            'from' => $model->locationFrom,
            'fromType' => $model->locationFromType,
            'fromName' => $this->getLocationName($model->getCityFrom()),

            'filter' => $model->categoryFilter,
            'filterName' => $this->getFilterName($model->categoryFilter),

            'page' => $model->page
        ]);*/
    }

    /**
     * @param TkCompareSearch $model
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    private function tkCompareSearchLog(TkCompareSearch $model)
    {
        $this->sendElk('tk-comparison-search', [
            'from' => $model->city_from,
            'fromName' => $this->getLocationName(City::findOne($model->city_from)),

            'to' => $model->city_to,
            'toName' => $this->getLocationName(City::findOne($model->city_to)),

            'weight' => (float)$model->weight,
            'width' => (float)$model->width,
            'height' => (float)$model->height,
            'depth' => (float)$model->depth
        ]);
    }

    /**
     * @param Cargo $cargo
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    private function updateCargo(Cargo $cargo)
    {
        $this->sendElk('cargo', array_merge([
            'document_id' => $cargo->id,
            'timestamp' => date('c', $cargo->created_at),
            'categoriesId' => $cargo->categoriesId,
            'cityFromName' => $cargo->cityFrom->title_ru,
            'cityToName' => $cargo->cityTo->title_ru,
            'regionFromName' => $cargo->region_from ? $cargo->regionFrom->title_ru : null,
            'regionToName' => $cargo->region_to ? $cargo->regionTo->title_ru : null,
            'categoryName' => $cargo->cargoCategory ? $cargo->cargoCategory->category : '[не определена]'
        ], $cargo->attributes));
    }

    /**
     * @param Transport $transport
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    private function updateTransport(Transport $transport)
    {
        $this->sendElk('transport', array_merge([
            'document_id' => $transport->id,
            'timestamp' => date('c', $transport->created_at),
            'categoriesId' => $transport->cargoCategoryIds,
            'cityFromName' => $transport->cityFrom->title_ru,
            'cityToName' => $transport->cityTo->title_ru,
            'regionFromName' => $transport->region_from ? $transport->regionFrom->title_ru : null,
            'regionToName' => $transport->region_to ? $transport->regionTo->title_ru : null
        ], $transport->attributes));
    }

    /**
     * @param Tk $tk
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    private function updateTk(Tk $tk)
    {
        $this->sendElk('tk', array_merge([
            'document_id' => $tk->id
        ], $tk->attributes));
    }

    /**
     * @param FetchPhoneLog $model
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    private function pushFetchPhoneLog(FetchPhoneLog $model)
    {
        $this->sendElk('fetch-phone', array_merge(
            [
                'timestamp' => date('c', $model->created_at)
            ], $model->attributes
        ));
    }

    /**
     * @param Profile $model
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    private function updateProfile(Profile $model){
        $this->sendElk('profile', array_merge(
            [
                'document_id' => $model->id,
                'timestamp' => date('c', $model->created_at)
            ], $model->attributes
        ));
    }
}
