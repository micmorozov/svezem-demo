<?php

namespace common\models;

use common\behaviors\UploadImageBehavior;
use common\helpers\LocationCacheHelper;
use common\helpers\TemplateHelper;
use common\helpers\TransportCargoTrait;
use common\SphinxModels\SphinxTransportCommon;
use common\SphinxModels\SphinxTransportRealTime;
use console\jobs\CalcDistance;
use console\jobs\jobData\AutoCategoryData;
use console\jobs\jobData\BuildPageCacheData;
use Redis;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;
use Yii;
use yii\base\InvalidConfigException;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\caching\TagDependency;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Exception;
use yii\di\NotInstantiableException;
use yii\helpers\ArrayHelper;
use yii\helpers\StringHelper;
use yii\helpers\Url;
use yii\sphinx\QueryBuilder;

/**
 * This is the model class for table "transport".
 *
 * @property int $id
 * @property int $created_by
 * @property int $updated_by
 * @property int $profile_id
 * @property string $price_from
 * @property int $payment_estimate
 * @property string $description
 * @property string $highlights Подсветка текста
 * @property int $city_from Город отправки
 * @property int $city_to Город доставки
 * @property int $region_from Регион отправки
 * @property int $region_to Регион доставки
 * @property int $status
 * @property int $created_at
 * @property int $updated_at
 * @property string $image Путь к картике
 * @property int $distance Расстояние (м)
 * @property int $duration Время в пути (c)
 * @property int $top Время окончания услуги TOP
 * @property int $colored Время окончания закраски
 * @property int $show_main_page Время окончания показа на главной странице
 * @property int $top_payed Время оплаты TOP
 * @property int $colored_payed Время оплаты colored
 * @property int $show_main_page_payed Время оплаты show_main_page
 * @property int $recommendation Время окончания рекомендации
 * @property int $recommendation_payed Время оплаты recommendation
 *
 * @property City $cityFrom
 * @property City $cityTo
 * @property User $createdBy
 * @property Profile $profile
 * @property Region $regionFrom
 * @property Region $regionTo
 * @property User $updatedBy
 * @property TransportCargoCategoryAssn[] $fullCargoCategories
 * @property CargoCategory[] $categories
 * @property TransportImage[] $transportImages
 *
 * @property array $estimateLabel
 * @property CargoCategory[] $cargoCategories
 * @property CargoCategory $transportType
 * @property CargoCategory[] $loadMethods
 * @property array[] $cargoCategoryIds
 * @property array $loadMethodIds
 * @property int $transportTypeId
 * @property string $url
 *
 * @property int $topProgress
 * @property int $mainPageProgress
 * @property int $coloredProgress
 * @property int $recommendationProgress
 * @property string $direction
 * @property CargoCategory[] $autoCategories
 * @property array $autoCategoryIds
 */
class Transport extends ActiveRecord implements FromToLocationInterface
{
    use TransportCargoTrait;

    const STATUS_ACTIVE = 0;
    const STATUS_DELETED = 10;

    const TR_POSITION = 'transportPosition';

    public $description_short;

    //требуется удалить файл
    //задается в форме редактирования транспорта
    public $deleteImage = 0;

    private $_transportTypeId;
    private $_loadMethodIds;
    private $_cargoCategoryIds;

    //флаг изменения категорий
    private $_isCategoryChanged = false;

    //создавать ли правило при создании транспорта
    //Поведение может быть изменено для импорта
    public $create_rule = true;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'transport';
    }

    public function behaviors()
    {
        return [
            'TimestampBehavior' => [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    self::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    self::EVENT_BEFORE_UPDATE => 'updated_at',
                ]
            ],
            'BlameableBehavior' => [
                'class' => BlameableBehavior::class,
                //чтобы при изменении модели через консоль
                //значение updated_by не становилось null
                'updatedByAttribute' => Yii::$app instanceof yii\console\Application
                    ? null
                    : 'updated_by'
            ],
            'images' => [
                'class' => UploadImageBehavior::class,
                'attribute' => 'image',
                'scenarios' => [self::SCENARIO_DEFAULT],
                'path' => '@frontend/web/uploads/transport/{id}',
                'url' => '@frontend/web/uploads/transport/{id}',
                'thumbPath' => '@frontend/web/uploads/transport/{id}/thumbnails',
                'thumbUrl' => 'uploads/transport/{id}/thumbnails',
                'thumbs' => [
                    'preview_86' => ['width' => 86, 'quality' => 15]
                ]
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_DELETED]],
            [['price_from'], 'number'],
            [['description'], 'string'],
            [
                [
                    'city_from',
                    'city_to',
                    'transportTypeId',
                    'description',
                    'loadMethodIds',
                    'cargoCategoryIds',
                    'price_from',
                    'payment_estimate'
                ],
                'required'
            ],
            [
                ['city_from'],
                'exist',
                'skipOnError' => true,
                'targetClass' => City::class,
                'targetAttribute' => ['city_from' => 'id']
            ],
            [
                ['city_to'],
                'exist',
                'skipOnError' => true,
                'targetClass' => City::class,
                'targetAttribute' => ['city_to' => 'id']
            ],
            [
                ['created_by'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['created_by' => 'id']
            ],
            [
                ['profile_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Profile::class,
                'targetAttribute' => ['profile_id' => 'id']
            ],
            [
                ['region_from'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Region::class,
                'targetAttribute' => ['region_from' => 'id']
            ],
            [
                ['region_to'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Region::class,
                'targetAttribute' => ['region_to' => 'id']
            ],
            [
                ['updated_by'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['updated_by' => 'id']
            ],
            [['deleteImage'], 'safe'],
            [['image'], 'file', 'extensions' => 'png, jpg, jpeg'],
            [['highlights'], 'string', 'max' => 256],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'created_by' => 'Владелец',
            'updated_by' => 'Кем редактировано',
            'profile_id' => 'Профиль пользователя',
            'price_from' => 'Стоимость перевозки',
            'payment_estimate' => 'Стоимость перевозки за',
            'description' => 'Описание',
            'highlights' => 'Подсветка текста',
            'city_from' => 'Город отправки',
            'city_to' => 'Город доставки',
            'region_from' => 'Регион отправки',
            'region_to' => 'Регион доставки',
            'status' => 'Статус',
            'created_at' => 'Время создания',
            'updated_at' => 'Время редактирования',
            'image' => 'Путь к картике',
            'distance' => 'Расстояние (м)',
            'duration' => 'Время в пути (c)',
            'top' => 'Время окончания услуги TOP',
            'colored' => 'Время окончания закраски',
            'show_main_page' => 'Время окончания показа на главной странице',
            'top_payed' => 'Время оплаты TOP',
            'colored_payed' => 'Время оплаты colored',
            'show_main_page_payed' => 'Время оплаты show_main_page',
            'transportTypeId' => 'Вид автотранспорта',
            'loadMethodIds' => 'Способ загрузки',
            'cargoCategoryIds' => 'Категория перевозимых грузов',
            'autoCategories' => 'Авто категории',
            'recommendation' => 'Время окончания рекомендации',
            'recommendation_payed' => 'Время оплаты recommendation',
        ];
    }

    public function afterFind()
    {
        $this->description_short = StringHelper::truncate($this->description, 200, '...');

        parent::afterFind();
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
    public function getCreatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /**
     * @return ActiveQuery
     */
    public function getProfile()
    {
        return $this->hasOne(Profile::class, ['id' => 'profile_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getRegionFrom()
    {
        return $this->hasOne(Region::class, ['id' => 'region_from']);
    }

    /**
     * @return ActiveQuery
     */
    public function getRegionTo()
    {
        return $this->hasOne(Region::class, ['id' => 'region_to']);
    }

    /**
     * @return ActiveQuery
     */
    public function getUpdatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'updated_by']);
    }

    /**
     * Таблица CargoCategory хранит виды перевозок, тип транспорта и пр.
     * Запросом $this->fullCargoCategories мы получаем все соответствия для данной модели,
     * а подвиды получаем соответсвующими свойствами (определенными как геттеры)
     * @return ActiveQuery
     */
    public function getFullCargoCategories()
    {
        return $this->hasMany(CargoCategory::class, ['id' => 'category_id'])
            ->viaTable('transport_cargo_category_assn', ['transport_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getCategories()
    {
        return $this->hasMany(CargoCategory::class, ['id' => 'category_id'])->viaTable('transport_cargo_category_assn',
            ['transport_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getTransportImages()
    {
        return $this->hasMany(TransportImage::class, ['transport_id' => 'id']);
    }

    /**
     * @param null $prefix
     * @param bool $absolute
     * @return string
     */
    public function getImagePath($prefix = null, $absolute = false)
    {
        $path = "/uploads/transport/{$this->id}";

        if ( !$this->image) {
            $resultPath = "/img/icons/default_transport_icon.svg";
        } else {
            if ($prefix) {
                $resultPath = $path."/thumbnails/$prefix-{$this->image}";
            } else {
                $resultPath = $path."/{$this->image}";
            }
        }

        if ($absolute) {
            $resultPath = "https://".Yii::getAlias('@assetsDomain').$resultPath;
        }

        return $resultPath;
    }

    /**
     * @return array
     */
    public static function getEstimateLabels()
    {
        $categories = TransportEstimate::find()->cache(2)->all();
        return ArrayHelper::map($categories, 'id', 'name');
    }

    /**
     * @param bool|false $colored
     * @return string
     */
    public function getEstimateLabel()
    {
        $labels = static::getEstimateLabels();
        return isset($labels[$this->payment_estimate]) ? $labels[$this->payment_estimate] : '';
    }

    /**
     * @return mixed|null
     */
    public function getTransportType()
    {
        $res = array_filter($this->fullCargoCategories, function ($var){
            /* @var CargoCategory $var */
            return $var->transport_type == 1;
        });

        if ( !empty($res)) {
            return current($res);
        } else {
            return null;
        }
    }

    /**
     * @return int
     */
    public function getTransportTypeId()
    {
        if ( !$this->_transportTypeId) {
            if ($model = $this->transportType) {
                $this->_transportTypeId = $model->id;
            }
        }
        return $this->_transportTypeId;
    }

    /**
     * @param $v
     */
    public function setTransportTypeId($v)
    {
        $this->_transportTypeId = $v;

        $this->_isCategoryChanged = true;
    }

    /**
     * @return ActiveQuery
     */
    public function getLoadMethods()
    {
        $res = array_filter($this->fullCargoCategories, function ($model){
            return $model->load_type == 1;
        });

        //для переиндексации
        return array_values($res);
    }

    /**
     * @return array
     */
    public function getLoadMethodIds()
    {
        if ($this->_loadMethodIds === null) {
            $this->_loadMethodIds = ArrayHelper::getColumn($this->loadMethods, 'id');
        }
        return $this->_loadMethodIds;
    }

    /**
     * @param $value array
     */
    public function setLoadMethodIds($value)
    {
        $this->_loadMethodIds = $value;

        $this->_isCategoryChanged = true;
    }

    /**
     * @return array
     */
    public function getCargoCategoryIds()
    {
        if ($this->_cargoCategoryIds === null) {
            $this->_cargoCategoryIds = ArrayHelper::getColumn($this->cargoCategories, 'id');
        }
        return $this->_cargoCategoryIds;
    }

    /**
     * @param $value array
     */
    public function setCargoCategoryIds($value)
    {
        $this->_cargoCategoryIds = $value;

        $this->_isCategoryChanged = true;
    }

    /**
     * Модели видов перевозок
     * @return array
     */
    public function getCargoCategories()
    {
        $res = array_filter($this->fullCargoCategories, function ($model){
            return $model->show_moder_tr_tk == 1;
        });

        //для переиндексации
        return array_values($res);
    }

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if ( !parent::beforeSave($insert)) {
            return false;
        }

        //удаление картинок
        // методы getUploadPath и getThumbUploadPath из UploadImageBehavior
        if ($this->deleteImage) {
            $path = $this->getUploadPath('image', true);
            $this->setAttribute('image', null);

            if (is_file($path)) {
                unlink($path);
            }

            $profiles = array_keys($this->thumbs);
            foreach ($profiles as $profile) {
                $path = $this->getThumbUploadPath('image', $profile, true);
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }

        $oldAttributes = $this->getOldAttributes();

        $oldCityFrom = $oldAttributes['city_from']??null;
        $oldCityTo = $oldAttributes['city_to']??null;

        //если новая модель или изменился город
        if ($this->isNewRecord || $oldCityFrom != $this->city_from) {
            $city = City::findOne($this->city_from);
            $this->region_from = $city->region_id;
        }
        if ($this->isNewRecord || $oldCityTo != $this->city_to) {
            $city = City::findOne($this->city_to);
            $this->region_to = $city->region_id;
        }

        return true;
    }

    /**
     * @param $column
     * @return float|int
     */
    private function progress($column)
    {
        $time = time();
        if ($this->{$column} < $time) {
            return 0;
        }

        return round(($this->{$column} - $time)/($this->{$column} - $this->{$column.'_payed'})*100);
    }

    /**
     * @return float|int
     */
    public function getTopProgress()
    {
        return $this->progress('top');
    }

    /**
     * @return float|int
     */
    public function getMainPageProgress()
    {
        return $this->progress('show_main_page');
    }

    /**
     * @return float|int
     */
    public function getColoredProgress()
    {
        return $this->progress('colored');
    }

    public function getRecommendationProgress()
    {
        return $this->progress('recommendation');
    }

    public function generateTags()
    {
        //Формирование тегов груза
        // Теги формируются через крон
       /* Yii::$app->gearman->getDispatcher()->background("UpdateTransporterTags", [
            'profile_id' => $this->profile_id
        ]);*/
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return Url::toRoute([
            '/transporter/default/view2',
            'id' => $this->profile_id,
            'slug' => $this->profile->slug,
        ]);
    }

    /**
     * @param Transport $tr
     * @param PageTemplates|null $tpl
     * @return string
     * @throws LoaderError
     * @throws SyntaxError
     */
    public static function titleItemByTemplate(Transport $tr, PageTemplates $tpl = null, CargoCategory $category = null):string
    {
        //Если передан шаблон, то заполняем поле транспорта

        $contact_person = $tr->profile->contact_person;
        $contact_person = $contact_person??'';

        if (isset($tpl) && trim($tpl->tr_title) != '') {
            $tpl = TemplateHelper::fillTemplate($tpl, [
                'name' => $contact_person,
                'category' => $category ? $category->category : '',
                'category_rod' => $category ? $category->category_rod : '',
            ], ['tr_title']);

            $tr_title = $tpl->tr_title;
        } else {
            $tr_title = $contact_person;
        }

        return $tr_title;
    }

    /**
     * @param bool $colored
     * @return array
     */
    public static function getStatusLabels($colored = false)
    {
        return [
            self::STATUS_ACTIVE => ($colored ? '<span style="color: #3ab845">Активен</span>' : 'Активен'),
            self::STATUS_DELETED => ($colored ? '<span style="color: #ac4137">Удалён</span>' : 'Удалён'),
        ];
    }

    /**
     * @param bool|false $colored
     * @return string
     */
    public function getStatusLabel($colored = false)
    {
        $labels = static::getStatusLabels($colored);
        return isset($labels[$this->status]) ? $labels[$this->status] : 'Неизвестный статус';
    }

    public function afterSave($insert, $changedAttributes)
    {
        //если были указаны категории
        if ($this->_isCategoryChanged) {
            $category = array_merge([],
                $this->loadMethodIds,
                $this->cargoCategoryIds,
                [$this->transportTypeId]
            );

            $categories = CargoCategory::findAll(['id' => $category]);

            //Проверяем есть ли среди категорий "Аренда"
            $rentCategoryFilter = array_filter($categories, function ($m){
                /** @var CargoCategory $m */
                return $m->rent;
            });

            //Если "аренды" нет, то дополняем категории
            if (empty($rentCategoryFilter)) {
                //необходимо добавлять доп категории при создании/редактировании
                $categoryTrType = CargoCategory::find()->where(['transportation_type' => 1])->one();
                $categoryPrType = CargoCategory::find()->where(['private_transportation' => 1])->one();

                $categories[] = $categoryTrType;
                $categories[] = $categoryPrType;
            }

            $this->unlinkAll('fullCargoCategories', true);
            foreach ($categories as $cat) {
                $this->link('fullCargoCategories', $cat);
            }
        }

        //очищаем кэш
        LocationCacheHelper::invalidateTag($this, Yii::$app->cache);
        TagDependency::invalidate(Yii::$app->cache, [
            'transportSearchCache'
        ]);

        //Изменились города
        if ($insert || isset($changedAttributes['city_from']) || isset($changedAttributes['city_to']) || isset($changedAttributes['status'])) {
            if ( !isset($changedAttributes['status'])) {
                Yii::$app->gearman->getDispatcher()->background("addFastCity", [
                    'transport_id' => $this->id
                ]);

                Yii::$app->gearman->getDispatcher()->background("CalcDistance", [
                    'behavoir' => CalcDistance::BEHAVIOR_OBJECT,
                    'objectClass' => self::class,
                    'object_id' => $this->id
                ]);

                self::calculatePosition($this->id);
            }

            //==== !!!! buildPageCache должен быть после инвалидации кэша !!!! ======
            $this->buildPageCache();
        }

        $this->generateTags();

        //добавляем новую модель в Sphinx
        self::sphinxUpdate($this);

        //если новый транспорт или изменилось описание
        if ($insert || isset($changedAttributes['description'])) {
            $this->makeAutoCategory();
        }

        self::updateElk($this->id);

        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * Строим кэш страниц для транспорта
     */
    public function buildPageCache()
    {
        $buildPage = new BuildPageCacheData();
        $buildPage->transport_id = $this->id;

        Yii::$app->gearman->getDispatcher()->background($buildPage->getJobName(), $buildPage);
    }

    public function makeAutoCategory()
    {
        //Job определения категорий
        $jobAutoCategory = new AutoCategoryData();
        $jobAutoCategory->transport_id = $this->id;

        Yii::$app->gearman->getDispatcher()->background($jobAutoCategory->getJobName(), $jobAutoCategory);
    }

    /**
     * @param Transport $model
     * @throws Exception
     */
    static public function sphinxUpdate(Transport $model)
    {
        $trCommon = SphinxTransportCommon::findOne($model->id);

        //если модель уже проиндексирована, то меняем данные
        //НО! Невозможно поменять description
        if ($trCommon) {
            $trCommon->attributes = [
                $trCommon->city_from = $model->city_from,
                $trCommon->city_to = $model->city_to,
                $trCommon->region_from = (int)$model->region_from,
                $trCommon->region_to = (int)$model->region_to,
                $trCommon->created_by = $model->created_by,
                $trCommon->status = $model->status,
                $trCommon->top = $model->top ? $model->top : 0,
                $trCommon->show_main_page = $model->show_main_page ? $model->show_main_page : 0,
                $trCommon->recommendation = $model->recommendation ? $model->recommendation : 0
            ];

            $trCommon->save();
        }

        $db = SphinxTransportRealTime::getDb();
        $params = [];
        $sql = (new QueryBuilder($db))->replace(SphinxTransportRealTime::indexName(), [
            'id' => $model->id,
            'description' => $model->description,
            'city_from' => $model->city_from,
            'city_to' => $model->city_to,
            'region_from' => (int)$model->region_from,
            'region_to' => (int)$model->region_to,
            'created_by' => $model->created_by,
            'status' => $model->status,
            'top' => $model->top ? $model->top : 0,
            'show_main_page' => $model->show_main_page ? $model->show_main_page : 0,
            'recommendation' => $model->recommendation ? $model->recommendation : 0
        ], $params);

        $db->createCommand($sql, $params)->execute();
    }

    public function positionKeyFrom()
    {
        return self::TR_POSITION.'_'.$this->id.":from".$this->city_from;
    }

    public function positionKeyFromTo()
    {
        return self::TR_POSITION.'_'.$this->id.":from".$this->city_from."_to".$this->city_to;
    }

    public function positionKeyCat()
    {
        return self::TR_POSITION.'_'.$this->id.':cat';
    }

    public function positionKeyMainCity()
    {
        return self::TR_POSITION.'_'.$this->id.":main";
    }

    public function positionKeyRecommend()
    {
        return self::TR_POSITION.'_'.$this->id.":recommend";
    }

    public function existPostion()
    {
        /** @var Redis $redis */
        $redis = Yii::$app->redisTemp;
        return $redis->exists($this->positionKeyFrom());
    }

    /**
     * @param $id
     */
    static public function calculatePosition($id)
    {
        Yii::$app->gearman->getDispatcher()->background("transportPosition", [
            'transport_id' => $id
        ]);
    }

    public function getAutoCategories()
    {
        return $this->hasMany(CargoCategory::class, ['id' => 'category_id'])
            ->viaTable('transport_auto_category_assn', ['transport_id' => 'id']);
    }

    public function getAutoCategoryIds()
    {
        return array_map(function ($model){
            /** @var CargoCategory $model */
            return $model->id;
        }, $this->autoCategories);
    }

    public function setAutoCategories($cats)
    {
        $this->unlinkAll('autoCategories', true);

        $categories = CargoCategory::findAll(['id' => $cats]);

        foreach ($categories as $cat) {
            $this->link('autoCategories', $cat);
        }
    }

    /**
     * Выделение текса по определившимся категориям
     * @param $catIds
     * @param int $wordBeside - кол-во слова слева-справа
     * @param string $prefix
     * @return string
     */
    public function descriptionHighlights($catIds, $wordBeside = 3, $prefix = '...')
    {
        if ( !is_array($catIds)) {
            $catIds = [$catIds];
        }

        $highlights = json_decode($this->highlights, 1);

        //Выбираем категории
        $highlights = array_filter($highlights, function ($key) use ($catIds){
            return in_array($key, $catIds);
        }, ARRAY_FILTER_USE_KEY);

        //Сортируем в порядке появления в тексте
        usort($highlights, function ($a, $b){
            return $a['start'] <=> $b['start'];
        });

        //Представляем текст в виде массива
        $words = preg_split('/(\s+)/u', $this->description, null, PREG_SPLIT_DELIM_CAPTURE);

        //Преобразуем к виду
        //  [
        //      [
        //      'word' => <слово>,
        //      'start' => <позиция в тексте>
        //      ]
        //  ]
        $sentence = [];
        $start = 0;
        foreach ($words as $word) {
            $sentence[] = [
                'word' => $word,
                'start' => $start
            ];

            $start += mb_strlen($word);
        }

        //Получаем отрезки с найденными категориями
        // добавляя слева и справа по $wordBeside слов
        $segments = [];
        foreach ($highlights as $highlight) {
            //Получаем часть из массива предложения
            //которая относится к данной категории
            $part = array_filter($sentence, function ($word) use ($highlight){
                return $word['start'] >= $highlight['start'] && $word['start'] < $highlight['start'] + $highlight['len'];
            });

            $first = array_key_first($part) - $wordBeside*2;
            $last = array_key_last($part) + $wordBeside*2;

            $first = $first >= 0 ? $first : 0;

            $segments[] = [
                $first,
                $last
            ];
        }

        //Т.к. периоды могут пересекаться,
        //то перестраиваем
        $periods = [];
        $lastIndex = null;
        foreach ($segments as $item) {
            if ($lastIndex === null) {
                $lastIndex = 0;
                $periods[$lastIndex] = $item;
                continue;
            }

            if ($item[0] <= $periods[$lastIndex][1]) {
                $periods[$lastIndex][1] = $item[1];
            } else {
                $lastIndex++;
                $periods[$lastIndex] = $item;
            }
        }

        //Строим карту слова добавляя $prefix между периодами
        $textMap = [];
        foreach ($periods as $period) {
            $textPeriod = array_slice($words, $period[0], $period[1] - $period[0] + 1);
            $textMap = array_merge($textMap, $textPeriod);

            if ($period[1] < count($words)) {
                $textMap[] = $prefix;
            }
        }

        $firstPeriod = array_shift($periods);

        if ($firstPeriod[0] > 0) {
            array_unshift($textMap, $prefix);
        }

        return implode('', $textMap);
    }

    /**
     * Обновленеи данных для kibana
     * @param $transport_id
     */
    public static function updateElk($transport_id){
        Yii::$app->gearman->getDispatcher()->background('ElkLog', [
            'transport_id' => $transport_id
        ]);
    }
}
