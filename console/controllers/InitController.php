<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 11.09.18
 * Time: 11:14
 */

namespace console\controllers;

use common\helpers\SqlHelper;
use common\helpers\Utils;
use common\models\Cargo;
use common\models\CargoCategory;
use common\models\CargoCategoryTags;
use common\models\CargoSearchTags;
use common\models\City;
use common\models\FastCity;
use common\models\FastCityTags;
use common\models\FetchPhoneLog;
use common\models\IntercityTags;
use common\models\Profile;
use common\models\SearchPageTags;
use common\models\Transport;
use common\models\TransporterTags;
use common\models\Transporter;
use common\models\CargoTags;
use common\models\TransportSearchTags;
use console\helpers\PageCacheHelper;
use dosamigos\fileupload\actions\FileDeleteAction;
use frontend\components\CityComponent;
use frontend\modules\account\models\EmailForm;
use frontend\modules\cargo\models\CargoSearch;
use frontend\modules\subscribe\models\SubscribeLog;
use frontend\modules\tk\models\Tk;
use frontend\modules\transport\models\TransportSearch;
use micmorozov\yii2\gearman\Dispatcher;
use Monolog\Logger;
use Redis;
use Svezem\Services\MatrixContentService\MatrixContentService;
use Yii;
use yii\base\InvalidConfigException;
use yii\console\Controller;
use yii\di\NotInstantiableException;
use yii\helpers\Url;

class InitController extends Controller
{
    /** @var $categories CargoCategory[] */
    private $categories = null;

    /** @var $fastCities FastCity[] */
    private $fastCities = null;

    /** @var MatrixContentService  */
    private $matrixContentService;

    public function __construct($id, $module, MatrixContentService $matrixContentService, $config = [])
    {
        $this->matrixContentService = $matrixContentService;

        parent::__construct($id, $module, $config);
    }

    public function Init()
    {
        $this->categories = CargoCategory::find()
            ->where(['create_tag' => 1])
            ->all();

        $this->fastCities = FastCity::find()->all();
    }

    /**
     * Восстанавливаем кэш страниц, пробегаясь по ним и делая http запросы
     * @param $domain - домен для которого требуется генерация
     */
    public function actionBuildFullCache($domain = null)
    {
        $city=null;
        if($domain) {
            $city = FastCity::findOne(['code'=>$domain]);
            $this->fastCities = [$city];
        }else{
            //Главный домен
            $baseUrl = 'https://' . Yii::getAlias('@domain');
            $this->fetchUrl($baseUrl);

            $this->buildCargoTransportationUrl($baseUrl);

            $this->fetchUrl($baseUrl . Url::toRoute(['/transport/search/all/']));
            $this->fetchUrl($baseUrl . Url::toRoute(['/cargo/search/all/']));
        }

        $total = count($this->fastCities);
        $done = 0;
        foreach ($this->fastCities as $fastCity) {
            echo BaseController::progress_bar($done, $total, ' Обработано fastCity');

            $baseUrl = 'https://'.$fastCity->code.'.'.Yii::getAlias('@domain');
            $this->fetchUrl($baseUrl);

            $this->buildCargoTransportationUrl($baseUrl, $fastCity);
            $this->buildIntercityUrl($baseUrl, $fastCity);

            $this->fetchUrl($baseUrl.Url::toRoute(['/transport/search/all/']));
            $this->fetchUrl($baseUrl.Url::toRoute(['/cargo/search/all/']));

            $done++;
        }

        $this->actionCargoCache(null, $city?$city->cityid:null);
        $this->actionTransportCache(null, $city?$city->cityid:null);
        $this->actionTkCache();
        $this->actionCityCache();
    }

    /**
     * Создаем кэш /cargo/transportation/ страниц
     */
    public function actionBuildCargoTransportationCache()
    {
        //Главный домен
        $baseUrl = 'https://'.Yii::getAlias('@domain');
        $this->buildCargoTransportationUrl($baseUrl);

        $total = count($this->fastCities);
        $done = 0;
        foreach ($this->fastCities as $fastCity) {
            echo BaseController::progress_bar($done, $total, ' Обработано fastCity');

            $baseUrl = 'https://'.$fastCity->code.'.'.Yii::getAlias('@domain');
            $this->buildCargoTransportationUrl($baseUrl, $fastCity);

            $done++;
        }
    }

    /**
     * Создаем кэш /intercity/ страниц
     */
    public function actionBuildIntercityCache()
    {
        $total = count($this->fastCities);
        $done = 0;
        foreach ($this->fastCities as $fastCity) {
            echo BaseController::progress_bar($done, $total, ' Обработано fastCity');

            $baseUrl = 'https://'.$fastCity->code.'.'.Yii::getAlias('@domain');
            $this->buildIntercityUrl($baseUrl, $fastCity);

            $done++;
        }
    }

    /**
     * Строим
     * @param $baseUrl
     * @param $fastCity
     * @return array
     */
    private function buildIntercityUrl($baseUrl, $fastCity)
    {
        $this->fetchUrl($baseUrl.Url::toRoute(['/intercity/']));

        foreach ($this->fastCities as $toFastCity) {
            // Пересечения сам с собой не делаем
            if ($fastCity->id == $toFastCity->id) {
                continue;
            }

            if ( !$this->matrixContentService->isEnoughContent('intercity-view', $fastCity, $toFastCity)) {
                continue;
            }

            $this->fetchUrl($baseUrl.Url::toRoute(["/intercity/{$toFastCity->code}"]));
            $this->fetchUrl($baseUrl.Url::toRoute(["/intercity/{$toFastCity->code}/all/"]));

            foreach ($this->categories as $cat) {
                if ( !$cat->create_tag) {
                    continue;
                }

                if ( !$this->matrixContentService->isEnoughContent('intercity-category-view', $fastCity, $toFastCity, $cat)) {
                    continue;
                }

                $this->fetchUrl($baseUrl.Url::toRoute([
                        '/intercity/default/search',
                        'cityTo' => $toFastCity->code,
                        'slug' => $cat->slug
                    ]));
            }
        }
    }

    /**
     * @param $baseUrl
     * @param FastCity|null $fastCity
     */
    private function buildCargoTransportationUrl($baseUrl, FastCity $fastCity = null)
    {
        $this->fetchUrl($baseUrl.Url::toRoute(['/cargo/transportation/']));

        foreach ($this->categories as $cat) {
            if ( !$cat->create_tag) {
                continue;
            }
            // =====/cargo/transportation/perevozka-zerna/=====

            //определяем кол-во контента по указанной ссылке
            if ($fastCity) {
                $isEnough = $this->matrixContentService->isEnoughContentAnyDirection('cargo-transportation-view', $fastCity, $cat);
            } else {
                $isEnough = $this->matrixContentService->isEnoughContent('cargo-transportation-view', null, null, $cat);
            }

            if ($isEnough) {
                $this->fetchUrl($baseUrl.Url::toRoute(["/cargo/transportation/{$cat->slug}"]));
            }
        }
    }

    /**
     * Добавляем урл в очередь на загрузку
     * @param string $url - URL адрес страницы
     * @param int $priority - Приоритет обработки
     */
    private function fetchUrl($url, $priority = Dispatcher::LOW)
    {
        return PageCacheHelper::fetchUrl($url, $priority);

        /*$url = trim($url);
        if ( !$url) {
            return;
        }

        $pathToSave = BuildPageCache::getStaticFilePath($url);

        Yii::$app->gearman->getDispatcher()->background("fetchUrl", [
            'url' => $url,
            'pathToSave' => $pathToSave
        ], $priority);*/
    }

    public function actionCargoCache($cargoid = null, $cityId=null)
    {
        $query = Cargo::find()
            ->orderBy(['id' => SORT_DESC])
            ->limit(500);


        if ($cargoid) {
            $query->where(['id' => $cargoid]);
        }

        if($cityId){
            $query->where(['city_from' => $cityId]);
        }

        $total = $query->count();
        $done = 0;

        /** @var Cargo[] $cargos */
        while ($cargos = $query->all()) {
            $query->offset += $query->limit;

            foreach ($cargos as $cargo) {
                $this->fetchUrl($cargo->url);
            }

            $done += count($cargos);
            echo BaseController::progress_bar($done, $total, ' Обработка грузов');
        }
    }

    public function actionTransportCache($tr_id = null, $cityId=null)
    {
        $query = Transport::find()
            ->limit(500);

        if ($tr_id) {
            $query->where(['id' => $tr_id]);
        }

        if($cityId){
            $query->where(['city_from' => $cityId]);
        }

        $total = $query->count();
        $done = 0;

        /** @var Transport[] $transports */
        while ($transports = $query->all()) {
            $query->offset += $query->limit;

            foreach ($transports as $transport) {
                $this->fetchUrl($transport->url);
            }

            $done += count($transports);
            echo BaseController::progress_bar($done, $total, ' Обработка транспорта');
        }
    }

    public function actionTkCache($tk_id = null)
    {
        $query = Tk::find()
            ->limit(500);

        if ($tk_id) {
            $query->where(['id' => $tk_id]);
        }

        $total = $query->count();
        $done = 0;

        /** @var Tk[] $tks */
        while ($tks = $query->all()) {
            $query->offset += $query->limit;

            foreach ($tks as $tk) {
                $this->fetchUrl($tk->internalUrl);
            }

            $done += count($tks);
            echo BaseController::progress_bar($done, $total, ' Обработка ТК');
        }
    }

    /**
     * Создать статическую страницу выбора города
     */
    public function actionCityCache()
    {
        $this->fetchUrl('https://'.Yii::getAlias('@domain').'/city');

        $alphabet = CityComponent::getAlphabet();

        foreach ($alphabet as $char){
            $this->fetchUrl('https://'.Yii::getAlias('@domain').'/city/'.$char);
        }
    }

    public function actionFastBuildCache()
    {
        /** @var Cargo[] $cargos */
        $cargos = Cargo::find()
            ->where(['id' => [11998, 11911]])
            ->all();

        foreach ($cargos as $cargo) {
            $cargo->buildPageCache();
        }

        /** @var Transport[] $transports */
        $transports = Transport::find()
            ->where(['id' => [137336, 103415]])
            ->all();

        foreach ($transports as $transport) {
            $transport->buildPageCache();
        }

        /** @var Tk[] $tks */
        $tks = Tk::find()
            ->where(['id' => [1, 2, 3]])
            ->all();

        foreach ($tks as $tk) {
            $tk->buildPageCache();
        }

        $this->actionCityCache();
    }

    /**
     * Вызывается после успешного деплоя приложения
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    public function actionAfterDeploy()
    {

    }

    public function actionCityCoordinates()
    {
        /** @var Redis $redis */
        $redis = Yii::$app->redisGeo;

        $query = City::find()
            ->where([
                'not',
                ['latitude' => null]
            ])
            ->limit(500);

        while ($cities = $query->all()) {
            $query->offset += $query->limit;

            foreach ($cities as $city) {
                $redis->geoadd(City::REDIS_CITY_COORDINATE_KEY, $city->longitude, $city->latitude, $city->id);
            }
        }
    }

    public function actionFetchPhoneLog()
    {
        $query = FetchPhoneLog::find()
            ->limit(500)
            ->orderBy(['id' => SORT_DESC]);

        while ($logs = $query->all()) {
            $query->offset += $query->limit;

            foreach ($logs as $log) {
                Yii::$app->gearman->getDispatcher()->background("ElkLog", [
                    'model' => $log
                ]);
            }
        }
    }

    public function actionSubscribeLog()
    {
        /** @var Logger $logger */
        $logger = Yii::$container->get(Logger::class);

        $query = SubscribeLog::find()
            ->orderBy(['moment' => SORT_DESC])
            ->limit(500);

        while ($logs = $query->all()) {
            $query->offset += $query->limit;

            foreach ($logs as $log) {
                $logger->withName('subscribe-log')
                    ->info(
                        $log->type,
                        array_merge(
                            [
                                'timestamp' => date('c', $log->moment)
                            ],
                            $log->attributes
                        )
                    );
            }
        }
    }

    public function actionElkCargo()
    {
        $query = Cargo::find()
            ->with('categories');

        /** @var Cargo $cargo */
        foreach (Utils::arModelsGenerator($query) as $cargo){
            Cargo::updateElk($cargo->id);
        }
    }

    public function actionElkTransport()
    {
        $query = Transport::find()
            ->with('categories')
            ->limit(500);

        while ($transports = $query->all()) {
            $query->offset += $query->limit;

            foreach ($transports as $transport) {
                Transport::updateElk($transport->id);
            }
        }
    }

    public function actionElkTk()
    {
        $query = Tk::find()
            ->limit(500);

        while ($tks = $query->all()) {
            $query->offset += $query->limit;

            foreach ($tks as $tk) {
                Tk::updateElk($tk->id);
            }
        }
    }

    public function actionElkProfile()
    {
        $query = Profile::find()
            ->limit(500);

        while ($profiles = $query->all()) {
            $query->offset += $query->limit;

            foreach ($profiles as $profile) {
                Profile::updateElk($profile->id);
            }
        }
    }

    public function actionCargoPhoneView(){
        $query =<<<QUERY
UPDATE cargo
INNER JOIN (
    SELECT object_id, count(*) count FROM fetch_phone_log
    WHERE object='cargo' AND status='show'
    GROUP BY object_id
) A ON A.object_id = cargo.id
SET phone_view=A.count

QUERY;

        Yii::$app->db->createCommand($query)->execute();
    }

    public function actionProfilePhoneView(){
        $query =<<<QUERY
UPDATE profile
INNER JOIN (
    SELECT object_id, count(*) count FROM fetch_phone_log
    WHERE object='transporter' AND status='show'
    GROUP BY object_id
) A ON A.object_id = profile.id
SET phone_view=A.count

QUERY;

        Yii::$app->db->createCommand($query)->execute();
    }

    public function actionTkPhoneView(){
        $query =<<<QUERY
UPDATE tk
INNER JOIN (
    SELECT object_id, count(*) count FROM fetch_phone_log
    WHERE object='tk' AND status='show'
    GROUP BY object_id
) A ON A.object_id = tk.id
SET phone_view=A.count

QUERY;

        Yii::$app->db->createCommand($query)->execute();
    }

    /**
     * Генерируем ссылки для перелинковки для страниц перевозчика и грузов
     *
     */
    public function actionPageTagsGenerate()
    {
        // ИД ключа, хранящего профили перевозчиков
        $srcPagesRedisKey = 'tags:source_id';

        // Ключ с тегами на разные страницы для перелинковки
        $tagRedisKey = 'tags:tag_list';

        ////////////////////////////////////////
        // Для удобства работы перегоняем всех перевозчиков в редис
        $lastProfileId = (int)Yii::$app->redisTemp->get('tags:lastProfileId');
        $query = Transport::find()
            ->select('profile_id')
            ->distinct()
            ->where(['>','profile_id',$lastProfileId])
            ->orderBy('profile_id', SORT_ASC)
            ->limit(1000);


        while ($profiles = $query->all()) {
            $query->offset += $query->limit;
            printf("\rГенерим перевозчиков");

            array_map(function ($item) use ($srcPagesRedisKey, &$lastProfileId){
                Yii::$app->redisTemp->zAdd($srcPagesRedisKey, ['NX'], 0, json_encode([
                    'id' => $item['profile_id'],
                    'cityid' => $item->profile->city_id,
                    'class' => Transport::class
                ]));

                $lastProfileId = $item['profile_id'];
            }, $profiles);
        }
        Yii::$app->redisTemp->set('tags:lastProfileId', $lastProfileId);

        // Генерим страницы грузов
        $lastCargoId = (int)Yii::$app->redisTemp->get('tags:lastCargoId');
        $query = Cargo::find()
            ->where(['not', ['status' => Cargo::STATUS_BANNED]])
            ->andWhere(['>', 'id', $lastCargoId])
            ->orderBy('id', SORT_ASC)
            ->limit(1000);
        while ($cargos = $query->all()) {
            $query->offset += $query->limit;
            printf("\rГенерим грузы");

            array_map(function ($item) use ($srcPagesRedisKey, &$lastCargoId){
                $data = json_encode([
                    'id' => $item['id'],
                    'cityid' => $item['city_from'],
                    'class' => Cargo::class
                ]);
                Yii::$app->redisTemp->zAdd($srcPagesRedisKey, ['NX'], 0, $data);

                $lastCargoId = $item['id'];
            }, $cargos);
        }
        Yii::$app->redisTemp->set('tags:lastCargoId', $lastCargoId);
        /////////////////////////////////////////

        ////////////////////////////////////////
        // Каждый вид тега храним в своем ключе

        // Виды перевозки между городами
        $query = IntercityTags::find()->where(['category_id' => null])->limit(1000);
        while ($items = $query->all()) {
            $query->offset += $query->limit;
            printf("\rГенерим теги IntercityTags");

            array_map(function ($item) use ($tagRedisKey){
                $data = json_encode([
                    'id' => $item['id'],
                    'cityid' => intval($item['city_from']),
                    'class' => IntercityTags::class,
                    'name' => $item['name'],
                    'url' => $item['url']
                ]);
                $score = lcg_value(); // Что бы теги шли в случайном порядке иначе на одной странице теги из одного города
                Yii::$app->redisTemp->zAdd($tagRedisKey.':'.IntercityTags::class, ['NX'], $score, $data);
                Yii::$app->redisTemp->zAdd($tagRedisKey.':'.IntercityTags::class.':'.intval($item['city_from']), $score, $data);
            }, $items);
        }

        // виды перевозки
        $query = CargoCategoryTags::find()->limit(1000);
        while ($items = $query->all()) {
            $query->offset += $query->limit;
            printf("\rГенерим теги CargoCategoryTags");

            array_map(function ($item) use ($tagRedisKey){
                $data = json_encode([
                    'id' => $item['id'],
                    'cityid' => intval($item['city_id']),
                    'class' => CargoCategoryTags::class,
                    'name' => $item['name'],
                    'url' => $item['url']
                ]);
                $score = lcg_value();
                Yii::$app->redisTemp->zAdd($tagRedisKey.':'.CargoCategoryTags::class, ['NX'], $score, $data);
                Yii::$app->redisTemp->zAdd($tagRedisKey.':'.CargoCategoryTags::class.':'.intval($item['city_id']), $score, $data);
            }, $items);
        }

        // Главные доменов
        $query = FastCityTags::find()->limit(1000);
        while ($items = $query->all()) {
            $query->offset += $query->limit;
            printf("\rГенерим теги FastCityTags");

            array_map(function ($item) use ($tagRedisKey){
                $data = json_encode([
                    'id' => $item['id'],
                    'cityid' => $item['cityid'],
                    'class' => FastCityTags::class,
                    'name' => $item['name'],
                    'url' => $item['url']
                ]);
                $score = lcg_value();
                Yii::$app->redisTemp->zAdd($tagRedisKey.':'.FastCityTags::class, ['NX'], $score, $data);
                Yii::$app->redisTemp->zAdd($tagRedisKey.':'.FastCityTags::class.':'.intval($item['cityid']), $score, $data);
            }, $items);
        }

        // Фильтр поиска груза
        $query = CargoSearchTags::find()->where(['not', ['city_from' => null]])->limit(1000);
        while ($items = $query->all()) {
            $query->offset += $query->limit;
            printf("\rГенерим теги CargoSearchTags");

            array_map(function ($item) use ($tagRedisKey){
                $data = json_encode([
                    'id' => $item['id'],
                    'cityid' => intval($item['city_from']),
                    'class' => CargoSearchTags::class,
                    'name' => $item['name'],
                    'url' => $item['url']
                ]);
                $score = lcg_value();
                Yii::$app->redisTemp->zAdd($tagRedisKey.':'.CargoSearchTags::class, ['NX'], $score, $data);
                Yii::$app->redisTemp->zAdd($tagRedisKey.':'.CargoSearchTags::class.':'.intval($item['city_from']), $score, $data);
            }, $items);
        }

        // Фильтр поиска перевозчика
        $query = TransportSearchTags::find()->limit(1000);
        while ($items = $query->all()) {
            $query->offset += $query->limit;
            printf("\rГенерим теги TransportSearchTags");

            array_map(function ($item) use ($tagRedisKey){
                $data = json_encode([
                    'id' => $item['id'],
                    'cityid' => intval($item['city_from']),
                    'class' => TransportSearchTags::class,
                    'name' => $item['name'],
                    'url' => $item['url']
                ]);
                $score = lcg_value();
                Yii::$app->redisTemp->zAdd($tagRedisKey.':'.TransportSearchTags::class, ['NX'], $score, $data);
                Yii::$app->redisTemp->zAdd($tagRedisKey.':'.TransportSearchTags::class.':'.intval($item['city_from']), $score, $data);
            }, $items);
        }
        ////////////////////////////////////////

        $this->buildTagsByClassName($srcPagesRedisKey, $tagRedisKey.':'.FastCityTags::class, 1, true);
        $this->buildTagsByClassName($srcPagesRedisKey, $tagRedisKey.':'.CargoCategoryTags::class, 4, true);
        $this->buildTagsByClassName($srcPagesRedisKey, $tagRedisKey.':'.IntercityTags::class, 3, true);
        $this->buildTagsByClassName($srcPagesRedisKey, $tagRedisKey.':'.CargoSearchTags::class, 1, true);
        $this->buildTagsByClassName($srcPagesRedisKey, $tagRedisKey.':'.TransportSearchTags::class, 1, true);

        $this->buildTagsByClassName($srcPagesRedisKey, $tagRedisKey.':'.CargoCategoryTags::class, 2, false);
        $this->buildTagsByClassName($srcPagesRedisKey, $tagRedisKey.':'.IntercityTags::class, 1, false);
        $this->buildTagsByClassName($srcPagesRedisKey, $tagRedisKey.':'.CargoSearchTags::class, 1, false);
    }

    /**
     * Для страниц из ключа $pageSrcKey генерим по $cntTags тегов на страницы из ключа $tagSrcKey
     *
     * @param $pageSrcKey - Ключ со страницами, для которых генерим теги
     * @param $tagSrcKey - Теги для размещения на страницах
     * @param $cntTags - количество тегов для генерации
     * @param $outer - Внешняя или внутрення ссылка
     */
    public function buildTagsByClassName($pageSrcKey, $tagSrcKey, $cntTags, $outer)
    {
        $countSrc = Yii::$app->redisTemp->zCard($pageSrcKey);
        $srcIndex = -1;
        while(true){
            $srcIndex++;
            printf("%3d%% %d\n", $srcIndex/$countSrc*100, $srcIndex);

            // Получаем страницу для которой мы генерим теги
            $pageArr = Yii::$app->redisTemp->zRange($pageSrcKey, $srcIndex, $srcIndex);
            if (!$pageArr) {
               // Yii::error("Ключа со страницами не существует", 'InitController.buildTagsByClassName');
                break;
            }

            // Страница, на которой надо размещать  теги
            $source = json_decode($pageArr[0], true);

            $tagIndex = -1;
            $insertedTag = false;
            $breakIteration = true;

            // В зависимости от того какой тег надо искать внутренний или внешний
            $mTagSrcKey = $tagSrcKey;
            if (!$outer){
                $mTagSrcKey = $tagSrcKey . ':' . $source['cityid'];
            }

            while(true){
                $tagIndex++;

                $tagLink = Yii::$app->redisTemp->zRange($mTagSrcKey, $tagIndex, $tagIndex);
                if (!$tagLink) {
                    $breakIteration = false;
                    //echo $mTagSrcKey;
                    //echo ' End tags ' .$tagIndex . ' srcIndex='.$srcIndex ."\n";
                    break;
                }

                $link = json_decode($tagLink[0], true);

                if (($outer && $link['cityid'] == $source['cityid']) ||
                    (!$outer && $link['cityid'] != $source['cityid'])) continue;

                // если достигли лимита по тегам указанного типа
                if(Yii::$app->redisTemp->hGet($pageSrcKey.":".$source['class'].':'.$source['id'], $link['class'].':'.intval($outer)) >= $cntTags) {
                    echo 'Лимит по тегам достигнут key='. $pageSrcKey.":".$source['class'].':'.$source['id'] . ' f='.$link['class'].':'.intval($outer) . "\n";

                    // Для внешних тегов прерываем внутренний цикл - у всех страниц тегов данного типа в достаточном количестве
                    // Для внутренних тегов надо перейти к следующей итерации, так как у других страниц могут быть не заполнены данные теги
                    // из за того, что на предудыщем шаге небыло ключа с тегами
                    if(!$outer){
                        $breakIteration = false;
                    }

                    break;

                }

               /* $linkClass = new $link['class'];
                $model = $linkClass->findOne($link['id']);
                if (!$model) {
                    Yii::error("Не удалось создать тег. No model",
                        'InitController.buildTagsByClassName');
                    continue;
                }*/

                $srcModel = new $source['class'];
                if ($srcModel instanceof Cargo) {
                    $tag = new CargoTags();
                    $tag->cargo_id = $source['id'];
                    $tag->name = $link['name'];
                    $tag->url = $link['url'];
                } elseif ($srcModel instanceof Transport) {
                    $tag = new TransporterTags();
                    $tag->profile_id = $source['id'];
                    $tag->name = $link['name'];
                    $tag->url = $link['url'];
                } else {
                    Yii::error("Модель страницы не определена " . print_r($link, 1), 'InitController.buildTagsByClassName');
                    break;
                }

                if ($tag->save()) {
                    $tagIndex--;
                    $insertedTag=true;
                    Yii::$app->redisTemp->multi()
                        // Увеличиваем счетчик использованмя тега только в том ключе который сейчас используется: по городу или общий список
                        ->zIncrBy($mTagSrcKey, 1, $tagLink[0])
                        //->zIncrBy($tagSrcKey, 1, $tagLink[0]) // Увеличиваем счетчик использований тега
                        //->zIncrBy($tagSrcKey.':'.$link['cityid'], 1, $tagLink[0]) // Так же увеличиваем счетчик использования тега в городе
                        ->zIncrBy($pageSrcKey, 1, $pageArr[0]) // Увеличиваем счетчик тегов на странице
                        ->hIncrBy($pageSrcKey.":".$source['class'].':'.$source['id'], $link['class'].':'.intval($outer), 1) // счетчик типа тега на странице
                        ->exec();
                } else {
                    /*Yii::error("Не удалось создать тег " . print_r($tag->attributes, 1) . "\n" .
                        print_r($tag->getErrors(), 1), 'InitController.buildTagsByClassName');*/
                }
            }

            if($insertedTag)
                $srcIndex--;
            else if($breakIteration)
                break;
        }
    }

    /**
     * Генерирует теги на страницы поиска
     */
    public function actionSearchPageTagsGenerate()
    {

        // Генерим теги, зависящие от городов
        $batchInsert = [];
        $tag = new SearchPageTags();
        $batchAttributes = $tag->attributes();

        $trans = Yii::$app->db->beginTransaction();
        SearchPageTags::deleteAll();

        // Ссылки генерим на страницы поиска существующих городов
        $query = FastCity::find()->limit(1000);
        while ($items = $query->all()) {
            $query->offset += $query->limit;

            foreach($items as $city){
                // Определяем количество грузов в поиске на текущем домене
                $searchModel = new CargoSearch();
                $queryParams['CargoSearch']['locationFrom'] = $city->cityid;
                $dataProvider = $searchModel->search($queryParams);
                $pageCount = $dataProvider->pagination->pageCount;
                if($pageCount >= 16) {
                    for ($i = 16; $i <= $pageCount; $i = $i + 10) {
                        array_push($batchInsert, [
                            'id' => null,
                            'name' => "Страница {$i} - поиск грузов",
                            'cityid' => $city->cityid,
                            'url' => "https://" . $city->code . "." . Yii::getAlias('@domain') . Url::toRoute([
                                    'cargo/search/index',
                                    'page' => $i
                                ])
                        ]);
                    }

                    // Выходящие за верхние границы страницы
                    $ost = $pageCount % 10;
                    if($ost > 0 && $ost < 6){
                        $i = $pageCount;
                        array_push($batchInsert, [
                            'id' => null,
                            'name' => "Страница {$i} - поиск грузов",
                            'cityid' => $city->cityid,
                            'url' => "https://" . $city->code . "." . Yii::getAlias('@domain') . Url::toRoute([
                                    'cargo/search/index',
                                    'page' => $i
                                ])
                        ]);
                    }
                }

                // Определяем количество перевозчиков в поиске на текущем домене
                $searchModel = new TransportSearch();
                $queryParams['TransportSearch']['locationFrom'] = $city->cityid;
                $dataProvider = $searchModel->search($queryParams);
                $pageCount = $dataProvider->pagination->pageCount;
                if($pageCount > 50) $pageCount = 50; // Сфинкс выводит только 1000 позиций, что соответствует максимум 50 странице
                if($pageCount >= 16) {
                    for ($i = 16; $i <= $pageCount; $i = $i + 10) {
                        array_push($batchInsert, [
                            'id' => null,
                            'name' => "Страница {$i} - поиск перевозчиков",
                            'cityid' => $city->cityid,
                            'url' => "https://" . $city->code . "." . Yii::getAlias('@domain') . Url::toRoute([
                                    'transport/search/index',
                                    'page' => $i
                                ])
                        ]);
                    }

                    // Выходящие за верхние границы страницы
                    $ost = $pageCount % 10;
                    if($ost > 0 && $ost < 6){
                        $i = $pageCount;
                        array_push($batchInsert, [
                            'id' => null,
                            'name' => "Страница {$i} - поиск перевозчиков",
                            'cityid' => $city->cityid,
                            'url' => "https://" . $city->code . "." . Yii::getAlias('@domain') . Url::toRoute([
                                    'transport/search/index',
                                    'page' => $i
                                ])
                        ]);
                    }

                }
            }

            if(count($batchInsert)>=1000) {
                $sql = SqlHelper::buildBatchInsertQuery(SearchPageTags::tableName(), $batchAttributes, $batchInsert, true);
                Yii::$app->db
                    ->createCommand($sql)
                    ->execute();

                $batchInsert = [];
            }
        }

        if(count($batchInsert)) {
            $sql = SqlHelper::buildBatchInsertQuery(SearchPageTags::tableName(), $batchAttributes, $batchInsert, true);
            Yii::$app->db
                ->createCommand($sql)
                ->execute();
        }

        $trans->commit();
    }
}
