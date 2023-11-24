<?php
/**
 * Created by PhpStorm.
 * User: ������� ������
 * Date: 14.07.2016
 * Time: 13:54
 */

namespace console\controllers;

use common\behaviors\UploadImageBehavior;
use common\components\telegram\Telegram;
use common\helpers\CodeHelper;
use common\helpers\Convertor;
use common\helpers\GoogleMapsResponse;
use common\helpers\SlugHelper;
use common\helpers\Utils;
use common\helpers\UTMHelper;
use common\models\Cargo;
use common\models\CargoCategory;
use common\models\City;
use common\models\FastCity;
use common\models\Profile;
use common\models\Transport;
use common\SphinxModels\SphinxTransport;
use console\helpers\NotifyHelper;
use console\helpers\tomita\TomitaHelper;
use console\jobs\CargoRoute;
use console\jobs\jobData\NotifyCarrierData;
use console\models\companies\TesgroupRu;
use Dompdf\Exception;
use frontend\modules\subscribe\models\SubscribeRules;
use frontend\modules\tk\models\Tk;
use GuzzleHttp\Client;
use JMS\Serializer\SerializerBuilder;
use krtv\yii2\serializer\Serializer;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Request;
use Redis;
use Svezem\Services\MatrixContentService\MatrixContentService;
use Svezem\Services\PaymentService\Classes\ExtendedStatusRequest;
use Svezem\Services\PaymentService\Gates\Sberbank\Input\CallbackData;
use Svezem\Services\PaymentService\Gates\Sberbank\SberbankGate;
use Svezem\Services\PaymentService\PaymentService;
use VipIp\Core\Log\Log;
use VK\Client\VKApiClient;
use VK\Exceptions\Api\ExceptionMapper;
use VK\Exceptions\VKApiException;
use Yandex\Geo\Api;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Expression;
use yii\db\Query;
use yii\helpers\Url;
use yii\sphinx\MatchExpression;
use yii\web\BadRequestHttpException;

//===============

//===============


class TestController extends Controller
{
    /**
     * �������� �������� ��� ���������
     */
    public function actionTestSendSms()
    {
        //\Yii::$app->sms->sendSMS('+79080191886', 'Test message', 'Svezem.ru');
        //\Yii::$app->sms->sendSMS('+79233026030', 'Test message', 'Svezem.ru');
        if (Yii::$app->sms->smsSend('89080191886',
            '������! ��� ����! :) ������! ��� ����! :) ������! ��� ����! :) ������! ��� ����! :) ������! ��� ����! :) ������! ��� ����! :) ������! ��� ����! :) ������! ��� ����! :) ������! ��� ����! :) ������! ��� ����! :) ������! ��� ����! :) ������! ��� ����! :) ������! ��� ����! :) ������! ��� ����! :) ������! ��� ����! :) ')) {
            echo '����������';
        } else {
            echo '�� ������� ���������';
        }
    }

    public function actionNotifyCarrier($cargoid)
    {
        $jobNotify = new NotifyCarrierData();
        $jobNotify->cargo_id = $cargoid;
        //$jobNotify->booking_only = 1;

        Yii::$app->gearman->getDispatcher()->background($jobNotify->getJobName(), $jobNotify);
    }

    public function actionPageGenerate()
    {
        $job = new TransportGenerateLinks();

        $data = [
            "transport_id" => 44,
            "oldAttributes" => [
                'id' => 44
            ],
            "oldCities" => [
                "from" => [104, 138],
                "to" => [22, 73]
            ],
            "oldCategories" => [1, 2, 3, 5]
        ];

        $job->exec2($data);

        /*Yii::$app->gearman->getDispatcher()->background("cargoGenerateLinks", [
            'cargo_id'=>150,
            'oldAttributes' => [
                'cargo_id'=>150,
                'cargo_category_id' => 1
            ],
            'oldCities' => [
                'from'  => 144,
                'to'    => 122
            ]
        ]);*/
    }

    public function actionQuery()
    {
        $query = Cargo::find();
        $query->where(['status' => Cargo::STATUS_ACTIVE]);

        $country_to = 1;

        $subQuery = CargoLocation::find()
            ->joinWith('city')
            ->select('cargo_location.id')
            ->where("cargo_location.cargo_id = {{".Cargo::tableName()."}}.[[id]]")
            ->andWhere([
                'and',
                ['cities.country_id' => $country_to],
                ['cargo_location.type' => CargoLocation::TYPE_UNLOADING],
            ]);
        $query->andFilterWhere(['exists', $subQuery]);

        $res = $query->count();

        print_r($res);
    }

    public function actionMap($id)
    {
        /** @var $model Cargo|Transport */
        $cargo = Cargo::findOne($id);

        $fromText = $cargo->cityFrom->title_ru.", ".$cargo->cityFrom->region_ru;
        $toText = $cargo->cityTo->title_ru.", ".$cargo->cityTo->region_ru;

        /* @var $map GoogleMapsResponse */
        $map = Yii::$app->googleMaps->distancematrix($fromText, $toText);

        $distance = $map->getDistance();
        $duration = $map->getDuration();

        echo "Distance: $distance ".Convertor::distance($distance)."\n";
        echo "Duration: $duration\n";

        return ExitCode::OK;
    }

    public function actionTransportUpdate()
    {
        $models = Transport::find()
            ->where(['status' => Transport::STATUS_ACTIVE])
            ->all();

        foreach ($models as $model) {
            echo "$model->id \n";
            if ( !$model->save(false)) {
                print_r($model->getErrors());
            }
        }
    }

    public function actionCargoNotify()
    {
        //Формирование тегов груза
        //for($i=0; $i<100; $i++)
        Yii::$app->gearman->getDispatcher()->background("notifyCarrier", [
            'cargo_id' => 176
        ]);
    }

    public function actionTransportNotify()
    {
        //Формирование тегов груза
        for ($i = 0; $i < 100; $i++) {
            Yii::$app->gearman->getDispatcher()->background("notifySender", [
                'transport_id' => 74
            ]);
        }
    }

    public function actionTkImageRegenerate()
    {
        $query = Tk::find()
            ->orderBy(['id' => SORT_ASC])
            ->limit(500);

        $total = $query->count();
        $done = 0;

        while ($tks = $query->all()) {
            $query->offset += $query->limit;

            foreach ($tks as $tk) {
                $done++;

                /** @var UploadImageBehavior $images */
                $images = $tk->behaviors['images'];
                $attribute = $images->attribute;
                $thumbs = $images->thumbs;

                $path = $tk->getUploadPath($attribute);

                if ( !file_exists($path)) {
                    continue;
                }

                foreach ($thumbs as $profile => $config) {
                    $thumbPath = $tk->getThumbUploadPath($attribute, $profile);
                    if ($thumbPath !== null) {
                        try{
                            $tk->generateImageThumb($config, $path, $thumbPath);
                        } catch (\Exception $e){
                            echo "Error: ".$e->getMessage()."\n";
                        }
                    }
                }

                echo BaseController::progress_bar($done, $total, ' Обработано картинок');
            }
        }

        echo BaseController::progress_bar(1, 1, '');
    }

    public function actionTransportImageRegenerate($from_id = null, $to_id = null)
    {
        ini_set('memory_limit', '1024M');

        $query = Transport::find()
            ->orderBy(['id' => SORT_ASC])
            ->limit(500);

        if ($from_id) {
            $query->where(['>=', 'id', $from_id]);
        }
        if ($to_id) {
            $query->andWhere(['<=', 'id', $to_id]);
        }

        $total = $query->count();
        $done = 0;

        while ($transports = $query->all()) {
            $query->offset += $query->limit;

            foreach ($transports as $transport) {
                $done++;

                /** @var UploadImageBehavior $images */
                $images = $transport->behaviors['images'];
                $attribute = $images->attribute;
                $thumbs = $images->thumbs;

                $path = $transport->getUploadPath($attribute);

                if ( !file_exists($path)) {
                    continue;
                }

                foreach ($thumbs as $profile => $config) {
                    $thumbPath = $transport->getThumbUploadPath($attribute, $profile);
                    if ($thumbPath !== null) {
                        try{
                            $transport->generateImageThumb($config, $path, $thumbPath);
                        } catch (\Exception $e){
                            echo "Error: ".$e->getMessage()."\n";
                        }
                    }
                }

                echo BaseController::progress_bar($done, $total, ' Обработано картинок');
            }
        }

        echo BaseController::progress_bar(1, 1, '');
    }

    public function actionProfileResave()
    {
        $limit = 500;

        $query = Profile::find()->limit($limit);
        while ($profiles = $query->all()) {
            $query->offset += $limit;

            foreach ($profiles as $profile) {
                $profile->save(false);

                echo "{$profile->id}\n";
            }
        }

        return ExitCode::OK;
    }

    public function actionCargoDistance()
    {
        $limit = 500;

        $query = Cargo::find()->where(['id' => 284])->limit($limit);
        while ($cargos = $query->all()) {
            $query->offset += $limit;

            foreach ($cargos as $cargo) {
                Yii::$app->gearman->getDispatcher()->background("DistanceObject", [
                    'object' => 'cargo',
                    'object_id' => $cargo->id
                ]);
            }
        }

        return ExitCode::OK;
    }

    public function actionTransportDistance()
    {
        $limit = 500;

        $query = Transport::find()->limit($limit);
        while ($transports = $query->all()) {
            $query->offset += $limit;

            foreach ($transports as $transport) {
                Yii::$app->gearman->getDispatcher()->background("DistanceObject", [
                    'object' => 'transport',
                    'object_id' => $transport->id
                ]);
            }
        }

        return ExitCode::OK;
    }

    public function actionPassing()
    {
        /*RouteHelper::getPassingCargo(1,57,30, ['build'=>true]);
        die();*/

        $cargo = Cargo::findOne(71);
        $cityFrom = $cargo->oneCityFrom;
        $cityTo = $cargo->oneCityTo;

        /** @var Redis $redis */
        $redis = Yii::$app->redisGeo;

        $radius = 30;

        $cityFromId = 1;//$cityFrom->id
        $cityToId = 57;//$cityTo->id;

        $routeKey = 'route:'.$cityFromId.'_'.$cityToId;
        //получаем маршрут искомого груза
        $route = $redis->geoRadiusByMember($routeKey, 0, 999999, 'km', ['withcoord']);

        //отсортируем по индексу
        usort($route, function ($item1, $item2){
            return $item1[0] > $item2[0];
        });

        //и преобразуем в удобный массив
        $route = array_map(function ($item){
            return $item[1];
        }, $route);

        $rand = rand(1, 99999);
        $startIdsKey = 'startIds_'.$rand;
        $startIdsUnionKey = 'startIdsUnion_'.$rand;

        $finishIdsKey = 'finishIds_'.$rand;

        $interIds = 'interIds_'.$rand;
        $interIdsUnion = 'interIdsUnion_'.$rand;

        foreach ($route as $routePoint) {
            //из точки маршрута получаем ид грузов, сохраняем в отдельный ключ
            $redis->geoRadius(CargoRoute::REDIS_CARGO_MAP_START, $routePoint[0], $routePoint[1], $radius, 'km',
                ['store' => $startIdsKey]);
            //далее добавляем эти ид в общий список ид грузов
            $redis->zUnionStore($startIdsUnionKey, [$startIdsUnionKey, $startIdsKey]);

            $redis->geoRadius(CargoRoute::REDIS_CARGO_MAP_FINISH, $routePoint[0], $routePoint[1], $radius, 'km',
                ['store' => $finishIdsKey]);

            $redis->zInterStore($interIds, [$startIdsUnionKey, $finishIdsKey]);
            $redis->zUnionStore($interIdsUnion, [$interIdsUnion, $interIds]);
        }

        $passingKey = 'passingCargoByRoute:'.$cityFromId.'_'.$cityToId.'_'.$radius;

        $redis->del($passingKey);

        $ids = $redis->zRange($interIdsUnion, 0, -1);

        $redis->del($startIdsKey, $startIdsUnionKey, $finishIdsKey);
        $redis->del($interIds, $interIdsUnion);

        foreach ($ids as $id) {
            $redis->sAdd($passingKey, $id);
        }
    }

    public function actionCargoGenerate()
    {
        $profile = Profile::findOne(330);

        for ($i = 0; $i < 5000; $i++) {
            $cargo = new Cargo();
            $Behavior = $cargo->getBehavior('BlameableBehavior');
            if ($Behavior) {
                $Behavior->value = $profile->created_by;
            }

            $cargo->profile_id = $profile->id;

            /** @var City $cityFrom */
            $cityFrom = City::find()
                ->where(['country_id' => 1])
                ->andWhere(['not', ['latitude' => null]])
                ->orderBy(new Expression('rand()'))
                ->one();

            /** @var City $cityTo */
            $cityTo = City::find()
                ->where(['country_id' => 1])
                ->andWhere(['not', ['latitude' => null]])
                ->orderBy(new Expression('rand()'))
                ->one();

            $cl = new CargoLocation();
            $cl->city_id = $cityFrom->id;
            $cl->type = CargoLocation::TYPE_LOADING;
            $cargo->cargoLocationsFrom = [$cl];

            $cl = new CargoLocation();
            $cl->city_id = $cityTo->id;
            $cl->type = CargoLocation::TYPE_UNLOADING;
            $cargo->cargoLocationsTo = [$cl];

            $cargo->status = Cargo::STATUS_ACTIVE;
            $cargo->paymentMethodIds = [1]; //Наличные
            $cargo->is_any_date = 1; //любое время
            $cargo->description = 'Сгенерированный груз';

            if ( !$cargo->save()) {
                print_r($cargo->getErrors());
                die;
            }
        }
    }

    public function actionCityCoordinates()
    {
        $api = new Api();
        // Настройка фильтров
        $api->setLimit(1)// кол-во результатов
        ->setLang(Api::LANG_RU);

        $query = City::find()
            ->where(['country_id' => 1])
            ->andWhere(['latitude' => null])
            ->orderBy('id')
            ->limit(500);

        $commonLimit = 13000;

        $stop = false;
        /** @var City[] $cities */
        while ($cities = $query->all()) {
            $query->offset += $query->limit;

            foreach ($cities as $city) {
                $string = $city->region_ru." ".$city->title_ru;

                try{
                    $commonLimit--;
                    $api->setQuery($string)->load();
                } catch (Exception $e){
                    echo "Error cargo id: ".$city->id."\n";
                    echo $e->getMessage()."\n";
                    continue;
                }

                if ( !$commonLimit) {
                    $stop = true;
                    break;
                }

                $response = $api->getResponse();
                // Список найденных точек
                $collection = $response->getList();

                if ( !isset($collection[0])) {
                    continue;
                }

                $item = $collection[0];

                City::updateAll([
                    'latitude' => $item->getLatitude(),
                    'longitude' => $item->getLongitude()
                ], "id=".$city->id);
            }

            if ($stop) {
                break;
            }
        }
    }

    public function actionCreateNotify($cargoid, $new = 1)
    {
        Yii::$app->gearman->getDispatcher()->background("notifyCarrier", [
            'cargo_id' => $cargoid,
            'isNewModel' => $new
        ]);
    }

    /**
     * @param $id
     */
    public function actionPay($id)
    {
        Yii::$app->gearman->getDispatcher()->background("paymentProcess", [
            'payment_id' => $id
        ]);
    }

    public function actionTkSearch()
    {
        $tk = new TesgroupRu();
        $tk->setAttributes([
            /*'from_city_id' => 73,
            'to_city_id' => 2,*/

            'from_city_name' => 'Красноярск',
            'to_city_name' => 'Екатеринбург',

            'weight' => 30,

            'width' => 1,
            'height' => 1,
            'depth' => 1,
        ]);

        $tk->parse();
    }

    public function actionSubEdit()
    {
        $cargo = Cargo::findOne(3797);
        $rule = SubscribeRules::findOne(593);

        $text = NotifyHelper::subscribeSms($cargo, $rule->subscribe->userid, 201);
        echo $text."\n";
    }

    public function actionSphinx()
    {
        //$transports = SphinxTransportCommon::find()
        //$transports = SphinxTransportRealTime::find()
        $transports = SphinxTransport::find()
            ->select('id, WEIGHT() AS city_from')
            ->match(new MatchExpression('(грузоперевозка | перевозка груза | транспортные | грузовые | услуги)'))
            ->limit(10)
            //->andWhere(['id'=>103359])
            ->all();

        $iter = 0;
        foreach ($transports as $transport) {
            $iter++;
            echo $iter.'. '.$transport->id." ".$transport->city_from."\n";
        }
    }

    public function actionFixFastCity()
    {
        $fastCities = FastCity::find()
            ->where(['like', 'title', "("])
            ->orWhere(['like', 'code', "-"])
            ->all();

        foreach ($fastCities as $fastCity) {
            echo $fastCity->title."\n";
            $fastCity->code = strtolower(SlugHelper::rus2translit($fastCity->title));
            $fastCity->save();
        }
    }

    public function actionRoute()
    {
        //echo Url::toRoute(['/cargo/default/view', 'city' => 'sd', 'id' => 5])."\n";
        echo Url::toRoute(['/articles/default/view', 'slug' => 'sd'])."\n";
    }

    public function actionPass()
    {
        echo Yii::$app->security->generatePasswordHash('123456')."\n";
    }

    public function actionSearch($tr_id)
    {
        Yii::$app->gearman->getDispatcher()->background("transportPosition", [
            'transport_id' => $tr_id
        ]);
    }

    public function actionAddFastCityCargo($id)
    {
        Yii::$app->gearman->getDispatcher()->background("addFastCity", [
            'cargo_id' => $id
        ]);
    }

    public function actionAddFastCityTransport($id)
    {
        Yii::$app->gearman->getDispatcher()->background("addFastCity", [
            'transport_id' => $id
        ]);
    }

    public function actionRegenerateCargoTags()
    {
        $query = Cargo::find()
            ->limit(500);

        /** @var Cargo[] $cargos */
        while ($cargos = $query->all()) {
            $query->offset += $query->limit;

            foreach ($cargos as $cargo) {
                $cargo->generateTags();
            }
        }
    }

    public function actionSave()
    {
        $cargo = Cargo::findOne(8751);

        $cargo->scenario = Cargo::SCENARIO_BOOKING_SAVE;
        $cargo->booking_price = -5;

        $cargo->save();

        print_r($cargo->errors);
    }

    public function actionRepairFastCity()
    {
        $sql = 'SELECT id, city_from FROM `transport`
                WHERE `city_from` not in (select cityid from fast_city)
                group by city_from';

        $fc = array_map(function ($f){
            return $f->cityid;
        }, FastCity::find()->select('cityid')->all());

        $rows = (new Query())
            ->select(['id', 'city_from'])
            ->from('transport')
            ->where(['not in', 'city_from', $fc])
            ->groupBy('city_from')
            ->all();

        foreach ($rows as $row) {
            Yii::$app->gearman->getDispatcher()->background("addFastCity", [
                'transport_id' => $row['id']
            ]);
        }
    }

    public function actionBuildPageCache($id)
    {
        Yii::$app->gearman->getDispatcher()->background("buildPageCache", [
            'cargo_id' => $id
        ]);
    }

    public function actionFetchUrl($url)
    {
        Yii::$app->gearman->getDispatcher()->background("fetchUrl", [
            'url' => $url
        ]);
    }

    public function actionCargoName($id)
    {
        $cargo = Cargo::findOne($id);

        if ( !$cargo) {
            die("Груз не найден\n");
        }

        /** @var TomitaHelper $tomita */
        $tomita = Yii::$container->get(TomitaHelper::class);

        $parser = $tomita->parseCargoName($cargo->description);

        $words = $parser->getWords();

        if ( !$words) {
            echo "Не удалось распарсить\n";
        } else {
            echo $cargo->description."\n";
            print_r($words);
        }
    }

    public function actionElastic()
    {
        $client = new Client([
            'base_uri' => 'elasticsearch:9200',
            'auth' => ['elastic', 'changeme'],
            'headers' => [
                'Content-Type' => 'application/json'
            ],
        ]);
        try{
            //$response = $client->get('svezem-cargo-2020.01.20/_search');

            $response = $client->put('svezem-cargo-2020.01.22', [
                'body' => <<<BODY
{
  "mappings": {
    "properties": {
      "my_join_field": { 
        "type": "join",
        "relations": {
          "question": "answer" 
        }
      }
    }
  }
}
BODY
            ]);

        } catch (\Exception $e){
            echo $e->getMessage()."\n";
            die();
        }

        echo $response->getBody()->getContents()."\n";
    }

    public function actionSendMail(){
        $view = null;
        $params = [];
        $email = 'micmorozov@gmail.com';
        $subject = 'Тест';
        $body = 'Привет, это тестовое сообщение';

        Yii::$app->mailer->compose($view, $params)
            // ->addHeader('list-unsubscribe', '<https://svezem.ru>')
            ->setFrom([Yii::$app->params['supportEmail'] => 'Svezem.ru'])
            ->setTo($email)
            ->setSubject(json_decode("\"\xF0\x9F\x9A\x9A\"") . '1 Svezem.ru: '.$subject)
            ->setHtmlBody($body)
            ->send();
    }

    public function actionVkGroup(){
        $vkComponent = Yii::$app->vkTest;
        //echo $vkComponent->getTokenUrl();
        //return;

        $utmParams=[
            'utm_source'    => 'vk',
            'utm_medium'    => 'group'.$vkComponent->group_id,
            'utm_compaign'  => 'notify_carrier'
        ];

        //Т.к страница груза находится в статике, переход делаем на главный домен
        $attachments = UTMHelper::genUTMLink('https://svezem.ru/', $utmParams);

        $vk = new VKApiClient();
        try {
            /*$res = $vk->messages()->send($vkComponent->access_token, [
                //'user_id' => '-'.$vkComponent->group_id,
                'random_id' => 1,
                'peer_id' => $vkComponent->group_id,
                'message' => 'Тестовое Cообщение'
              //  'attachment' => $attachments
            ]);*/
            //echo $vkComponent->access_token;
            //echo $vkComponent->group_id;
            $res = $vk->wall()->post($vkComponent->access_token, [
                'owner_id' => $vkComponent->group_id,
                'message' => 'Поиск заявок на перевозку',
            ]);
            var_dump($res);
        }catch(VKApiException $e){
            echo $e->getCode() . ' ' . $e->getMessage();
        }
    }

    /**
     * Отправляем уведомление в телеграм
     */
    public function actionSendTelegram()
    {
        /** @var Telegram $t */
        $t = Yii::$container->get(Telegram::class);
        Request::initialize($t);

        $chatId = '@svezem_test_channel';
        $cargoid = 13214;
        $cargo = Cargo::findOne($cargoid);

        $keyboard = new InlineKeyboard([
            ['text' => 'Взять в работу!', 'callback_data' => http_build_query([
                'cmd' => 'CargoGetJob',
                'cargoid' => $cargoid
            ])]
        ]);
        $keyboard->setResizeKeyboard(true);

        $message = Request::sendMessage([
            'chat_id' => $chatId,
            'text' => $cargo->description,
            'parse_mode' => 'html',
            'disable_web_page_preview' => true,
            'reply_markup' => $keyboard
        ]);

        print_r($message);

    }

    public function actionEditProfile()
    {
        $query = Profile::find()
            ->limit(1500);

        /** @var Profile[] $profiles */
        while ($profiles = $query->all()) {
            $query->offset += $query->limit;

            foreach ($profiles as $profile) {
                if($profile->contact_person && preg_match('/^[:)?!,`~_-]+$/iu', $profile->contact_person) && $profile->type != Profile::TYPE_SENDER) {
                    $profile->contact_person = 'Перевозчик';
                    $profile->name = 'Перевозчик';
                    if(!$profile->save()){
                        var_dump($profile->getErrors());
                        throw new \yii\db\Exception('Error');
                    }
                    //Profile::updateAll(['contact_person' => null], 'id=:id', [':id' => $profile->id]);

                    echo "[{$profile->id}] {$profile->contact_person} \n";
                }
            }
        }
    }

    public function actionDropDoubleCity()
    {
        $cities = Yii::$app->db->createCommand('SELECT country_id, region_id, title_ru, area_ru, region_ru, count(*) cnt 
FROM `cities` 
group by country_id, region_id, title_ru, area_ru, region_ru
having cnt > 1
order by  cnt desc')->queryAll();

        foreach($cities as $city){
            $cityList = City::find()->where([
                    'region_id' => $city['region_id'],
                    'country_id' => $city['country_id'],
                    'title_ru' => $city['title_ru']
            ])
                ->orderBy(['code' => SORT_ASC])
                ->all();


            $index=0;
            foreach($cityList as $c) {
                $index++;
                if($index==1) continue;

                $c->delete();
                echo $c->id . ' ' . $c->code."\n";
            }
        }
    }

    public function actionPayment()
    {
        $paymentGate = Yii::$container->get(PaymentService::class, [Yii::$container->get(SberbankGate::class)]);
        /*$paymentUrl = $paymentGate->getPaymentUrl([
            'orderNumber' => 2,
            'amount' => 100,
            'currency' => 643,
            'returnUrl' => 'https://svezem.ru',
            'failUrl' => 'https://svezem.ru',
            'description' => 'Описание платежа'
        ]);

        echo $paymentUrl;*/

        $status = $paymentGate->getExtendedStatus(new ExtendedStatusRequest(1));
        var_dump($status);
    }

    public function actionMatrixContent()
    {
        $matrixContent =  Yii::$container->get(MatrixContentService::class);

    }
}
