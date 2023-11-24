<?php

namespace frontend\modules\articles\controllers;

use common\behaviors\NoSubdomain;
use common\helpers\LocationHelper;
use common\models\ArticleTags;
use common\models\Articles;
use common\models\FastCity;
use Yii;
use yii\base\InvalidArgumentException;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use common\helpers\TemplateHelper;
use common\models\CargoCategory;
use frontend\modules\articles\models\ArticlesSearch;

class DefaultController extends Controller
{
    public function behaviors()
    {
        return [
            NoSubdomain::class
        ];
    }

    /**
     * Список статей
     * @param null $slug
     * @return string
     * @throws NotFoundHttpException
     */
	public function actionIndex($slug = null) {
        // Шаблон страницы со статьями
        $pageTpl = 'articles-list';
        $category = null;
        if( $slug ){
            $category = CargoCategory::findOne(['slug'=>$slug]);
            if( !$category )
                throw new NotFoundHttpException('Страница не найдена');

            $pageTpl = 'articles-category-list';

            // Хлебные крошки
            //$currentCityDomain = \common\helpers\LocationHelper::getCurrentCityDomain();
            $this->view->params['breadcrumbs'][] = [
                'label' => 'Статьи по грузоперевозкам',
                'url' => Url::toRoute('/articles/')
            ];
            ////////////////

            // Ссылки следующая и предыдущая
            $nextCategory = $this->getNextCategory('/articles/'.$slug.'/');
            $prevCategory = $this->getPrevCategory('/articles/'.$slug.'/');
            if($nextCategory) $this->view->params['navlinks']['next'] = $nextCategory->url;
            if($prevCategory) $this->view->params['navlinks']['prev'] = $prevCategory->url;
            ///////////////
        }

        $fs = null;
        if($city = LocationHelper::getCityFromDomain()) {
            $fs = FastCity::findOne(['code' => $city]);
        }
        $queryParams['ArticlesSearch'] = [
            'cityid' => $fs ? $fs->cityid : null
        ];
        if($category) {
            $queryParams['ArticlesSearch']['categoryIds'] = $category->id;
        }
        $arSearchModel = new ArticlesSearch();
        $articleDataProvider = $arSearchModel->search($queryParams);

	    /*$articleProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'defaultPageSize' => $pageSize,
                'forcePageParam' => false
            ],
        ]);*/

		return $this->render('index', [
		    'articleProvider' => $articleDataProvider,
            'pageTpl' => TemplateHelper::get($pageTpl, null, $category),
            'tags' => ArticleTags::find()->all()
        ]);
	}

    /**
     * Отображение статьи
     * @param $id
     * @param $slug
     * @return string
     * @throws NotFoundHttpException
     */
	public function actionView($slug){

	    $article = Articles::find()
            ->where(['and',
                ['slug'=>$slug],
                ['status'=>Articles::STATUS_ACTIVE]
            ])
            ->one();

	    if( !$article )
            throw new NotFoundHttpException('Статья не найдена');

	    if( $article->slug != $slug)
	        return $this->redirect(Url::to(['/articles/default/view', 'slug'=>$article->slug]), 301);

        // Проверяем, что $city равен тому что в БД, иначе редирект 301
        $cityArticle = $article->city?$article->city->code:'';

        //если не совпадает город в поддомене
        //то делаем редирект на корректные данные
        if ($cityArticle != LocationHelper::getCityFromDomain()) {
            if(!$cityArticle)
                LocationHelper::toNoCityUrl();

            // Гет параметры надо тоже отправить в редиректе
            $route = array_merge(['/articles/default/view', 'slug' => $article->slug, 'city' => $cityArticle], Yii::$app->request->queryParams);

            return $this->redirect(Url::toRoute($route), 301);
        }

        // Хлебные крошки
        //$currentCityDomain = \common\helpers\LocationHelper::getCurrentCityDomain();
        $this->view->params['breadcrumbs'][] = [
            'label' => 'Статьи по грузоперевозкам',
            'url' => Url::toRoute('/articles/')
        ];
        ////////////////

		return $this->render('view', [
		    'article' => $article,
            'seeAlso' => Articles::getArticleTags($article)
        ]);
	}

    /**
     * Получаем следующую категорию
     */
    private function getPrevCategory($url){
        return ArticleTags::find()
            ->where(['>', 'url', $url])
            ->orderBy(['url' => SORT_ASC])
            ->limit(1)
            ->one();
    }

    /**
     * Получаем предыдущую категорию
     */
    private function getNextCategory($url){
        return ArticleTags::find()
            ->where(['<', 'url', $url])
            ->orderBy(['url' => SORT_DESC])
            ->limit(1)
            ->one();
    }
}
