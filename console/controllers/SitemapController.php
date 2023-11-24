<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 05.02.19
 * Time: 17:52
 */

namespace console\controllers;

use common\helpers\Utils;
use common\models\Articles;
use common\models\ArticleTags;
use common\models\Cargo;
use common\models\CargoCategory;
use common\models\CargoSearchTags;
use common\models\City;
use common\models\FastCity;
use common\models\LocationInterface;
use common\models\Profile;
use common\models\Region;
use common\models\Transport;
use common\models\TransportSearchTags;
use console\controllers\sitemap\SitemapLastIdRedisStorage;
use frontend\components\CityComponent;
use frontend\modules\tk\models\Tk;
use samdark\sitemap\Index;
use samdark\sitemap\Sitemap;
use Svezem\Services\MatrixContentService\MatrixContentService;
use Yii;
use yii\console\Controller;
use yii\helpers\FileHelper;
use yii\helpers\Url;

class SitemapController extends BaseController
{
    const SITE_MAP_PATH = 'sitemaps';

    /** @var $categories CargoCategory[] */
    private $categories;

    /** @var CargoCategory[] */
    private $rootCategories;

    /** @var MatrixContentService  */
    private $matrixContentService;

    public function __construct($id, $module,
        MatrixContentService $matrixContentService,
        $config = [])
    {
        $this->matrixContentService = $matrixContentService;

        parent::__construct($id, $module, $config);
    }

    public function init()
    {
        parent::init();

        $this->categories = CargoCategory::find()
            ->where(['create_tag'=>1])
            ->all();

        $this->rootCategories = array_filter($this->categories, function(CargoCategory $category){
            return in_array($category->slug, ['pereezd', 'gruzoperevozki']);
        });
    }

    public function actionBuild()
    {
        $startTime = microtime(true);

        $tmpSitemapDir = sys_get_temp_dir() . '/sitemap-'.time();
        FileHelper::createDirectory($tmpSitemapDir);

        $sitemapDir = Yii::getAlias('@frontend/web/'.self::SITE_MAP_PATH);
        FileHelper::createDirectory($sitemapDir);

        $baseUrl = 'https://'.Yii::getAlias('@domain');

        //генерим ссылки для главного домена
        $sitemap = new Sitemap($tmpSitemapDir.'/file.xml.gz');
        $sitemap->setUseGzip(true);

        $this->baseLocation($sitemap, $baseUrl);

        $this->anyItem($sitemap, $baseUrl);

        //Генерация страниц выбора города
        $this->citySelectGenerate($sitemap, $baseUrl);

        $this->cargoSearch($sitemap, $baseUrl);
        $this->cargoSearchTags($sitemap, $baseUrl);

        $this->transportSearch($sitemap, $baseUrl);
        $this->transportSearchTags($sitemap, $baseUrl);

        $this->tkSearch($sitemap, $baseUrl);

        //грузы
        $this->cargo($sitemap, $baseUrl);
        //перевозчики
        $this->transporter($sitemap, $baseUrl);
        //Транспортные компании
        $this->tk($sitemap, $baseUrl);
        //статьи
        $this->articles($sitemap, $baseUrl);
        $this->articleTags($sitemap, $baseUrl);

        // URL с категориями
        $this->cargoByCategories($sitemap, $baseUrl);

        $this->interCity($sitemap, $baseUrl);

        $regionQuery = Region::find()
            ->where(['not', ['center' => null]]);

        /** @var Region $region */
        foreach($regionQuery->each() as $region){

            $this->baseLocation($sitemap, $baseUrl, $region);

            $this->cargoSearch($sitemap, $baseUrl, $region);
            $this->transportSearch($sitemap, $baseUrl, $region);
            $this->tkSearch($sitemap, $baseUrl, $region);

            $this->interCity($sitemap, $baseUrl, $region);

            // URL с категориями
            $this->cargoByCategories($sitemap, $baseUrl, $region);
        }

        /** @var FastCity $fastCity */
        foreach(FastCity::find()->each() as $fastCity){
            /** @var City $city */
            $city = $fastCity->city;

            $this->baseLocation($sitemap, $baseUrl, $city);

            $this->cargoSearch($sitemap, $baseUrl, $city);
            $this->transportSearch($sitemap, $baseUrl, $city);
            $this->tkSearch($sitemap, $baseUrl, $city);

            $this->interCity($sitemap, $baseUrl, $city);

            // URL с категориями
            $this->cargoByCategories($sitemap, $baseUrl, $city);
        }

        $sitemap->write();

        // Созданные во временной директории файлы копируем в рабочую директорию
        // Можно переименовывать директорию, но у нас в sitemaps лежат файлы со старой структуры
        $scanDir = array_diff(scandir($tmpSitemapDir), array('..', '.'));
        foreach($scanDir as $sitemapFile){
            rename($tmpSitemapDir . '/' . $sitemapFile, $sitemapDir . '/' . $sitemapFile);
        }
        @rmdir($tmpSitemapDir);

        // create sitemap index file
        $index = new Index(Yii::getAlias('@frontend/web/') . 'sitemap.xml');
        $index->setUseGzip(false);

        // add URLs
        $sitemapFileUrls = $sitemap->getSitemapUrls($baseUrl.'/'.self::SITE_MAP_PATH.'/');
        foreach ($sitemapFileUrls as $sitemapUrl) {
            $index->addSitemap($sitemapUrl);
        }
        $index->write();

        $endTime = microtime(true);
        echo 'Время генерации sitemap: ' . ($endTime-$startTime)." сек\n";
    }

    /**
     * @param $sitemap Sitemap
     */
    protected function cargo(&$sitemap, $baseUrl)
    {
        $query = Cargo::find()
            ->where(['and',
                ['<>', 'status', Cargo::STATUS_BANNED]
            ]);

        /** @var Cargo $cargo */
        foreach($query->each() as $cargo){
            $sitemap->addItem($baseUrl . $cargo->url, null/*$cargo->updated_at*/, $cargo->isExpired?Sitemap::YEARLY:Sitemap::MONTHLY, $cargo->isExpired?0.1:0.3);
        }
    }

    /**
     * @param $sitemap Sitemap
     */
    protected function transporter(&$sitemap, $baseUrl)
    {
        $query = Profile::find()
            ->innerJoinWith('transport')
            ->where(['not', [Transport::tableName().'.id' => null]])
            ->limit(200);

        /** @var Profile[] $profiles */
        while($profiles = $query->all()){
            $query->offset += $query->limit;

            foreach($profiles as $profile){
                $sitemap->addItem($baseUrl . $profile->url, null/*$profile->updated_at*/, Sitemap::MONTHLY, 0.3);
            }
        }
    }

    /**
     * @param $sitemap Sitemap
     */
    protected function tk(&$sitemap, $baseUrl)
    {
        $query = Tk::find()
            ->where(['status'=>Tk::STATUS_ACTIVE]);

        /** @var Tk $tk */
        foreach($query->each() as $tk){
            $sitemap->addItem($baseUrl . $tk->getUrl(), null, Sitemap::YEARLY, 0.3);
        }
    }

    /**
     * @param $sitemap Sitemap
     */
    protected function articles(&$sitemap, $baseUrl)
    {
        $sitemap->addItem($baseUrl . Url::toRoute('/articles/'), null, Sitemap::YEARLY, 0.5);

        $query = Articles::find()
            ->where(['status' => Articles::STATUS_ACTIVE])
        ;

        /** @var Articles $article */
        foreach($query->each() as $article){
            $sitemap->addItem($baseUrl . $article->url, null/*$article->updated_at*/, Sitemap::YEARLY, 0.3);
        }
    }

    /**
     * @param $sitemap Sitemap
     * @package $baseUrl string
     */
    protected function articleTags(&$sitemap, $baseUrl)
    {
        /** @var ArticleTags $tag */
        foreach(ArticleTags::find()->each() as $tag){
            $sitemap->addItem($baseUrl . $tag->url, null, Sitemap::YEARLY, 0.1);
        }
    }

    /**
     * @param $sitemap Sitemap
     * @param $baseUrl string
     * @param $location LocationInterface
     */
    protected function cargoByCategories(&$sitemap, $baseUrl, LocationInterface $location = null)
    {
        if(!$this->matrixContentService->isEnoughContentAnyDirection('cargo-transportation-view', $location)){
            return;
        }

        $sitemap->addItem($baseUrl . Url::toRoute(["/cargo/transportation",
                'location' => $location]), null, Sitemap::MONTHLY, 0.6);

        foreach($this->categories as $cat){
            $categoryRequired = true;
            if( $location instanceof City ) {
                $categoryRequired = Utils::check_mask($location->size, $cat->city_size_mask);
            }
            $isEnough = $categoryRequired || $this->matrixContentService->isEnoughContentAnyDirection('cargo-transportation-view', $location, $cat);

            if (!$isEnough) continue;

            $sitemap->addItem($baseUrl . Url::toRoute(["cargo/transportation/search2",
                    'slug' => $cat, 'location' => $location]), null, Sitemap::MONTHLY, 0.6);
        }
    }

    /**
     * @param $sitemap Sitemap
     * @param $baseUrl string
     * @param $location LocationInterface
     */
    protected function cargoSearch(&$sitemap, $baseUrl, LocationInterface $location = null)
    {
        $sitemap->addItem($baseUrl.Url::toRoute(['/cargo/search/index', 'location'=>$location]), null, Sitemap::MONTHLY, 0.7);
    }

    /**
     * @param $sitemap Sitemap
     * @param $baseUrl string
     */
    protected function cargoSearchTags(&$sitemap, $baseUrl)
    {
        $query = CargoSearchTags::find();

        /** @var CargoSearchTags $tag */
        foreach($query->each() as $tag){
            $sitemap->addItem($tag->url, null, Sitemap::MONTHLY, 0.5);

            $lastId = $tag->id;
        }
    }

    /**
     * @param $sitemap Sitemap
     * @param $baseUrl string
     * @param $location LocationInterface
     */
    protected function transportSearch(&$sitemap, $baseUrl, LocationInterface $location = null)
    {
        // Поиск транспорта
        $sitemap->addItem($baseUrl.Url::toRoute(['/transport/search/index', 'location'=>$location]), null, Sitemap::MONTHLY, 0.7);
    }

    /**
     * @param $sitemap Sitemap
     * @param $baseUrl string
     */
    protected function transportSearchTags(&$sitemap, $baseUrl)
    {
        $query = TransportSearchTags::find();

        /** @var TransportSearchTags $tag */
        foreach($query->each() as $tag){
            $sitemap->addItem($tag->url, null, Sitemap::MONTHLY, 0.5);
        }
    }

    /**
     * @param $sitemap Sitemap
     * @param $baseUrl string
     * @param $location LocationInterface
     */
    protected function tkSearch(&$sitemap, $baseUrl, LocationInterface $location = null)
    {
        // Поиск транспорта
        $sitemap->addItem($baseUrl.Url::toRoute(['/tk/search/index', 'location'=>$location]), null, Sitemap::MONTHLY, 0.7);
    }

    /**
     * @param $sitemap Sitemap
     * @param $baseUrl string
     * @param $location LocationInterface
     */
    protected function interCity(&$sitemap, $baseUrl, LocationInterface $location = null)
    {
        $sitemap->addItem($baseUrl . Url::toRoute([
                "/intercity/default/index",
                "location" => $location
            ]), null, Sitemap::MONTHLY, 0.6);

        if(!$location instanceof City) {
            return;
        }

        $query = FastCity::find();

        /** @var FastCity $toFastCity */
        foreach($query->each() as $toFastCity){
            if($location->getId() == $toFastCity->cityid)
                continue;

            $city = $toFastCity->city;
            foreach($this->rootCategories as $rootCategory) {
                if (!$this->matrixContentService->isEnoughContent('intercity-view', $location, $city, $rootCategory)) {
                    continue;
                }

                $sitemap->addItem($baseUrl . Url::toRoute([
                        "/intercity/default/transportation2",
                        "root" => $rootCategory,
                        "cityFrom" => $location,
                        "cityTo" => $city
                    ]), null, Sitemap::MONTHLY, 0.6);
            }
        }
    }

    /**
     * @param Sitemap $sitemap
     * @param string $baseUrl
     */
    protected function citySelectGenerate(&$sitemap, $baseUrl)
    {
        $sitemap->addItem($baseUrl.Url::toRoute('/city'), null, Sitemap::MONTHLY, 0.8);

        $alphabet = CityComponent::getAlphabet();
        foreach ($alphabet as $char){
            $sitemap->addItem($baseUrl.Url::toRoute('/city/'.urlencode($char)), null, Sitemap::MONTHLY, 0.8);
        }
    }

    protected function anyItem(&$sitemap, $baseUrl)
    {
        // контакты
        $sitemap->addItem($baseUrl.Url::toRoute('/contacts/'), null, Sitemap::MONTHLY, 0.1);

        // Страница с инфой о платежах
        //$sitemap->addItem($baseUrl.Url::toRoute('/info/mobile-pay'), null, Sitemap::YEARLY, 0.1);
        //$sitemap->addItem($baseUrl.Url::toRoute('/info/subscribe'), null, Sitemap::YEARLY, 0.1);
        //$sitemap->addItem(Url::toRoute('/info/how-it-works/client'), null, Sitemap::YEARLY, 0.1);
        //$sitemap->addItem(Url::toRoute('/info/how-it-works/transporter'), null, Sitemap::YEARLY, 0.1);
        //$sitemap->addItem(Url::toRoute('/info/legal/advice'), null, Sitemap::YEARLY, 0.1);
        // добавление транспорта
        //$sitemap->addItem($baseUrl.Url::toRoute('/account/signup-transport/'), null, Sitemap::MONTHLY, 0.3);
        // Подписка
        //$sitemap->addItem($baseUrl.Url::toRoute('/sub/'), null, Sitemap::YEARLY, 0.1);
    }

    protected function baseLocation(&$sitemap, $baseUrl, LocationInterface $location = null)
    {
        if($location){
            if($this->matrixContentService->isEnoughContentAnyDirection('main', $location)) {
                $sitemap->addItem($baseUrl . '/' . $location->getCode() . '/', null, Sitemap::MONTHLY, 0.9);
            }
        }else {
            //базовый УРЛ
            $sitemap->addItem($baseUrl, null, Sitemap::MONTHLY, 0.9);
        }
    }
}
