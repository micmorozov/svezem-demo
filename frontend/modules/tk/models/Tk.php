<?php

namespace frontend\modules\tk\models;

use common\behaviors\SlugBehavior;
use common\behaviors\UploadImageBehavior;
use common\helpers\PhoneHelpers;
use common\helpers\TemplateHelper;
use common\helpers\Utils;
use common\models\FastCity;
use common\models\LocationInterface;
use common\models\PageTemplates;
use common\models\Region;
use console\jobs\jobData\BuildPageCacheData;
use simialbi\yii2\schemaorg\models\AutomotiveBusiness;
use simialbi\yii2\schemaorg\models\PostalAddress;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;
use Yii;
use common\models\CargoCategory;
use common\models\City;
use common\models\TkReviews;
use frontend\behaviors\RatingBehavior;
use yii\caching\TagDependency;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use common\behaviors\JunctionBehavior;
use yii\helpers\Url;

/**
 * This is the model class for table "tk".
 *
 * @property integer $id
 * @property integer $created_by
 * @property integer $updated_by
 * @property string $name
 * @property string $email
 * @property string $phone
 * @property string $about
 * @property string $code
 * @property integer $type
 * @property integer $status
 * @property string $icon
 * @property int|null $phone_view Просмотры номера
 * @property string $order_link
 * @property string $describe
 * @property CargoCategory[] $categories
 * @property array $categoriesIds
 * @property string $slug
 * @property string $address
 * @property string $url
 * @property integer $inn
 * @property integer $kpp
 * @property integer $transportation_types
 * @property integer $cost_kg_per_km
 * @property string $cargo_types
 * @property integer $restrict_weight
 * @property integer $restrict_volume
 * @property integer $restrict_volume_param
 * @property integer $expedition
 * @property integer $insurance
 * @property integer $packing
 * @property integer $storage
 *
 * @property array $cityIds
 * @property string $cityAddress
 * @property string $detailAddress
 * @property TkDetails[] $details
 * @property TkReviews[] $reviews
 *
 * @property integer $rating
 * @property integer $rating_sum
 * @property integer $rating_voices
 * @property string $statusLabel
 *
 * @property AutomotiveBusiness $structured
 */
class Tk extends ActiveRecord
{
    const STATUS_ACTIVE = 1;
    const STATUS_DISABLED = 0;

    /**
     * @var array
     */
    private $_cityIds;

    private $_categoriesIds;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tk';
    }

    public function behaviors()
    {
        return [
            RatingBehavior::class,
            [
                //Сохранение данных в промежуточные таблицы
                'class' => JunctionBehavior::class,
                'association' => [
                    ['categoriesIds', CargoCategory::class, 'categories']
                ]
            ],
            'images' => [
                'class' => UploadImageBehavior::class,
                'attribute' => 'icon',
                'scenarios' => [self::SCENARIO_DEFAULT],
                'path' => '@frontend/web/uploads/tk/{id}',
                'url' => '@frontend/web/uploads/tk/{id}',
                'thumbPath' => '@frontend/web/uploads/tk/{id}/thumbnails',
                'thumbs' => [
                    'preview_198' => ['width' => 198, 'quality' => 15],
                    'preview_86' => ['width' => 86, 'quality' => 15]
                ]
            ],
            [
                'class' => SlugBehavior::class,
                'value' => 'name'
            ]
        ];
    }

    public function rules(){
        return [
            ['name', 'required'],
            ['name', 'nameUnique', 'params'=>['message'=>'ТК "{value}" уже существует']],
            ['status', 'in', 'range'=>array_keys(self::statusLabels())],
            [['name', 'url'], 'string', 'max' => 255],
            ['url', 'url', 'enableIDN'=>true],
            [['code'], 'string', 'max' => 32],
            [['email'], 'string', 'max' => 128],
            ['email', 'email'],
            ['phone', 'common\validators\PhoneValidator'],
            [['phone','email'], 'unique', 'message'=>'{attribute} "{value}" уже используется'],
            [['describe'], 'string', 'max' => 1024],
            [['address'], 'string', 'max' => 256],
            ['categoriesIds', 'exist', 'targetClass' => CargoCategory::class, 'targetAttribute' => 'id', 'allowArray' => true],
            ['icon', 'file']
        ];
    }

    /**
    * @inheritdoc
    */
    public function attributeLabels()
    {
        return [
            'id' => 'ИД',
            'name' => 'Наименование',
            'code' => 'Код',
            'status' => 'Статус',
            'email' => 'Email',
            'phone' => 'Телефон',
            'url' => 'УРЛ',
            'describe' => 'Описание РК',
            'address' => 'адрес',
            'icon' => 'Картинка',
            'rating' => 'рейтинг',
            'rating_sum' => 'сумма оценок',
            'rating_voices' => 'кол-во голосов',
            'categoriesIds' => 'Категории',
            'phone_view' => 'Просмотры номера',
        ];
    }

    public function nameUnique($attribute, $params){
        //Для проверки уникальности имени приводим к нижнему регистру
        // и убираем все пробелы
        $query = self::find()
            ->where(
                ["REPLACE(LOWER(`{$attribute}`), ' ', '')" => str_replace(' ', '', mb_strtolower($this->$attribute))]
            );

        //если модель редактируется, то исключаем свой ИД
        if( !$this->isNewRecord )
            $query->andWhere(['<>', 'id', $this->id]);

        if( $query->count() ){
            $message = isset($params['message'])?$params['message']:"{attribute} не уникален";
            $params = [
                'attribute' => $this->getAttributeLabel($attribute),
                'value' => $this->$attribute
            ];
            $message = Yii::$app->getI18n()->format($message, $params, Yii::$app->language);
            $this->addError($attribute, $message);
        }
    }

    static public function statusLabels(){
        return [
            self::STATUS_ACTIVE => 'активна',
            self::STATUS_DISABLED => 'неактивна'
        ];
    }

    public function getStatusLabel(){
        $labels = self::statusLabels();
        return isset($labels[$this->status])?$labels[$this->status]:'';
    }

    /**
     * @return ActiveQuery
     */
    public function getCities()
    {
        return $this->hasMany(City::class, ['id' => 'city_id'])
            ->viaTable('tk_city_assn', ['tk_id' => 'id']);
    }

    /**
     * @return array
     */
    public function getCityIds()
    {
        if ($this->_cityIds === null) {
            $this->_cityIds = ArrayHelper::getColumn($this->cities, 'id');
        }

        return $this->_cityIds;
    }

    /**
     * @param $value array
     */
    public function setCityIds($value) {
        $this->_cityIds = $value;
    }

    /**
     * @inheritdoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        //очищаем кэш поиска
        TagDependency::invalidate(Yii::$app->cache, 'tkSearchCache');

        //TODO удалить в будущем
        //для сохранения категории "автоперевозки"
        Utils::setTkTransportationType($this->id);

        //==== !!!! buildPageCache должен быть после инвалидации кэша !!!! ======
        $this->buildPageCache();
    }

    /**
     * Строим кэш страниц для груза
     */
    public function buildPageCache(){
        $buildCache = new BuildPageCacheData();
        $buildCache->transport_id = $this->id;

        Yii::$app->gearman->getDispatcher()->background($buildCache->getJobName(), $buildCache);
    }

    /**
     * @return ActiveQuery
     */
    public function getDetails(){
        return $this->hasMany(TkDetails::class, ['tk_id' => 'id']);
    }

    /**
     * @return TkDetails
     */
    public function getDetailsByLocation(LocationInterface $location = null)
    {
        $relation = $this->getDetails();

        $cityid = null;
        if($location instanceof City)
            $cityid =  $location->getId();
        elseif($location instanceof Region)
            $cityid = $location->center;

        if($cityid)
            $relation->where(['cityid' => $cityid]);

        return $relation;
    }

    public function getPhones(LocationInterface $location = null): array
    {
        if(!$location)
            return [$this->phone];

        if($cityDetails = $this->getDetailsByLocation($location)->one())
            return $cityDetails->phone;

        return [];
    }

    public function getCityAddress(LocationInterface $location = null)
    {
        if(!$location) return $this->address;

        if($cityDetails = $this->getDetailsByLocation($location)->one()){
            return $cityDetails->address;
        }

        return null;
    }

    public function getEmails(LocationInterface $location = null): array
    {
        if(!$location)
            return [$this->email];

        if($cityDetails = $this->getDetailsByLocation($location)->one()){
            return $cityDetails->email;
        }

        return [];
    }

    public function getReviews(){
        return $this->hasMany(TkReviews::class, ['tk_id' => 'id']);
    }

    public function getCategories(){
        return $this->hasMany(CargoCategory::class, ['id' => 'category_id'])
            ->viaTable('tk_category_assn', ['tk_id' => 'id']);
    }

    public function getCategoriesIds(){
        if( !isset($this->_categoriesIds) ){
            $this->_categoriesIds = ArrayHelper::getColumn($this->categories, 'id');
        }
        return $this->_categoriesIds;
    }

    public function setCategoriesIds($v){
        $this->_categoriesIds = $v;
    }

    public function iconPath($prefix = '', $png = false, $absolute = false){
        $domain = $absolute ? 'https://'.Yii::getAlias('@assetsDomain') : '';

        if( !$prefix )
            $path =  "/uploads/tk/{$this->id}/{$this->icon}";
        else
            $path =  "/uploads/tk/{$this->id}/thumbnails/{$prefix}-{$this->icon}";

        if( $this->icon == '' ){
            if( $png ){
                return $domain."/img/icons/default_tk_icon.png";
            }
            else{
                return $domain."/img/icons/default_tk_icon.svg";
            }
        }

        return $domain.$path;
    }

    /**
     * Объект микроразметки
     * @return AutomotiveBusiness
     */
    public function getStructured(){
        $address = new PostalAddress();

        $address->streetAddress = $this->getCityAddress();

        $organization = new AutomotiveBusiness();
        $organization->name = $this->name;
        $organization->image = $this->iconPath('preview', false, true);
        $organization->description = $this->describe;

        $organization->address = $address;
        $phones = [];
        foreach($this->getPhones() as $phone){
            $phones[] = PhoneHelpers::formatter($phone);
        }
        $organization->telephone = implode(", ", $phones);

        return $organization;
    }

    /**
     * @param PageTemplates|null $pageTpl
     * @return string
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function title(PageTemplates $pageTpl = null){
        //Если передан шаблон, то заполняем поле ТК
        if( $pageTpl && $pageTpl->tk_title != '' ){
            $pageTpl = TemplateHelper::fillTemplate($pageTpl, [
                'name' => $this->name
            ], ['tk_title']);

            $tk_title = $pageTpl->tk_title;
        } else{
            $tk_title = $this->name;
        }

        return $tk_title;
    }

    public function getUrl(){
        return Url::toRoute([
            '/tk/default/view2',
            'id' => $this->id,
            'slug' => $this->slug
        ]);
    }

    public function getInternalUrl(){
        return Url::to([
            '/tk/default/view',
            'id' => $this->id
        ]);
    }

    /**
     * Получаем Id следующего груза в указанном городе
     */
    public function getPrev(){
        $query = self::find()
            ->andWhere(['AND',
                ['in', 'status', [self::STATUS_ACTIVE]],
                ['>', 'id', $this->id]
            ]);

        return $query->limit(1)->one();
    }

    /**
     * Получаем Id предыдущего груза в указанном городе
     */
    public function getNext(){
        $query = self::find()
            ->andWhere(['AND',
                ['in', 'status', [self::STATUS_ACTIVE]],
                ['<', 'id', $this->id]
            ]);

        return $query->orderBy(['id' => SORT_DESC])->limit(1)->one();
    }

    /**
     * Обновленеи данных для kibana
     * @param $cargo_id
     */
    public static function updateElk($tk_id){
        Yii::$app->gearman->getDispatcher()->background('ElkLog', [
            'tk_id' => $tk_id
        ]);
    }
}
