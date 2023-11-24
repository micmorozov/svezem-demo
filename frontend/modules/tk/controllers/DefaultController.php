<?php

namespace frontend\modules\tk\controllers;

use common\behaviors\NoSubdomain;
use common\helpers\LocationHelper;
use common\helpers\TemplateHelper;
use common\models\City;
use common\models\FetchPhoneLog;
use common\models\TkSearchTags;
use frontend\modules\tk\models\CreateTkForm;
use frontend\modules\tk\models\Tk;
use frontend\modules\tk\models\TkSearch;
use frontend\widgets\phoneButton\FetchPhoneAction;
use micmorozov\yii2\gearman\Dispatcher;
use Svezem\Services\MatrixContentService\MatrixContentService;
use Yii;
use yii\filters\AccessControl;
use yii\filters\PageCache;
use yii\helpers\Json;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\helpers\Url;

class DefaultController extends Controller
{
    /** @var MatrixContentService  */
    private $matrixContentService;

    public function __construct($id, $module, MatrixContentService $matrixContentService, $config = [])
    {
        $this->matrixContentService = $matrixContentService;

        parent::__construct($id, $module, $config);
    }

    public function behaviors()
    {
        return [
            'nosubdomain' => [
                'class' => NoSubdomain::class,
                'only' => ['view2']
            ],

            'pageCache' => [
                'class' => PageCache::class,
                // Кэш работает для не авторизованного пользователя и нет города в поддомене
                'enabled' => Yii::$app->user->isGuest && !LocationHelper::getCityFromDomain(),
                'only' => ['view2'],
                'duration' => 86400,
                'variations' => [
                    Yii::$app->request->get('id')
                ]
            ],
        ];
    }

    public function actions()
    {
        return [
            'fetch-phone' => [
                'class' => FetchPhoneAction::class,
                'object' => FetchPhoneLog::OBJECT_TK
            ]
        ];
    }

    public function actionCreate()
    {
        $model = new CreateTkForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $model->subject = 'Запрос добавления новой ТК';
            if ($model->sendEmail(Yii::$app->params['adminEmail'])) {
                Yii::$app->session->setFlash('success',
                    'Спасибо за запрос. В ближайшее время мы добавим вашу транспортную кампанию');
            } else {
                Yii::$app->session->setFlash('error',
                    'Произошла ошибка при отправке сообщения. Попробуйте связаться с нами по контактам, указанным в разделе Контакты');
            }
            return $this->refresh();
        }

        return $this->render('create', ['model' => $model]);
    }

    /**
     * Поиск транспортных компаний
     * @param $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        $model = Tk::find()
            ->alias('tk')
            ->joinWith(['details.city', 'categories'])
            ->where([
                'tk.id' => $id,
                'status' => Tk::STATUS_ACTIVE
            ])
            ->one();

        if ($model == null) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        //////////////////////////////////
        // РЕДИРЕКТ НА НОВУЮ СТРУКТУРУ
        // Гет параметры надо тоже отправить в редиректе
        $route = array_merge(Yii::$app->request->queryParams, ['/tk/default/view2', 'id' => $id, 'slug' => $model->slug]);

        return Yii::$app->getResponse()->redirect('https://' . Yii::getAlias('@domain') . Url::toRoute($route), 301, false);
        //////////////////////////////////

        // Хлебные крошки
        $this->view->params['breadcrumbs'][] = [
            'label' => 'Поиск транспортных компаний',
            'url' => Url::toRoute('/tk/default/search')
        ];
        $this->view->params['breadcrumbs'][] = [
            'label' => "Транспортная компания {$model->name}"
        ];

        ////////////////

        // Ссылки следующая и предыдущая
        $nextTk = $model->getNext();
        $prevTk = $model->getPrev();
        if($nextTk) $this->view->params['navlinks']['next'] = Url::toRoute(['/tk/default/view', 'id' => $nextTk->id]);
        if($prevTk) $this->view->params['navlinks']['prev'] = Url::toRoute(['/tk/default/view', 'id' => $prevTk->id]);
        ///////////////

        return $this->render('view', [
            'model' => $model,
            'matrixContentService' => $this->matrixContentService
        ]);
    }

    public function actionView2($id, $slug)
    {
        $model = Tk::find()
            ->alias('tk')
            ->joinWith(['details.city', 'categories'])
            ->where([
                'tk.id' => $id,
                'status' => Tk::STATUS_ACTIVE
            ])
            ->one();

        if ($model == null) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        // сверяем slug
        if($model->slug != $slug){
            // Гет параметры надо тоже отправить в редиректе
            $route = array_merge(Yii::$app->request->queryParams, ['/tk/default/view2', 'id' => $id, 'slug' => $model->slug]);
            return Yii::$app->getResponse()->redirect(Url::toRoute($route), 301, false);
        }

        // Хлебные крошки
        $this->view->params['breadcrumbs'][] = [
            'label' => 'Поиск транспортных компаний',
            'url' => Url::toRoute('/tk/default/search')
        ];
        $this->view->params['breadcrumbs'][] = [
            'label' => "Транспортная компания {$model->name}"
        ];

        ////////////////

        // Ссылки следующая и предыдущая
        $nextTk = $model->getNext();
        $prevTk = $model->getPrev();
        if($nextTk) $this->view->params['navlinks']['next'] = Url::toRoute(['/tk/default/view2', 'id' => $nextTk->id, 'slug' => $nextTk->slug]);
        if($prevTk) $this->view->params['navlinks']['prev'] = Url::toRoute(['/tk/default/view2', 'id' => $prevTk->id, 'slug' => $prevTk->slug]);
        ///////////////

        return $this->render('view', [
            'model' => $model,
            'matrixContentService'=> $this->matrixContentService
        ]);
    }
}
