<?php

namespace common\models;

use common\behaviors\SlugBehavior;
use common\components\limiter\RateLimiter;
use common\helpers\LocationCacheHelper;
use common\helpers\SlugHelper;
use common\helpers\TemplateHelper;
use common\helpers\Utils;
use common\models\query\CargoQuery;
use common\models\traits\FromToLocationTrait;
use common\modules\NeuralNetwork\NeuralNetwork;
use common\validators\CargoStopWordValidator;
use console\jobs\CalcDistance;
use console\jobs\jobData\AutoCategoryData;
use console\jobs\jobData\BuildPageCacheData;
use console\jobs\jobData\CalcDistanceData;
use console\jobs\jobData\CargoNameData;
use console\jobs\jobData\NotifyCarrierData;
use micmorozov\yii2\gearman\Dispatcher;
use simialbi\yii2\schemaorg\models\Place;
use simialbi\yii2\schemaorg\models\PostalAddress;
use simialbi\yii2\schemaorg\models\SendAction;
use simialbi\yii2\schemaorg\models\Thing;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;
use Yii;
use yii\base\InvalidConfigException;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\caching\TagDependency;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\di\NotInstantiableException;
use yii\helpers\Url;
use frontend\modules\cabinet\models\CargoBookingComment;

/**
 * This is the model class for table "cargo".
 *
 * @property int $id
 * @property int $created_by
 * @property int $updated_by
 * @property int $profile_id
 * @property int $city_from Город отправки
 * @property int $city_to Город доставки
 * @property int $region_from Регион отправки
 * @property int $region_to Регион доставки
 * @property int $cargo_category_id
 * @property int $tomita_category
 * @property int $neural_category Категория нейросети
 * @property int $auto_category категория присвоена автоматически
 * @property string $name
 * @property string $name_rod наименование в родительном падеже
 * @property string $name_vin наименование в винительном падеже
 * @property string $description
 * @property int $status
 * @property int $created_at
 * @property int $updated_at
 * @property int $views_count
 * @property int $distance Расстояние (м)
 * @property int $duration Время в пути (c)
 * @property string $delete_reason Причина удаления
 * @property int $booking_by Кем забронирован
 * @property int $booking_at Время бронирования
 * @property double $booking_price Стоимость бронирования
 * @property int|null $phone_view Просмотры номера
 * @property string $slug ЧПУ
 * @property User $bookingBy
 * @property City $cityFrom
 * @property City $cityTo
 * @property User $createdBy
 * @property User $updatedBy
 * @property CargoCategory $cargoCategory
 * @property CargoCategory $tomitaCategory
 * @property CargoBookingComment[] $bookingComment
 * @property Profile $profile
 * @property CargoCategory $neuralCategory
 * @property Region $regionFrom
 * @property Region $regionTo
 * @property CargoCategory[] $categories
 * @property CargoCategory[] $tomitaCategories
 * @property CargoCategory[] $moderCategories
 * @property string $icon
 * @property string $iconPng
 * @property bool $isExpired
 * @property SendAction $structured
 * @property array $categoriesId
 * @property string $url
 * @property CargoCategory[] $realCategories
 * @property CargoCategory[] $autoCategories
 */
class Cargo extends ActiveRecord implements FromToLocationInterface
{
    const STATUS_ACTIVE = 0;
    const STATUS_BANNED = 20;
    const STATUS_ARCHIVE = 40;
    const STATUS_WORKING = 50;
    const STATUS_DONE = 60;

    // Количество дней актуальности груза
    const DAYS_ACTUAL = 2;

    const SCENARIO_BOOKING_SAVE = 'booking_save';

    private $_categoriesId;
    //переменная заполняется только
    //при смене категории
    private $_categoriesToSave;

    private $_moderCategories;

    //Была ли задана категория
    private $isSetCategory = false;

    /**
     * {@inheritdoc}
     */
    public static function tableName(){
        return 'cargo';
    }

    public function behaviors(){
        return [
            TimestampBehavior::class,
            'BlameableBehavior' => [
                'class' => BlameableBehavior::class,
                //чтобы при изменении модели через консоль
                //значение updated_by не становилось null
                'updatedByAttribute' => Yii::$app instanceof yii\console\Application
                    ? null
                    : 'updated_by'
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules(){
        return [
            [['created_by', 'updated_by', 'profile_id', 'city_from', 'city_to', 'region_from', 'region_to', 'cargo_category_id',
                'neural_category', 'auto_category', 'status', 'created_at', 'updated_at', 'views_count', 'distance', 'duration',
                'booking_by', 'booking_at', 'phone_view'], 'integer'],
            [['description', 'delete_reason'], 'filter', 'filter' => '\yii\helpers\HtmlPurifier::process'],
            [['description'], CargoStopWordValidator::class],
            [['booking_price'], 'compare', 'compareValue' => 0, 'skipOnEmpty' => false, 'operator' => '>',
                'message' => 'Цена должна быть больше нуля',
                'on' => [self::SCENARIO_BOOKING_SAVE]],
            [['city_from', 'city_to', 'description'], 'required'],
            [['description'], 'string'],
            [['name'], 'string', 'max' => 255],
            [['name_rod', 'name_vin'], 'string', 'max' => 256],
            [['city_from'], 'exist', 'skipOnError' => true, 'targetClass' => City::class, 'targetAttribute' => ['city_from' => 'id']],
            [['city_to'], 'exist', 'skipOnError' => true, 'targetClass' => City::class, 'targetAttribute' => ['city_to' => 'id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['created_by' => 'id']],
            [['updated_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['updated_by' => 'id']],
            [['cargo_category_id'], 'exist', 'skipOnError' => true, 'targetClass' => CargoCategory::class, 'targetAttribute' => ['cargo_category_id' => 'id']],
            [['profile_id'], 'exist', 'skipOnError' => true, 'targetClass' => Profile::class, 'targetAttribute' => ['profile_id' => 'id']],
            [['neural_category'], 'exist', 'skipOnError' => true, 'targetClass' => CargoCategory::class, 'targetAttribute' => ['neural_category' => 'id']],
            ['categories', 'safe'],
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_BANNED, self::STATUS_ARCHIVE, self::STATUS_WORKING, self::STATUS_DONE]],
            [['region_from'], 'exist', 'skipOnError' => true, 'targetClass' => Region::class, 'targetAttribute' => ['region_from' => 'id']],
            [['region_to'], 'exist', 'skipOnError' => true, 'targetClass' => Region::class, 'targetAttribute' => ['region_to' => 'id']],
            [['booking_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['booking_by' => 'id']]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(){
        return [
            'id' => 'ID',
            'created_by' => 'Создан',
            'updated_by' => 'Изменен',
            'profile_id' => 'Профиль',
            'city_from' => 'Город отправки',
            'city_to' => 'Город доставки',
            'region_from' => 'Регион отправки',
            'region_to' => 'Регион доставки',
            'cargo_category_id' => 'Категория',
            'neural_category' => 'Категория нейросети',
            'tomita_category' => 'Категория tomita',
            'auto_category' => 'категория присвоена автоматически',
            'name' => 'Наименование',
            'name_rod' => 'наименование в родительном падеже',
            'name_vin' => 'наименование в винительном падеже',
            'description' => 'Описание',
            'status' => 'Статус',
            'created_at' => 'Дата создания',
            'updated_at' => 'Updated At',
            'views_count' => 'Views Count',
            'distance' => 'Расстояние (м)',
            'duration' => 'Время в пути (c)',
            'delete_reason' => 'Причина удаления',
            'booking_by' => 'Кем забронирован',
            'booking_at' => 'Время бронирования',
            'booking_price' => 'Стоимость бронирования',
            'phone_view' => 'Просмотры номера',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getBookingBy()
    {
        return $this->hasOne(User::className(), ['id' => 'booking_by']);
    }

    /**
     * @return ActiveQuery
     */
    public function getCityFrom():ActiveQuery
    {
        return $this->hasOne(City::class, ['id' => 'city_from']);
    }

    /**
     * @return ActiveQuery
     */
    public function getCityTo():ActiveQuery
    {
        return $this->hasOne(City::class, ['id' => 'city_to']);
    }

    /**
     * @return ActiveQuery
     */
    public function getCreatedBy(){
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /**
     * @return ActiveQuery
     */
    public function getUpdatedBy(){
        return $this->hasOne(User::class, ['id' => 'updated_by']);
    }

    /**
     * @return ActiveQuery
     */
    public function getCargoCategory(){
        return $this->hasOne(CargoCategory::class, ['id' => 'cargo_category_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getTomitaCategory(){
        return $this->hasOne(CargoCategory::class, ['id' => 'tomita_category']);
    }

    /**
     * @return ActiveQuery
     */
    public function getBookingComment(){
        return $this->hasMany(CargoBookingComment::class, ['cargo_id' => 'id']);
    }

    /**
     * @param $created_by
     * @return string
     */
    public function bookingCommentByUser($created_by){
        /** @var CargoBookingComment $comment */
        $comment = $this->getBookingComment()
            ->where(['created_by' => $created_by])
            ->one();

        return $comment ? $comment->comment : '';
    }

    /**
     * @return ActiveQuery
     */
    public function getProfile(){
        return $this->hasOne(Profile::class, ['id' => 'profile_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getNeuralCategory(){
        return $this->hasOne(CargoCategory::class, ['id' => 'neural_category']);
    }

    /**
     * @return ActiveQuery
     */
    public function getRegionFrom(){
        return $this->hasOne(Region::class, ['id' => 'region_from']);
    }

    /**
     * @return ActiveQuery
     */
    public function getRegionTo(){
        return $this->hasOne(Region::class, ['id' => 'region_to']);
    }


    /**
     * @return ActiveQuery
     */
    public function getCategories(){
        return $this->hasMany(CargoCategory::class, ['id' => 'category_id'])
            ->viaTable('cargo_category_assn', ['cargo_id' => 'id'])
            ->cache(2);
    }

    /**
     * @return ActiveQuery
     */
    public function getTomitaCategories(){
        return $this->hasMany(CargoCategory::class, ['id' => 'category_id'])
            ->viaTable('cargo_auto_category_assn', ['cargo_id' => 'id'])
            ->cache(2);
    }

    public function getModerCategories(){
        if( !$this->_moderCategories){
            $this->_moderCategories = array_filter($this->categories, function($model){
                /** @var $model CargoCategory */
                return $model->show_moder_cargo;
            });
        }
        return $this->_moderCategories;
    }

    /**
     * @return string
     */
    public function getIcon(){
        return CargoCategory::getIcon($this->cargo_category_id, true);
    }

    /**
     * @return string
     */
    public function getIconPng($absolute = true){
        return CargoCategory::getIconPng($this->cargo_category_id, $absolute);
    }

    /**
     * @return bool
     */
    public function getIsExpired(){
        $expiredDays = Setting::getValueByCode(Setting::CARGO_EXPIRE_DAYS, 30);
        return $this->created_at < strtotime("-$expiredDays days") || $this->status != self::STATUS_ACTIVE;
    }

    /**
     * Объект микроразметки
     * @return SendAction
     */
    public function getStructured(){
        $addressFrom = new PostalAddress();
        $addressFrom->addressLocality = $this->cityFrom->title_ru;
        $addressFrom->addressRegion = $this->cityFrom->region_ru;

        $addressTo = new PostalAddress();
        $addressTo->addressLocality = $this->cityTo->title_ru;
        $addressTo->addressRegion = $this->cityTo->region_ru;

        $placeFrom = new Place();
        $placeFrom->address = $addressFrom;

        $placeTo = new Place();
        $placeTo->address = $addressTo;

        $thing = new Thing();
        $thing->name = $this->name;
        $thing->description = $this->description;

        $transfer = new SendAction();

        $agent = $this->profile->structured;
        $agent->telephone = '+7 800 201-23-56';

        $transfer->agent = $agent;
        $transfer->fromLocation = $placeFrom;
        $transfer->toLocation = $placeTo;
        $transfer->object = $thing;

        return $transfer;
    }

    /**
     * @param bool $colored
     * @return array
     */
    public static function getStatusLabels($colored = false){
        return [
            self::STATUS_ACTIVE => ($colored ? '<span style="color: #3ab845">Заявка открыта</span>' : 'Заявка открыта'),
            self::STATUS_BANNED => ($colored ? '<span style="color: #ac4137">Заявка удалена</span>' : 'Заявка забанена'),
            self::STATUS_ARCHIVE => ($colored ? '<span style="color: #ac4137">Заявка в архиве</span>' : 'Заявка в архиве'),
            self::STATUS_WORKING => ($colored ? '<span style="color: #1b43bd">Заявка в работе</span>' : 'Заявка в работе'),
            self::STATUS_DONE => ($colored ? '<span style="color: #3ab845">Заявка выполнена</span>' : 'Заявка выполнена')
        ];
    }

    /**
     * @param bool|false $colored
     * @return string
     */
    public function getStatusLabel($colored = false){
        // Если груз не актуален возвращаем соответствующую строку
        if($this->status == self::STATUS_ACTIVE && $this->isExpired)
            return $colored ? '<span style="color: #ac4137">Заявка не актуальна</span>' : 'Заявка не актуальна';

        $labels = static::getStatusLabels($colored);

        return isset($labels[$this->status]) ? $labels[$this->status] : 'Неизвестный статус';
    }

    /**
     * @return array|mixed
     */
    public function getCategoriesId(){
        if( !$this->_categoriesId){
            $this->_categoriesId = array_map(function($model){
                /** @var $model CargoCategory */
                return $model->id;
            }, $this->categories);
        }
        return $this->_categoriesId;
    }

    /**
     * @param $val
     */
    public function setCategories($val){
        if( !isset($val['list']))
            $val['list'] = [];

        $this->_categoriesToSave = $val['list'];

        if( !isset($val['main']))
            $val['main'] = '';
        $this->cargo_category_id = $val['main'];
    }

    /**
     * Получение ссылки просмотра груза
     * @return string
     */
    public function getUrl(){
        return Url::toRoute([
            '/cargo/default/view2',
            'id' => $this->id,
            'slug' => $this->slug
        ]);

        /*return Url::toRoute([
            '/cargo/default/view',
            'city' => $this->cityFrom->code,
            'id' => $this->id
        ]);*/
    }

    /**
     * Возвращает реальные категории грузов
     * без типа транспорта и частных перевозок
     * @return CargoCategory[]
     */
    public function getRealCategories(){
        return array_filter($this->categories, function($cat){
            /** @var $cat CargoCategory */
            return $cat->transportation_type !== 1 && $cat->private_transportation !== 1;
        });
    }

    /**
     * {@inheritdoc}
     * @return CargoQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new CargoQuery(get_called_class());
    }

    public function afterFind()
    {
        parent::afterFind();

        //Необходимо в afterSave
        $this->isSetCategory = (bool)$this->cargo_category_id;
    }

    /**
     * @return bool
     */
    public function beforeValidate(){
        if( !parent::beforeValidate())
            return false;

        if(isset($this->_categoriesToSave)){
            if( !in_array($this->cargo_category_id, $this->_categoriesToSave)){
                $this->addError('categories', 'Главная категория отсутсвует в списке выбранных');
            }
        }

        return !$this->hasErrors();
    }

    public function beforeSave($insert){
        if($insert){
            $nn = new NeuralNetwork();
            $vector = $nn->getVectorByText($this->description);
            $category = $nn->result($vector);

            if($category)
                $this->neural_category = $category;

            $uInt = Setting::getValueByCode(Setting::CARGO_UNIQ_INTERVAL, null);
            $limParams = json_decode($uInt, 1);

            if( !is_array($limParams) ){
                $limParams = [600 => 1];
            }

            /** @var RateLimiter $limiter */
            $limiter = Yii::$container->get(RateLimiter::class);
            foreach($limParams as $time => $number){
                $limiter->addLimit($number, $time);
            }

            $limiterKey = "createCargo:{$this->profile_id}:{$this->city_from}:{$this->city_to}";
            if( $limiter->limitExceeded($limiterKey) ){
                    $c = self::find()->where([
                            'city_from' => $this->city_from,
                            'city_to' => $this->city_to,
                            'profile_id' => $this->profile_id
                        ])
                        ->orderBy(['id' => SORT_DESC])
                        ->one();

                    if( $c ) {
                        $this->addError('showAlert', 'Вы недавно оставляли похожую заявку. Пожалуйста, не создавайте дублей. Если хотите, <a href="//'.Yii::getAlias('@domain').'/cargo/update/?id='.$c->id.'" target="_blank">отредактируйте заявку</a> добавленную ранее');
                    } else {
                        $this->addError('showAlert', 'Вы недавно оставляли похожую заявку. Пожалуйста, не создавайте дублей');
                    }
                return false;
            }
        }

        /////////////////////
        // Формирование slug
        $this->slug = (function(){
            $slugParts[] = $this->cargo_category_id ? $this->cargoCategory->slug : 'perevozka';

            $slugParts[] = $this->cityFrom->code;
            if($this->city_from != $this->city_to)
                $slugParts[] = $this->cityTo->code;

            return SlugHelper::genSlug($slugParts);
        })();
        ////////////////////

        $oldAttributes = $this->getOldAttributes();

        $oldCityFrom = $oldAttributes['city_from']??null;
        $oldCityTo = $oldAttributes['city_to']??null;

        //если новая модель или изменился город
        if($this->isNewRecord || $oldCityFrom != $this->city_from){
            $city = City::findOne($this->city_from);
            $this->region_from = $city->region_id;
        }
        if($this->isNewRecord || $oldCityTo != $this->city_to){
            $city = City::findOne($this->city_to);
            $this->region_to = $city->region_id;
        }

        if( $this->status == self::STATUS_ACTIVE ){
            $pattern = '#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#i';
            $num_found = preg_match_all($pattern, $this->description, $match);

            if( $num_found ){
                $this->addError('description', 'Описание содержит ссылки');
            }
            $text_after_strip = strip_tags($this->description);

            if(strcmp($this->description, $text_after_strip) != 0){
                $this->addError('description', 'Описание содержит HTML теги');
            }

            if( $this->hasErrors() )
                return false;
        }

        return parent::beforeSave($insert);
    }

    public function afterSave($insert, $changedAttributes){
        //если были указаны категории
        if(isset($this->_categoriesToSave)){
            $categories = CargoCategory::find()
                ->where(['id' => $this->_categoriesToSave])
                ->all();

            //Проверяем есть ли среди категорий "Аренда"
            $rentCategoryFilter = array_filter($categories, function ($m){
                /** @var CargoCategory $m */
                return $m->rent;
            });

            //Если "аренды" нет, то дополняем категории
            if( empty($rentCategoryFilter) ){
                $categories[] = CargoCategory::find()->where(['transportation_type' => 1])->one();
                $categories[] = CargoCategory::find()->where(['private_transportation' => 1])->one();
            }

            $categories = Utils::arrayUniqueObjects($categories, function ($model){
                /* @var CargoCategory $model */
                return $model->id;
            });

            $this->unlinkAll('categories', true);
            foreach($categories as $cat){
                $this->link('categories', $cat);
            }

            //необходимо очистить переменные чтобы при обращении
            //через getter они были переопределены
            $this->_categoriesId = null;
            $this->categories = null;
        }

        //если изменился город
        if(isset($changedAttributes['city_from']) || isset($changedAttributes['city_to'])){
            //Job расчета растояния
            $calcJob = new CalcDistanceData();
            $calcJob->behavior = CalcDistance::BEHAVIOR_OBJECT;
            $calcJob->objectClass = self::class;
            $calcJob->objectId = $this->id;

            $buildPageCache = new BuildPageCacheData();
            $buildPageCache->cargo_id = $this->id;

            $calcJob->addJob($buildPageCache);

            Yii::$app->gearman->getDispatcher()->background($calcJob->getJobName(), $calcJob);

            //Необходимо добавить город для выбора пользователя
            Yii::$app->gearman->getDispatcher()->background("addFastCity", [
                'cargo_id' => $this->id
            ]);

            //для нахождения попутных грузов
            Yii::$app->gearman->getDispatcher()->background("CargoRoute", [
                'cargo_id' => $this->id
            ]);
        }

        //При добавлении нового груза требуется уведомить перевозчиков
        if($insert){
            //Необходимо добавить город для выбора пользователя
            Yii::$app->gearman->getDispatcher()->background("addFastCity", [
                'cargo_id' => $this->id
            ]);

            //для нахождения попутных грузов
            Yii::$app->gearman->getDispatcher()->background("CargoRoute", [
                'cargo_id' => $this->id
            ]);

            //Job расчета растояния
            $calcJob = new CalcDistanceData();
            $calcJob->behavior = CalcDistance::BEHAVIOR_OBJECT;
            $calcJob->objectClass = self::class;
            $calcJob->objectId = $this->id;

            //Job определения категорий
            $jobAutoCategory = new AutoCategoryData();
            $jobAutoCategory->cargo_id = $this->id;

            if ($insert && $this->auto_category) {
                $saveCategories = false;
            } else {
                $saveCategories = true;
            }

            $jobAutoCategory->saveCategories = $saveCategories;

            //Job рассылки уведомлений
            $jobNotify = new NotifyCarrierData();
            $jobNotify->cargo_id = $this->id;
            $jobNotify->booking_only = 1; // Уведомляем только перевозчиков, имеющих доступ к бронированию грузов

            $calcJob
                ->addJob($jobAutoCategory)
                ->addJob($jobNotify);

            Yii::$app->gearman->getDispatcher()->background($calcJob->getJobName(), $calcJob, Dispatcher::HIGH);
        } else{
            //если заданы категории и ранее их не было,
            //то считаем что их изменили вручную, поэтому отправляем уведомление
            if(isset($this->_categoriesToSave) && !$this->isSetCategory){
                //Job рассылки уведомлений
                $jobNotify = new NotifyCarrierData();
                $jobNotify->cargo_id = $this->id;
                $jobNotify->booking_only = 1;

                Yii::$app->gearman->getDispatcher()->background($jobNotify->getJobName(), $jobNotify, Dispatcher::HIGH);
            }
        }

        //очищаем кэш
        LocationCacheHelper::invalidateTag($this, Yii::$app->cache);
        TagDependency::invalidate(Yii::$app->cache, [
            'cargoSearchCache'
        ]);

        //если новый груз или изменился город или категория
        if($insert || isset($changedAttributes['city_from']) || isset($changedAttributes['city_to'])
            || isset($this->_categoriesToSave) || isset($changedAttributes['status'])){

            if(!isset($changedAttributes['status']))
                $this->generateTags();

            // Строим кэш страниц для груза
            //==== !!!! buildPageCache должен быть после инвалидации кэша !!!! ======
            $this->buildPageCache();
        }

        //если изменилось описание
        if (isset($changedAttributes['description']) ){
            //если новый груз с автокатегорией,
            //то не нужно сохранять категории tomita
            if ($insert && $this->auto_category) {
                $saveCategories = false;
            } else {
                $saveCategories = true;
            }

            $this->makeAutoCategory($saveCategories);
        }

        if ($insert || isset($changedAttributes['description']) ){
            $jobCargoName = new CargoNameData();
            $jobCargoName->cargo_id = $this->id;

            $buildPageCache = new BuildPageCacheData();
            $buildPageCache->cargo_id = $this->id;

            $jobCargoName->addJob($buildPageCache);

            Yii::$app->gearman->getDispatcher()->background($jobCargoName->getJobName(), $jobCargoName);
        }

        self::updateElk($this->id);

        parent::afterSave($insert, $changedAttributes);
    }

    public function generateTags(){
        //Формирование тегов груза
        // Теги генерятся по другому алгоритму
        /*Yii::$app->gearman->getDispatcher()->background("UpdateCargoTags", [
            'cargo_id' => $this->id
        ]);*/
    }

    /**
     * Строим кэш страниц для груза
     */
    public function buildPageCache(){
        $buildPageCache = new BuildPageCacheData();
        $buildPageCache->cargo_id = $this->id;

        Yii::$app->gearman->getDispatcher()->background($buildPageCache->getJobName(), $buildPageCache);
    }

    /**
     * @param bool $saveCategories
     */
    public function makeAutoCategory($saveCategories = false){
        //Job определения категорий
        $jobAutoCategory = new AutoCategoryData();
        $jobAutoCategory->cargo_id = $this->id;
        $jobAutoCategory->saveCategories = $saveCategories;

        Yii::$app->gearman->getDispatcher()->background($jobAutoCategory->getJobName(), $jobAutoCategory);
    }

    /**
     * @param PageTemplates|null $pageTpl
     * @return string
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function title(PageTemplates $pageTpl = null){
        $cargo_title = '';
        if(isset($pageTpl)){
            $pageTpl = TemplateHelper::fillTemplate($pageTpl, [
                'cargo_name' =>  $this->name?? 'груз',
                'cargo_name_rod' => $this->name_rod ?? 'груза'
            ], ['cargo_title']);

            $cargo_title = $pageTpl->cargo_title;
        }

        if( trim($cargo_title) == '' ){
            $category = isset($this->cargoCategory) ? $this->cargoCategory->category : '';
            $cargo_title = $category;
        }

        return $cargo_title;
    }

    public function getAutoCategories()
    {
        return $this->hasMany(CargoCategory::class, ['id' => 'category_id'])
            ->viaTable('cargo_auto_category_assn', ['cargo_id' => 'id']);
    }

    public function getAutoCategoryIds()
    {
        return array_map(function ($model){
            /** @var CargoCategory $model */
            return $model->id;
        }, $this->autoCategories);
    }

    public function setAutoCategories($cats, $saveCategories = false)
    {
        $this->unlinkAll('autoCategories', true);

        $categories = CargoCategory::findAll(['id' => $cats]);

        $mainCategoryId = 0;
        $mainCategoryChild = false;
        foreach ($categories as $cat) {
            $this->link('autoCategories', $cat);

            //если дочерняя категория не определена
            if( !$mainCategoryChild ){
                $mainCategoryId = $cat->id;
                $mainCategoryChild = !$cat->root;
            }
        }

        self::updateAll([
                'tomita_category' => $mainCategoryId
        ], ['id'=>$this->id]);

        if( $saveCategories ) {
            $this->categories = [
                'list' => $cats,
                'main' => $mainCategoryId
            ];

            $this->save();
        }
    }

    /**
     * Получаем Id предыдущего груза в указанном городе
     * @param $cityId - Ид города
     */
    public function getPrev(City $city=null){
        $query = self::find()
            ->with(['cityFrom'])
            ->andWhere(['AND',
                ['not in', 'status', [self::STATUS_BANNED]],
                ['>', 'id', $this->id]
            ]);

        if($city)
            $query->andWhere(['city_from' => $city]);

        return $query->limit(1)->one();
    }

    /**
     * Получаем Id следующего груза в указанном городе
     * @param $cityId - Ид города
     */
    public function getNext(City $city=null){
        $query = self::find()
            ->with(['cityFrom'])
            ->andWhere(['AND',
                ['not in', 'status', [self::STATUS_BANNED]],
                ['<', 'id', $this->id]
            ]);

        if($city)
            $query->andWhere(['city_from' => $city]);

        return $query->orderBy(['id' => SORT_DESC])->limit(1)->one();
    }

    /**
     * Обновленеи данных для kibana
     * @param $cargo_id
     */
    public static function updateElk($cargo_id){
        Yii::$app->gearman->getDispatcher()->background('ElkLog', [
            'cargo_id' => $cargo_id
        ]);
    }
}
