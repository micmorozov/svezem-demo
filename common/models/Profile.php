<?php

namespace common\models;

use common\behaviors\SlugBehavior;
use common\helpers\SlugHelper;
use common\helpers\Utils;
use common\validators\ContactPersonValidator;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\UploadedFile;
use common\helpers\ImageHelper;
use simialbi\yii2\schemaorg\models\PostalAddress;
use simialbi\yii2\schemaorg\models\Place;
use simialbi\yii2\schemaorg\models\Person;
use common\helpers\PhoneHelpers;

/**
 * This is the model class for table "profile".
 *
 * @property integer                      $id
 * @property integer                      $created_by
 * @property integer                      $updated_by
 * @property int                          $city_id Город
 * @property string                       $name
 * @property string                       $contact_person
 * @property string                       $contact_phone
 * @property string                       $contact_email
 * @property string                       $photo
 * @property string                       $skype
 * @property string                       $icq
 * @property integer                      $type
 * @property integer                      $status
 * @property integer                      $reliability_rating
 * @property boolean                      $infinite_offers
 * @property integer                      $infinite_until
 * @property integer                      $free_offers
 * @property integer                      $paid_offers
 * @property integer                      $sms_amount
 * @property integer                      $created_at
 * @property integer                      $updated_at
 * @property int|null                     $phone_view Просмотры номера
 * @property string                       $slug
 * @property User                         $updatedBy
 * @property User                         $createdBy
 * @property ProfileTransporterTypeAssn[] $profileTransporterTypeAssns
 * @property City                         $city
 * @property Cargo[]                      $cargo
 * @property Transport[]                  $transport
 * @property Offer[]                      $offers
 * @property TransporterReviews[]         $transporterReviews
 *
 * @property string                       $image
 * @property string                       $imagePng
 * @property Person                       $structured
 * @property string                       $url
 */
class Profile extends ActiveRecord
{
    public $imageFile;

    const TYPE_SENDER = 10;
    const TYPE_TRANSPORTER_PRIVATE = 20;
    const TYPE_TRANSPORTER_JURIDICAL = 30;
    const TYPE_TRANSPORTER_IP = 40;
    const TYPE_TRANSPORTER_NOT_SPECIFIED = 100;

    const STATUS_ACTIVE = 10;
    const STATUS_PENDING = 20;

    public $deleteImage;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'profile';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
            'BlameableBehavior' => [
                'class' => BlameableBehavior::class
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'contact_person'], 'required'],
            [['name', 'contact_person'], ContactPersonValidator::class],

            [['name', 'contact_person', 'photo'], 'string', 'max' => 255, 'min' => 5],
            ['infinite_until', 'integer'],

            ['contact_email', 'trim'],
            ['contact_email', 'email'],
            ['contact_email', 'string', 'max' => 255],

            ['skype', 'trim'],
            ['skype', 'string', 'max' => 255],

            ['icq', 'trim'],
            ['icq', 'string', 'max' => 255],

            ['city_id', 'exist', 'targetClass' => City::class, 'targetAttribute' => 'id'],

            ['imageFile', 'file'],
            ['deleteImage', 'safe'],

            [
                'type',
                'in',
                'range' => [
                    self::TYPE_SENDER,
                    self::TYPE_TRANSPORTER_PRIVATE,
                    self::TYPE_TRANSPORTER_JURIDICAL,
                    self::TYPE_TRANSPORTER_IP,
                    self::TYPE_TRANSPORTER_NOT_SPECIFIED
                ]
            ],

            ['status', 'default', 'value' => self::STATUS_PENDING],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_PENDING]],

            ['reliability_rating', 'default', 'value' => 0],
            ['reliability_rating', 'integer'],

            ['free_offers', 'default', 'value' => Setting::getValueByCode('offer-limit')],
            ['free_offers', 'integer'],

            ['paid_offers', 'default', 'value' => 0],
            ['paid_offers', 'integer'],

            ['sms_amount', 'default', 'value' => 0],
            ['sms_amount', 'integer'],

            ['infinite_offers', 'default', 'value' => false],

            [['city_id'], 'required'],
            [['city_id'], 'exist', 'skipOnError' => true, 'targetClass' => City::class, 'targetAttribute' => ['city_id' => 'id']],

            ['phone_country', 'safe'],
            ['contact_phone', 'common\validators\PhoneValidator', 'countryAttribute' => 'phone_country']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'created_by' => 'Владелец',
            'updated_by' => 'Кем редактировано',
            'city_id' => 'Город',
            'created_at' => 'Создано в',
            'name' => 'ФИО',
            'contact_person' => 'Контактное лицо',
            'contact_phone' => 'Контактный телефон',
            'contact_email' => 'Контактный E-mail',
            'photo' => 'Фото',
            'type' => 'Тип',
            'status' => 'Статус',
            'reliability_rating' => 'Рейтинг надежности',
            'updated_at' => 'Редактировано в',
            'imageFile' => 'Файл изображения',
            'phone_view' => 'Просмотры номера',
        ];
    }

    public function afterValidate(){
        parent::afterValidate();

        //Чтобы не сохранялись номера +7
        if( strlen($this->contact_phone) < 3 )
            $this->contact_phone = '';
    }

    /**
     * @return ActiveQuery
     */
    public function getProfileTransporterTypeAssns()
    {
        return $this->hasMany(ProfileTransporterTypeAssn::class, ['profile_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getTransporterTypes()
    {
        return $this->hasMany(TransporterType::class, ['id' => 'type_id'])->viaTable('profile_transporter_type_assn', ['profile_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getCity()
    {
        return $this->hasOne(City::class, ['id' => 'city_id'])->cache(60 * 60);
    }

    /**
     * @return ActiveQuery
     */
    public function getUpdatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'updated_by']);
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
    public function getCargo()
    {
        return $this->hasMany(Cargo::class, ['profile_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getOffers()
    {
        return $this->hasMany(Offer::class, ['profile_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getTransport()
    {
        return $this->hasMany(Transport::class, ['profile_id' => 'id']);
    }

    /**
     * @return Offer[]
     */
    public function getActiveOffers()
    {
        $offers = array_filter($this->offers, function ($var) {
            /* @var Offer $var */
            return $var->status == Offer::STATUS_ACTIVE ? true : false;
        });
        return $offers;
    }

    /**
     * @param bool $colored
     * @return array
     */
    public static function getPersonLabels($colored = false)
    {
        return [
            self::TYPE_TRANSPORTER_PRIVATE => ($colored ? '<span style="color: #5cb85c">Частное лицо</span>' : 'Частное лицо'),
            self::TYPE_TRANSPORTER_JURIDICAL => ($colored ? '<span style="color: #eea236">Юридическое лицо</span>' : 'Юридическое лицо'),
            self::TYPE_TRANSPORTER_IP => ($colored ? '<span style="color: #edee00">ИП</span>' : 'ИП'),
        ];
    }

    /**
     * @param bool|true $bold
     * @return string
     */
    public function getPersonLabel($bold = true, $colored = true)
    {
        $labels = static::getPersonLabels();
        return isset($labels[$this->type]) ? $labels[$this->type] : 'Не указано';
    }

    /**
     * @param bool $colored
     * @return array
     */
    public static function getTypeLabels($colored = false)
    {
        return [
            self::TYPE_SENDER => ($colored ? '<span style="color: #5cb85c">Я буду размещать заявки на перевозку</span>' : 'Я буду размещать заявки на перевозку'),
            self::TYPE_TRANSPORTER_NOT_SPECIFIED => ($colored ? '<span style="color: #eea236">Я буду выполнять заявки на перевозку</span>' : 'Я буду выполнять заявки на перевозку'),
        ];
    }

    /**
     * @return string
     */
    public function getTypeLabel()
    {
        $labels = static::getTypeLabels();

        return isset($labels[$this->type]) ? $labels[$this->type] : 'Неизвестный тип';
    }

    /**
     * @param bool $colored
     * @return array
     */
    public static function getStatusLabels($colored = false)
    {
        return [
            self::STATUS_ACTIVE => ($colored ? '<span style="color: #5cb85c">Active</span>' : 'Active'),
            self::STATUS_PENDING => ($colored ? '<span style="color: #eea236">Pending</span>' : 'Pending'),
        ];
    }

    /**
     * @return string
     */
    public function getStatusLabel()
    {
        $labels = static::getStatusLabels();

        return isset($labels[$this->status]) ? $labels[$this->status] : 'Неизвестный статус';
    }

    public function getCityString($full_city = true)
    {
        if (!$this->city) {
            return false;
        }

        $city = $this->city->title_ru;

        if ($full_city) {
            if (!empty($this->city->region_ru)) {
                $city .= ', ' . trim($this->city->region_ru);
            }
            $city .= ', ' . $this->city->country->title_ru;
        }
        return $city;
    }

    public function getFQName($with_person = false, $is_encode = true, $with_city = false)
    {
        if ($this->type == self::TYPE_SENDER) {
            $name = $this->contact_person ? ($is_encode ? e($this->contact_person) : $this->contact_person) : "Отправитель";
        } else {
            $contact_person = $this->contact_person ? ($is_encode ? e($this->contact_person) : $this->contact_person) : "Перевозчик";
            $name = $this->name ? ($is_encode ? e($this->name) : $this->name) : $contact_person;
            $name = '«' . $name . '»';
            $name .= $with_person ? " (" . $this->getPersonLabel(false, false) . ")" : "";
            $cityName = $this->getCityString(false);
            $name .= ($with_city && $cityName) ? " из г. " . $cityName : "";
        }
        return $name;
    }

    /**
     * @param $duration
     */
    public function enableInfiniteOffers($duration)
    {
        $this->updateAttributes([
            'infinite_offers' => true,
            'infinite_until' => $this->infinite_offers ? ($this->infinite_until + $duration) : (time() + $duration)
        ]);
    }

    /**
     * @param $increment
     */
    public function increaseOfferLimit($increment)
    {
        $this->updateCounters(['paid_offers' => $increment]);
    }

    /**
     * @param $increment
     */
    public function increaseSmsAmount($increment)
    {
        $this->updateCounters(['sms_amount' => $increment]);
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        // Генерим slug
        $this->slug = SlugHelper::genSlug($this->contact_person ?: 'perevozchik');
        /////////////////

        if ($this->deleteImage) {
            $this->photo = null;
        }

        //для новых пользователей полгода бесплатных СМС
        if ($this->getIsNewRecord()) {
            $this->free_sms_untill = new Expression("NOW() + INTERVAL 6 MONTH");
        }

        return true;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        $filename = ImageHelper::previewUploadedImage(
            UploadedFile::getInstance($this, 'imageFile'),
            Yii::getAlias("@frontend/web/uploads/profiles/{$this->id}"),
            Yii::$app->params['profileThumbnails']
        );
        if ($filename) {
            $this->updateAttributes([
                'photo' => $filename
            ]);
        }

        if (!$insert) {
            static::getDb()
                ->createCommand()
                ->delete('profile_transporter_type_assn', ['profile_id' => $this->id])
                ->execute();
        }

        //при смене статуса перевозчика меняем категории его транспорта
        Utils::setTransportPrivateTransportation($this->id);


        //Статическая страница перевозчика
        //только если есть транспорт
        $transport = $this->getTransport()->one();
        if( $transport ){
            Yii::$app->gearman->getDispatcher()->background("buildPageCache", [
                'transport_id' => $transport->id
            ]);
        }
    }

    public function afterDelete()
    {
        parent::afterDelete();

        $file = Yii::getAlias('@frontend/uploads/profiles') . DIRECTORY_SEPARATOR . $this->photo;

        if (is_file($file)) {
            unlink($file);
        }
    }

    /**
     * @return ActiveQuery
     */
    public function getTransporterReviews()
    {
        return $this->hasMany(TransporterReviews::class, ['profile_id' => 'id']);
    }

    public function getImage()
    {
        if ($this->photo) {
            return "https://" . Yii::getAlias('@assetsDomain') . "/uploads/profiles/{$this->id}/thmb/" . ImageHelper::getThmbFilename($this->photo,
                    Yii::$app->params['profileThumbnails']['review']);
        } else {
            return "https://" . Yii::getAlias('@assetsDomain')."/img/icons/default_transport_icon.svg";
        }
    }

    public function getImagePng()
    {
        if ($this->photo) {
            return "https://" . Yii::getAlias('@assetsDomain') . "/uploads/profiles/{$this->id}/thmb/" . ImageHelper::getThmbFilename($this->photo,
                    Yii::$app->params['profileThumbnails']['review']);
        } else {
            return "https://" . Yii::getAlias('@assetsDomain')."/img/icons/default_transport_icon.png";
        }
    }

    /**
     * Объект микроразметки
     * @return Person
     */
    public function getStructured()
    {
        $address = new PostalAddress();
        if ($this->city) {
            $address->addressLocality = $this->city->title_ru;
            $address->addressRegion = $this->city->region_ru;
        }

        $place = new Place();
        $place->address = $address;

        $person = new Person();
        $person->name = $this->contact_person . " " . $this->name;
        $person->email = $this->contact_email;
        if ($this->contact_phone) {
            $person->telephone = PhoneHelpers::formatter($this->contact_phone);
        }
        $person->workLocation = $place;
        if ($this->photo) {
            $person->image = $this->image;
        }

        return $person;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        $url = Url::to([
            '/transporter/default/view2',
            'id' => $this->id,
            'slug' => $this->slug
        ]);

        return $url;
    }

    /**
     * Получаем Id следующего профиля в указанном городе
     * @param $cityId - Ид города
     */
    public function getPrev($cityId=null){
        $query = self::find()
            ->with(['city'])
            ->andWhere(['AND',
                ['!=', 'type', self::TYPE_SENDER],
                ['>', 'id', $this->id]
            ]);

        if($cityId)
            $query->andWhere(['city_id' => $cityId]);

        return $query->limit(1)->one();
    }

    /**
     * Получаем Id предыдущего профиля в указанном городе
     * @param $cityId - Ид города
     */
    public function getNext($cityId=null){
        $query = self::find()
            ->with(['city'])
            ->andWhere(['AND',
                ['!=', 'type', self::TYPE_SENDER],
                ['<', 'id', $this->id]
            ]);

        if($cityId)
            $query->andWhere(['city_id' => $cityId]);

        return $query->orderBy(['id' => SORT_DESC])->limit(1)->one();
    }

    /**
     * Обновленеи данных для kibana
     * @param $profile_id
     */
    public static function updateElk($profile_id){
        Yii::$app->gearman->getDispatcher()->background('ElkLog', [
            'profile_id' => $profile_id
        ]);
    }
}
