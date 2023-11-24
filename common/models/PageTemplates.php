<?php

namespace common\models;
use yii\caching\TagDependency;
use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "page_templates".
 *
 * @property integer $id
 * @property string $title
 * @property string $desc
 * @property string $h1
 * @property string $type
 * @property integer $is_city
 * @property int $city_id Город
 * @property integer $category_id
 * @property integer $default
 * @property string $text
 * @property string $tag_name
 * @property string $tr_title Заголовок транспорта
 * @property string $tk_title Заголовок ТК
 * @property string $cargo_title Заголовок груза
 * @property string $keywords
 *
 * @property CargoCategory $category
 * @property string $cargo_hint
 * @property City $city
 * @property Region $region
 *
 * @property int $one_city Один город
 * @property string $cargoFormTitle Наименование формы добавления груза
 */
class PageTemplates extends ActiveRecord
{
    const TYPE_MAIN = 'main';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'page_templates';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title', 'h1', 'is_city'], 'required'],
            [['is_city', 'city_id', 'category_id', 'one_city'], 'integer'],
            [['type', 'cargoFormTitle'], 'string'],
            [['h1', 'tag_name', 'tr_title', 'tk_title', 'cargo_title'], 'string', 'max' => 128],
            [['title', 'keywords'], 'string', 'max' => 256],
            [['text', 'city_id'], 'safe'],
            [['cargo_hint', 'desc'], 'string', 'max' => 512],
            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => CargoCategory::class, 'targetAttribute' => ['category_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ИД шаблона',
            'title' => 'Текст заголовка',
            'desc' => 'Текст описания',
            'keywords' => 'Ключевые слова',
            'h1' => 'H1',
            'text' => 'Текст на странице',
            'tag_name' => 'Наименование тега',
            'tr_title' => 'Заголовок транспорта',
            'tk_title' => 'Заголовок ТК',
            'cargo_title' => 'Заголовок груза',
            'type' => 'Тип шаблона',
            'is_city' => 'Шаблон города',
            'city_id' => 'Город',
            'category_id' => 'ИД категории',
            'default' => 'Шаблон по умолчанию',
            'cargo_hint' => 'Подсказка для описания груза',
            'one_city' => 'Один город',
            'cargoFormTitle' => 'Наименование формы добавления груза',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(CargoCategory::class, ['id' => 'category_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getCity()
    {
        return $this->hasOne(City::class, ['id' => 'city_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getRegion()
    {
        return $this->hasOne(Region::class, ['id' => 'city_id']);
    }

    static public function getTypeList(){
        /** @var $types self[] */
        $types = self::find()
            ->select('type')
            ->distinct('type')
            ->all();

        $list = [];
        foreach($types as $model){
            $list[$model->type] = $model->getTypeLabel();
        }

        return $list;
    }

    static public function typeLabel(){
        return [
            self::TYPE_MAIN => 'Главная страница'
        ];
    }

    public function getTypeLabel(){
        $types = self::typeLabel();
        return isset($types[$this->type])?$types[$this->type]:$this->type;
    }

    static public function isCityList(){
        return [
            1 => 'Города',
            0 => 'Страны'
        ];
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        $tagKey = "PageTemplates.{$this->type}.".($this->is_city?1:0).'.'.($this->city_id??0).($this->category_id?".$this->category_id":'');

        TagDependency::invalidate(Yii::$app->cache, $tagKey);
    }

    /**
     * Является ли текущий шаблон шаблоном страницы услуг
     * @return bool
     */
    public function isServicePageTpl(): bool
    {
        return in_array($this->type, ['cargo-transportation-view']);
    }
}
