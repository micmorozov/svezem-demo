<?php

namespace common\modules\Notify\models;

use common\models\Transport;
use common\models\User;
use frontend\modules\subscribe\models\Subscribe;
use frontend\modules\subscribe\models\SubscribeRules;
use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "notify_rule".
 *
 * @property int $id
 * @property string $page Страница
 * @property string $message Сообщение
 * @property string $type Тип
 * @property string $url Ссылка
 * @property int $delay Задержка
 * @property string $rule Правило
 *
 * @property bool $suitable;
 */
class NotifyRule extends ActiveRecord
{
    const PAGE_CARGO_VIEW = 'cargo/default/view';
    const TRANSPORT_SEARCH = 'transport/default/search';
    const TK_SEARCH = 'tk/default/search';
    const TK_PRICE_COMPARISON = 'tk/comparison/index';
    const SIGNUP_TRANSPORT = 'account/default/signup-transport';
    const CARGO_PASSING = 'cargo/default/passing';
    const CARGO_SEARCH = 'cargo/default/search';
    const SUBSCRIBE = 'sub/default/index';
    const MAIN_PAGE = 'svezem-frontend/site/index';
    const TRANSPORTER_VIEW = 'transporter/default/view';
    const TK_VIEW = 'tk/default/view';
    const ANY_PAGE = 'any_page';

    const TYPE_SUCCESS = 'success';
    const TYPE_INFO = 'info';
    const TYPE_DANGER = 'danger';
    const TYPE_WARNING = 'warning';

    /**
     * {@inheritdoc}
     */
    public static function tableName(){
        return 'notify_rule';
    }

    public function init(){
        $this->delay = 5000;
        $this->type = self::TYPE_INFO;

        parent::init();
    }

    /**
     * {@inheritdoc}
     */
    public function rules(){
        return [
            [['page', 'rule', 'delay'], 'required'],
            [['type'], 'in', 'range' => array_keys(self::typeLabels())],
            [['delay'], 'integer', 'skipOnEmpty' => false],
            [['rule'], 'string'],
            [['page'], 'string', 'max' => 64],
            [['message', 'url'], 'string', 'max' => 256]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(){
        return [
            'id' => 'ID',
            'page' => 'Страница',
            'message' => 'Сообщение',
            'type' => 'Тип',
            'url' => 'Ссылка',
            'delay' => 'Время отображения сообщения(мс)',
            'rule' => 'Правило',
        ];
    }

    /**
     * @return array
     */
    static public function pageLabels(){
        return [
            self::PAGE_CARGO_VIEW => 'Страница груза cargo/<id>',
            self::TRANSPORT_SEARCH => 'Поиск транспорта transport/search/',
            self::TK_SEARCH => 'Поиск ТК tk/search/',
            self::TK_PRICE_COMPARISON => 'Сравнение цен ТК tk/price-comparison/',
            self::SIGNUP_TRANSPORT => 'Добавление транспорта account/signup-transport/',
            self::CARGO_PASSING => 'Поиск попутного груза cargo/passing/',
            self::CARGO_SEARCH => 'Поиск груза cargo/search/',
            self::SUBSCRIBE => 'Подписка sub/',
            self::MAIN_PAGE => 'Главная страница',
            self::TRANSPORTER_VIEW => 'Страница перевозчика transporter/<id>',
            self::TK_VIEW => 'Страница ТК tk/<id>',
            self::ANY_PAGE => 'Любая страница'
        ];
    }

    /**
     * @param $page
     * @return mixed|null
     */
    static public function getPageLabel($page){
        $list = self::pageLabels();
        return $list[$page]??null;
    }

    /**
     * @return array
     */
    static public function typeLabels(){
        return [
            self::TYPE_SUCCESS => 'Успешный',
            self::TYPE_INFO => 'Инфо',
            self::TYPE_DANGER => 'Опасность',
            self::TYPE_WARNING => 'Предупреждение'
        ];
    }

    /**
     * @param $type
     * @return mixed|null
     */
    static function getTypeLabel($type){
        $list = self::typeLabels();
        return $list[$type]??null;
    }

    /**
     * @return mixed
     */
    public function getSuitable(){
        //Используется в eval
        $user = User::findOne(Yii::$app->user->id);
        $subscribe = Subscribe::findOne(['userid' => Yii::$app->user->id]);

        $transportCount = 0;
        $subscribeRulesCount = 0;

        if( $subscribe ){
            $rulesQuery = $subscribe->getSubscribeRules()
                ->andWhere(['status' => SubscribeRules::STATUS_ACTIVE]);
            $subscribeRulesCount = (int)$rulesQuery->count();
        }

        if($user){
            $trQuery = Transport::find()
                ->where(['created_by' => $user->id]);

            $transportCount = (int)$trQuery->count();
        }

        $result = false;
        eval($this->rule);
        return $result;
    }

    /**
     * @param $userid
     * @return array
     */
    static public function getVars($userid){
        $user = User::findOne($userid);
        return [$user];
    }
}
