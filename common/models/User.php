<?php

namespace common\models;

use common\helpers\StringHelper;
use frontend\modules\subscribe\models\Subscribe;
use Yii;
use yii\base\NotSupportedException;
use yii\base\View;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * User model
 *
 * @property integer $id
 * @property string $username
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $email
 * @property string $phone
 * @property string $auth_key
 * @property integer $status
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $verification_date
 * @property string $password write-only password
 * @property Profile[] $profiles
 * @property Notification[] $notifications
 * @property Offer[] $offers
 * @property Offer[] $activeOffers
 * @property Cargo[] $activeCargo
 * @property int $activeCargoCount
 * @property Transport[] $activeTransport
 * @property int $activeTransportCount
 * @property Profile $transporterProfile
 * @property Profile $senderProfile
 */
class User extends ActiveRecord implements IdentityInterface
{
    const STATUS_DELETED = 0;
    const STATUS_ACTIVE = 10;
    const STATUS_PENDING = 20;
    const STATUS_BANNED = 40;

    /**
     * Шаблон с сообщением новому пользователю
     * @var array|false Массив с параметрами или false, false значит не отправлять уведомление о регистрации
     */
    static public $createUserTemplate = ['tpl' => "newUser"];

    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%user}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors(){
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules(){
        return [
            ['email', 'trim'],
            ['email', 'email'],
            ['email', 'unique'],
            ['email', 'string', 'max' => 255],

            ['status', 'default', 'value' => self::STATUS_PENDING],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_DELETED, self::STATUS_PENDING]],

            ['phone_country', 'safe'],
            ['phone', 'common\validators\PhoneValidator', 'countryAttribute' => 'phone_country'],

            ['news', 'boolean']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(){
        return [
            'id' => 'ID',
            'username' => 'Логин',
            'password_hash' => 'Хеш пароля',
            'password_reset_token' => 'Секретный токен для восстановления пароля',
            'auth_key' => 'Ключ авторизации',
            'status' => 'Статус',
            'created_at' => 'Создано в',
            'updated_at' => 'Редактировано в',
            'verification_date' => 'Дата верификации',
            'password' => 'Пароль',
            'phone' => "Телефон",
            'lastlogin' => 'Последний вход',
            'news' => 'Получать новости системы'
        ];
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id){
        return static::findOne(['id' => $id, 'status' => [self::STATUS_ACTIVE, self::STATUS_PENDING]]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null){
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username){
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    public static function findByEmail($email){
        if(empty($email))
            return false;

        return static::findOne(['email' => trim($email), 'status' => [self::STATUS_ACTIVE, self::STATUS_PENDING]]);
    }

    public static function findByPhone($phone){
        if( !$phone) return null;

        return static::findOne(['phone' => $phone, 'status' => [self::STATUS_ACTIVE, self::STATUS_PENDING]]);
    }

    /**
     * @param $name
     * @return null|static
     */
    public static function findByUsernameOrEmail($name){
        return static::find()->where(['or', ['username' => $name], ['email' => $name]])->one();
    }

    /**
     * Поиск пользователя по E-mail или телефону
     * @param $login
     * @return bool|static
     */
    public static function findByLogin($login){
        $user = self::findByEmail($login);

        if( !$user){
            //убираем лишние символы
            $phone = preg_replace("/[^0-9]/", "", $login);
            //если первая 8, то меняем на 7
            $phone = preg_replace("/^8/", "7", $phone);

            $user = self::findByPhone($phone);
        }

        return $user;
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token){
        if( !static::isPasswordResetTokenValid($token)){
            return null;
        }

        return static::findOne([
            'password_reset_token' => $token,
            'status' => [self::STATUS_ACTIVE, self::STATUS_PENDING]
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAuthKey($key){
        return static::find()->where(['auth_key' => $key])->one();
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return boolean
     */
    public static function isPasswordResetTokenValid($token){
        if(empty($token)){
            return false;
        }
        $timestamp = (int)substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        return $timestamp + $expire >= time();
    }

    /**
     * @inheritdoc
     */
    public function getId(){
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey(){
        return $this->auth_key;
    }

    /**
     * @return ActiveQuery
     */
    public function getProfiles(){
        return $this->hasMany(Profile::class, ['created_by' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getNotifications(){
        return $this->hasMany(Notification::class, ['user_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getOffers(){
        return $this->hasMany(Offer::class, ['created_by' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getActiveOffers(){
        return $this->hasMany(Offer::class, ['created_by' => 'id'])
            ->where(['not', ['status' => [Offer::STATUS_CANCELED]]]);
    }

    /**
     * @return ActiveQuery
     */
    public function getActiveCargo(){
        return $this->hasMany(Cargo::class, ['created_by' => 'id'])
            ->where(['status' => [Cargo::STATUS_ACTIVE]]);
    }

    /**
     * @return int|string
     */
    public function getActiveCargoCount(){
        return $this->getActiveCargo()->count();
    }

    /**
     * @return ActiveQuery
     */
    public function getActiveTransport(){
        return $this->hasMany(Transport::class, ['created_by' => 'id'])
            ->where(['status' => Transport::STATUS_ACTIVE]);
    }

    /**
     * @return int|string
     */
    public function getActiveTransportCount(){
        return $this->getActiveTransport()->count();
    }

    /**
     * @return ActiveQuery
     */
    public function getSenderProfile(){
        $q = $this->hasOne(Profile::class, ['created_by' => 'id']);
        $q->andWhere(['type' => Profile::TYPE_SENDER]);
        return $q;
    }

    /**
     * @return ActiveQuery
     */
    public function getTransporterProfile(){
        $q = $this->hasOne(Profile::class, ['created_by' => 'id']);
        $q->andWhere([
            'or',
            ['type' => Profile::TYPE_TRANSPORTER_JURIDICAL],
            ['type' => Profile::TYPE_TRANSPORTER_PRIVATE],
            ['type' => Profile::TYPE_TRANSPORTER_IP],
            ['type' => Profile::TYPE_TRANSPORTER_NOT_SPECIFIED],
        ]);
        return $q;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey){
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password){
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password){
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey(){
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken(){
        $this->password_reset_token = Yii::$app->security->generateRandomString().'_'.time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken(){
        $this->password_reset_token = null;
    }

    /**
     * @param bool $colored
     * @return array
     */
    public static function getStatusLabels($colored = false){
        return [
            self::STATUS_ACTIVE => ($colored ? '<span style="color: #3ab845">Активен</span>' : 'Активен'),
            self::STATUS_PENDING => ($colored ? '<span style="color: #2d618c">Не подтверждена почта</span>' : 'Не подтверждена почта'),
            self::STATUS_DELETED => ($colored ? '<span style="color: #ff0000">Удален</span>' : 'Удален'),
            self::STATUS_BANNED => ($colored ? '<span style="color: #ffd288">Забанен</span>' : 'Забанен'),
        ];
    }

    /**
     * @return string
     */
    public function getStatusLabel(){
        $labels = static::getStatusLabels();
        return isset($labels[$this->status]) ? $labels[$this->status] : 'Неизвестный статус';
    }

    public function beforeSave($insert){
        if( !parent::beforeSave($insert))
            return false;

        // Если запись новая
        if($insert){
            $this->generateAuthKey();

            $refid = null;

            //потому что пользователь может создаваться из консоли
            //например при парсинге с АВИТО
            if(property_exists(Yii::$app->request, 'cookies'))
                $refid = Yii::$app->request->cookies->getValue('refid');

            // Если есть рефка, надо проверить есть ли юзер
            if($refid && self::find()->where(['id' => $refid])->exists())
                $this->refid = $refid;
        }

        return true;
    }

    public function afterSave($insert, $changedAttributes){
        parent::afterSave($insert, $changedAttributes);

        //если новая запись
        if($insert){
            $passwd = StringHelper::str_rand(6, '1234567890'); // Генерим пароль для отправки на почту/СМС
            $this->setPassword($passwd);
            //обновляем поле "пароль"
            $this->updateAttributes(['password_hash' => $this->password_hash]);

            // получаем название шаблона уведомления пользователя
            // Если шаблон не устанолвен, то отправлять уведомление не нужно
            if($newUserMsgTmpl = self::$createUserTemplate) {
                $TplMsg = $newUserMsgTmpl['tpl'];
                $TplParams = isset($newUserMsgTmpl['params']) ? $newUserMsgTmpl['params'] : [];
                if (!empty($this->email)) {
                    $mailer = Yii::$app->mailer->compose("{$TplMsg}-html", array_merge($TplParams, ['user' => $this, 'passwd' => $passwd]))
                        ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name . ' support'])
                        ->setTo($this->email)
                        ->setSubject('Svezem.ru: Регистрация в сервисе');

                    $mailer->send();
                } elseif (!empty($this->phone)) {
                    //Отправляем СМС с паролем
                    $view = new View();
                    $smsMsg = $view->render("@common/sms/{$TplMsg}", array_merge($TplParams, ['phone' => $this->phone, 'pass' => $passwd]));

                    Yii::$app->sms->smsSend($this->phone, $smsMsg);
                }
            }

            //для нового пользователя создаем бесплатную подписку
//            $subscribe = Subscribe::findOne(['userid' => $this->id]);
//
//            if( !$subscribe){
//                Subscribe::createFree([
//                    'userid' => $this->id,
//                    'phone' => $this->phone,
//                    'email' => $this->email
//                ]);
//            }
        }
    }

    /**
     * Создание профиля перевозчика
     * @return bool|Profile
     */
    public function createTransporterProfile($type = Profile::TYPE_TRANSPORTER_PRIVATE, $opt = []){
        if($this->transporterProfile)
            return $this->transporterProfile;

        $profile = new Profile();
        $profile->type = $type;

        //поле created_by устанавливается при помощи Behavior
        //чтобы задать его, не авторизуя пользователя,
        //задаем значение ИД найденного/созданного пользователя
        $Behavior = $profile->getBehavior('BlameableBehavior');
        if($Behavior)
            $Behavior->value = $this->id;

        $profile->contact_phone = $this->phone;
        $profile->phone_country = $this->phone_country;

        if(isset($opt['contact_person']))
            $profile->contact_person = $opt['contact_person'];

        if(isset($opt['city_id']))
            $profile->city_id = $opt['city_id'];

        if($profile->save())
            return $profile;
        else
            return false;
    }

    public function adminAccess(){
        $roles = Yii::$app->authManager->getRolesByUser($this->id);
        $roles = array_keys($roles);

        if(count($roles) > 0 && $roles[0] != 'user')
            return true;
        else
            return false;
    }

    public function getAvatar(){
        return 'https://'.Yii::getAlias('@assetsDomain')."/img/icons/default_transport_icon.svg";
    }

    public function getAuthorName(){
        return $this->username;
    }
}
