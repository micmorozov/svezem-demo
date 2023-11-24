<?php
/**
 * При добавлении груза необходимо добавить новый город
 * для выбора пользователя
 */
namespace console\jobs;

use common\helpers\SlugHelper;
use common\models\Cargo;
use common\models\City;
use common\models\FastCity;
use common\models\Transport;
use console\helpers\PageCacheHelper;
use GearmanJob;
use micmorozov\yii2\gearman\JobBase;
use common\models\TransportLocation;
use frontend\modules\tk\models\TkDetails;
use yii\caching\TagDependency;
use Yii;

class AddFastCity extends JobBase
{
    /**
     * @param GearmanJob|null $job
     * @return mixed|void
     */
    public function execute(GearmanJob $job = null)
	{
		$workload = $this->getWorkload($job);
		if(!$workload) return;

        $cities = null;

		// Определяем город по грузу
		if(isset($workload['cargo_id'])) {
            $cargo = Cargo::findOne($workload['cargo_id']);
            if( $cargo ){
                $cities = [$cargo->cityFrom, $cargo->cityTo];
            }
        // Определяем город по перевозчику
        }elseif(isset($workload['transport_id'])){
            $transport = Transport::findOne($workload['transport_id']);
            if($transport) {
                $cities = [$transport->cityFrom, $transport->cityTo];
            }

        // Определяем город по ТК
        }elseif(isset($workload['tk_id'])){
            $locations = TkDetails::findAll([
                'tk_id' => $workload['tk_id']
            ]);

            foreach($locations as $location){
                $cities[] = $location->city;
            }
        }

        if(!$cities) return;

        /** @var City[] $cities */
        foreach($cities as $city) {
            //ищем город списке выбора городов
            $fast_city = FastCity::findOne(['cityid' => $city->id]);

            //город уже есть
            if ($fast_city) continue;

            $newCity = new FastCity();
            $newCity->title = $city->title_ru;
            $newCity->cityid = $city->id;
            $newCity->regionid = $city->region_id;
            $newCity->code = $city->code;

            if( $newCity->save() ){
                //тег используется в GeoBehavior
                TagDependency::invalidate(Yii::$app->cache, "FastCity:{$newCity->code}");

                //Создать статическую страницу выбора города
                PageCacheHelper::fetchUrl('https://'.Yii::getAlias('@domain').'/city');

                //Страница регионов
                if ($city->region_id){
                    $char = mb_substr($city->region->title_ru, 0, 1);
                    PageCacheHelper::fetchUrl('https://'.Yii::getAlias('@domain').'/city/'.$char);
                }
            }
        }
	}
}