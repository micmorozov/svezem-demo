<?php

//UrlManager необходим для веб
//НО! в консоли происходит генерация sitemap
//поэтому его следут добавить в оба конфига

use common\components\SvezemUrlManager;
use frontend\components\urlRules\CategoryUrlRule;
use frontend\components\urlRules\IntercityUrlRule;
use frontend\components\urlRules\LocationUrlRule;
use frontend\components\urlRules\RobotsRule;
use yii\web\UrlRule;

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
    || !empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443
    || (PHP_SAPI === 'cli')) ? "https://" : "http://";

return [
    'class' => SvezemUrlManager::class,
    'baseUrl' => '',
    'enablePrettyUrl' => true,
    'showScriptName' => false,
    'suffix' => '/',
    'normalizer' => [
        'class' => 'yii\web\UrlNormalizer',
        'action' => yii\web\UrlNormalizer::ACTION_REDIRECT_PERMANENT
    ],
    'rules' => [
        //необходимо при переключении Регионы/Города
        '/' => '/site/index/',

        // Обработка СТАРОГО запроса /transporter/7 и /cargo/7
        '<_c:(cargo|transporter|tk)>/<id:\d+>' => '<_c>/default/view',

        // Обработка запроса /transporter/7/slug и /cargo/7/slug, /tk/7/slug
        [
            'pattern' => '<_c:(cargo|transporter|tk)>/<slug:[\w-]+>-<id:\d+>',
            'route' => '<_c>/default/view2',
            'defaults' => [
                'slug' => ''
            ]
        ],
       // '<_c:(cargo|transporter|tk)>/<slug:[\w-]+>-<id:\d+>' => '<_c>/default/view2',

        //страница сгенерированных фильтров
        // ВНИМАНИЕ!! Последовательность важна!
        '<_c:(cargo|transport|tk)>/search/all' => '<_c>/search/all',

        // https://svezem.ru/tk/5/
        //$protocol.Yii::getAlias('@domain').'/tk/<id:\d+>' => 'tk/default/view',
        'tk/price-comparison' => 'tk/comparison/index',

        // Укорачиватель урлов для груза. Обязательно должен следовать после 'cargo/<id:\d+>/<slug>.html' принимает как русскую так и латинскую с
        '<c|с|s|g><id:\d+>' => 'cargo/default/view-redir',

        '/cargo/booking' => 'cargo/booking/index',
        '/cargo/booking/<_a>' => 'cargo/booking/<_a>',

        ////////////////////////////////////////
        // Обработка запроса contacts
        'contacts' => 'info/default/contacts',
        ////////////////////////////////////////

        //'/info/<_c:[\w-]+>/<_a:[\w-]+>' => 'info/<_c>/<_a>',
        '/cargo/passing/' => 'cargo/default/passing',
        //подписка сокращенный УРЛ для перехода из СМС
        [
            'class' => LocationUrlRule::class,
            'pattern' => 'sub',
            'route' => 'sub/default/index'
        ],
        //Сокращенная ссылка на справку о подписках
        [
            'pattern' => 'i/sub',
            'route' => 'info/subscribe/',
            'mode' => UrlRule::PARSING_ONLY
        ],

        //omsk.svezem.ru/intercity
        "intercity/<cityTo:[\w-]+>" => 'intercity/default/transportation',
        "intercity/<cityTo:[\w-]+>/all" => 'intercity/default/alltags',
        "intercity/<cityTo:[\w-]+>/<slug:[\w-]+>" => 'intercity/default/search',

        //////////// СТАТЬИ /////////////////////////
        "/article/<slug:[\w-]+>" => 'articles/default/view',

        "/articles/<slug:[\w-]+>/<page:[\d]+>" => 'articles/default/index',
        "/articles/<page:[\d]+>" => 'articles/default/index', // С номером страницы в пути адреса
        "/articles/<slug:[\w-]+>" => 'articles/default/index',
        //это правило влияет на корректный адрес при пейджировании
        '/articles' => 'articles/default/index',
        /////////////////////////////////////////////

        //'<_c>/default/<_a>',
        'r/<code:\w{6}>' => 'r/index',

        [
            'class' => RobotsRule::class,
            'pattern' => 'robots.txt',
            'route' => 'robots/index'
        ],

        [
            'pattern' => 'city/<char:\w>',
            'route' => 'city/index',
            'defaults' => [
                'char' => null
            ]
        ],

        //////////////////////////////////
        // Поиск грузов, перевозчиков и тк
        [
            'class' => LocationUrlRule::class,
            'pattern' => '<location:[\w\d-]+>/<module:(cargo|transport|tk)>/search/<slug:[\w\.-]+>',
            'route' => '<module>/search/index',
            'defaults' => [
                'location' => '',
                'slug' => ''
            ]
        ],
        ////////////////////////////////////

        //omsk.svezem.ru/cargo/transportation
        'cargo/transportation/<slug:[\w\.-]+>' => 'cargo/transportation/search',
        [
            'class' => LocationUrlRule::class,
            'pattern' => '<location:[\w\d-]+>/cargo/transportation',
            'route' => 'cargo/transportation',
            'defaults' => [
                'location' => ''
            ]
        ],

        '<_c:(account|tk|transport|cargo)>/<_a>' => '<_c>/default/<_a>',

        // Обработка междугородних перевозок
        [
            'class' => LocationUrlRule::class,
            'pattern' => '<location:[\w\d-]+>/intercity',
            'route' => 'intercity/default/index',
            'defaults' => [
                'location' => ''
            ]
        ],

        // Обработка страниц с картой сайта
        [
            'class' => LocationUrlRule::class,
            'pattern' => '<location:[\w\d-]+>/info/sitemap',
            'route' => 'info/sitemap/index',
            'defaults' => [
                'location' => ''
            ]
        ],

        // Обработка междугородних перевозок
        [
            'class' => IntercityUrlRule::class,
            'pattern' => '<root:[\w-]+>/<cityFrom:[\w\d-]+>-to-<cityTo:[\w\d-]+>',
            'route' => 'intercity/default/transportation2'
        ],

        // Обработка междугородних перевозок
        [
            'class' => LocationUrlRule::class,
            'pattern' => 'cabinet/cargo-booking',
            'route' => 'cabinet/cargo-booking/index'
        ],


        // Обработка категорий
        // При создании урл можно использовать параметр city и slug
        [
            'class' => CategoryUrlRule::class,
            'pattern' => '<slug:[\w\d-\/]+>',
            'route' => 'cargo/transportation/search2'
        ],

        'location/<location:\d+>/categories' => 'locationselector/default/categories' ,
/*
        //страница сгенерированных фильтров
        // ВНИМАНИЕ!! Последовательность важна!
        '<_c:(cargo|transport|tk)>/search/all' => '<_c>/search/all',
        '<_c:(cargo|transport|tk)>/search/<pg:[\d]+>' => '<_c>/search/index', // Номер страницы в пути
        '<_c:(cargo|transport|tk)>/search/<slug:[\w\.-]+>' => '<_c>/search/index',
        '<_c:(cargo|transport|tk)>/search' => '<_c>/search/index',

        ////////////////////////////////////////
        // transporter/2
        //'<_c:(transporter)>/<id:\d+>/<slug:[\w\.-]+>' => '<_c>/default/view',
        ///////////////////////////////////////////////

        // https://svezem.ru/tk/5/
        $protocol.Yii::getAlias('@domain')."/tk/<id:\d+>" => 'tk/default/view',
        'tk/price-comparison' => 'tk/comparison/index',

        // Обработка запроса https://omsk.svezem.ru/transporter/7/ и https://omsk.svezem.ru/cargo/7/
        [
            'pattern' => "{$protocol}<city>.".Yii::getAlias('@domain')."/<_c:(cargo|transporter)>/<id:\d+>",
            'route' => '<_c>/default/view',
            'mode' => UrlRule::CREATION_ONLY
        ],
        // Обрабатывает запросы на страницы груза и перевозчика без домена
        '<_c:(cargo|transporter)>/<id:\d+>' => '<_c>/default/view',

        // Укорачиватель урлов для груза. Обязательно должен следовать после 'cargo/<id:\d+>/<slug>.html' принимает как русскую так и латинскую с
        '<c|с|s|g><id:\d+>' => 'cargo/default/view-redir',

        $protocol.Yii::getAlias('@domain')."/cargo/booking" => 'cargo/booking/index',
        $protocol.Yii::getAlias('@domain')."/cargo/booking/<_a>" => 'cargo/booking/<_a>',

        //omsk.svezem.ru/intercity
        "intercity/<cityTo:[\w-]+>" => 'intercity/default/transportation',
        "intercity/<cityTo:[\w-]+>/all" => 'intercity/default/alltags',
        "intercity/<cityTo:[\w-]+>/<slug:[\w-]+>" => 'intercity/default/search',

        //omsk.svezem.ru/cargo/transportation
        'cargo/transportation' => 'cargo/transportation',
        'cargo/transportation/<slug:[\w\.-]+>' => 'cargo/transportation/search',

        ////////////////////////////////////////
        // Обработка запроса contacts
        'contacts' => 'info/default/contacts',
        ////////////////////////////////////////

        $protocol.Yii::getAlias('@domain')."/account/<_a:[\w-]+>" => 'account/default/<_a>',
        $protocol.Yii::getAlias('@domain')."/info/<_c:[\w-]+>/<_a:[\w-]+>" => 'info/<_c>/<_a>',
        $protocol.Yii::getAlias('@domain')."/cargo/passing/" => 'cargo/default/passing',
        //подписка сокращенный УРЛ для перехода из СМС
        $protocol.Yii::getAlias('@domain')."/sub/" => 'sub/default/index',

        //Сокращенная ссылка на справку о подписках
        [
            'pattern' => 'i/sub',
            'route' => 'info/subscribe/',
            'mode' => UrlRule::PARSING_ONLY
        ],

        //////////// СТАТЬИ /////////////////////////
        [
            'pattern' => "{$protocol}<city>.".Yii::getAlias('@domain')."/article/<slug:[\w-]+>",
            'route' => 'articles/default/view',
            'mode' => UrlRule::CREATION_ONLY
        ],
        "/article/<slug:[\w-]+>" => 'articles/default/view',

        "/articles/<slug:[\w-]+>/<page:[\d]+>" => 'articles/default/index',
        "/articles/<page:[\d]+>" => 'articles/default/index', // С номером страницы в пути адреса
        "/articles/<slug:[\w-]+>" => 'articles/default/index',
        //это правило влияет на корректный адрес при пейджировании
        '/articles' => 'articles/default/index',
        /////////////////////////////////////////////

        '<_c:(account|tk|transport|cargo)>/<_a>' => '<_c>/default/<_a>',

        '<_c>/default/<_a>',
        'r/<code:\w{6}>' => 'r/index',

        [
            'class' => 'common\components\RobotsRule'
        ],
        'city/<char:\w>' => 'city'
*/
    ],
];
