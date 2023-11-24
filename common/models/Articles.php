<?php

namespace common\models;

use common\behaviors\JunctionBehavior;
use common\behaviors\SlugBehavior;
use common\models\query\ArticlesQuery;
use mohorev\file\UploadImageBehavior;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\caching\TagDependency;
use Twig_Environment;
use Twig_Function;
use Twig_Loader_Filesystem;
use yii\helpers\Url;

/**
 * This is the model class for table "articles".
 *
 * @property integer $id
 * @property string $slug
 * @property string $name
 * @property string $body
 * @property string $img
 * @property integer $status
 * @property string $description
 * @property string $keywords
 * @property string $preview
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $viewBody
 * @property integer $cityid
 * @property string url
 *
 * @property array $categoryIds
 * @property CargoCategory[] $categories
 * @property City $city
 */
class Articles extends ActiveRecord
{
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    protected $_categoryIds;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'articles';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
            [
                'class' => SlugBehavior::class,
                'attribute' => 'name'
            ],
            [
                //Сохранение данных в промежуточные таблицы
                'class' => JunctionBehavior::class,
                'association' => [
                    ['categoryIds', CargoCategory::class, 'categories']
                ]
            ],
            [
                'class' => UploadImageBehavior::class,
                'attribute' => 'img',
                'scenarios' => [self::SCENARIO_DEFAULT],
                'path' => '@frontend/web/uploads/article/{id}',
                'url' => '@frontend/web/uploads/article/{id}',
                'thumbPath' => '@frontend/web/uploads/article/{id}/thumbnails/',
                'thumbUrl' => 'uploads/article/{id}/thumbnails/',
                'thumbs' => [
                    'article' => ['width' => 1140], //большая картинка в статье
                    'preview' => ['width' => 540], // в списке статей
                    'link_preview' => ['width' => 165] //в ссылке на статью
                ]
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['body'], 'string'],
            [['status', 'created_at', 'updated_at'], 'integer'],
            [['name', 'slug', 'keywords'], 'string', 'max' => 128],
            [['description'], 'string', 'max' => 256],
            ['preview', 'string', 'max' => 1024],
            ['img', 'image', 'minWidth'=>1140, 'minHeight'=>385, 'maxSize'=>1048576],
            ['cityid', 'exist', 'targetClass' => City::class, 'targetAttribute' => 'id'],
            ['categoryIds', 'exist', 'targetClass' => CargoCategory::class, 'targetAttribute' => 'id', 'allowArray' => true]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ИД статьи',
            'slug' => 'ЧПУ',
            'name' => 'Наименование',
            'body' => 'Текст статьи',
            'img' => 'Картинка',
            'status' => 'Статус',
            'description' => 'Описание',
            'keywords' => 'Ключевые слова',
            'preview' => 'Анонс',
            'created_at' => 'Время создания',
            'updated_at' => 'Время редактирования',
            'categoryIds' => 'Категории',
            'cityid' => 'Город размещения статьи'
        ];
    }

    /**
     * @inheritdoc
     * @return ArticlesQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ArticlesQuery(get_called_class());
    }

    static public function statusLabels(){
        return [
            self::STATUS_ACTIVE => 'активна',
            self::STATUS_INACTIVE => 'неактивна'
        ];
    }

    public function getStatusLabel(){
        $status = $this->status;
        $statuses = self::statusLabels();
        return isset($statuses[$status])?$statuses[$status]:'';
    }

    /**
     * @return ActiveQuery
     */
    public function getCategories(){
        return $this->hasMany(CargoCategory::class, ['id' => 'category_id'])
            ->viaTable('articles_category_assn', ['article_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getCity(){
        return $this->hasOne(City::class, ['id' => 'cityid']);
    }

    public function getCategoryIds(){
        if( !$this->_categoryIds )
            $this->_categoryIds = ArrayHelper::getColumn($this->categories, 'id');
        return $this->_categoryIds;
    }

    public function setCategoryIds($ids){
        $this->_categoryIds = $ids;
    }

    /**
     * @param null $thunbnail
     * @param $thumbName
     * @return string
     */
    public function imagePath($thumbName = null){
        $path = "https://".Yii::getAlias('@assetsDomain')."/uploads/article/{$this->id}/";

        if( $thumbName )
            $path .= "thumbnails/$thumbName-";

        return $path.$this->img;
    }

    public function getViewBody(){
        $loader = new Twig_Loader_Filesystem('');
        $twig = new Twig_Environment($loader);

        $twigFunction = new Twig_Function('adv', function($code){
            $setting = Setting::findOne(['code'=>$code]);
            $value = $setting ? $setting->value : '';

            //рекламный блок
            $adv_block =<<<BLOCK
<div class="post__news">
    <div class="news clear">
    $value
    </div>
</div>
BLOCK;
            echo $adv_block;
        });

        $twig->addFunction($twigFunction);

        $template = $twig->createTemplate($this->body);

        return $template->render([]);
    }

    public function beforeSave($insert){
        if( !parent::beforeSave($insert) )
            return false;

        //TODO удалить в будущем
        //расширить автокатегорией
        $this->extendCategory();

        return true;
    }

    /**
     * расширить автокатегорией
     */
    public function extendCategory(){
        $category = CargoCategory::find()->where(['transportation_type'=>1])->one();
        if( $category )
            $this->_categoryIds[] = $category->id;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        //очищаем кэш поиска
        TagDependency::invalidate(Yii::$app->cache, 'articleSearchCache');
    }

    public function getUrl() : string
    {
        return Url::to([
            '/articles/default/view',
            'slug' => $this->slug
        ]);
    }

    public static function getArticleTags(Articles $article, int $count = 4): ?array
    {
        $result = self::find()
            ->andWhere(['>', 'id', $article->id])
            ->orderBy(['id' => SORT_ASC])
            ->limit($count)
            ->all();

        // Если находимся в конце списка, то добавляем недостающие элементы с начала списка
        if(count($result) < $count){
            $res = self::find()
                ->orderBy(['id' => SORT_ASC])
                ->limit($count-count($result))
                ->all();
            if($res) array_push($result, ...$res);
        }

        return $result;
    }
}
