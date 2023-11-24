<?php
return [
	// Список превьюшек для транспорта с их размерами
	'profileThumbnails' => [
		// В личкабе
		'profile' => [
			'width' => 100,
			'height' => 100,
			'quality' => 80
		],

		// Используется в отзывах на странице груза
		'review' => [
			'width' => 130,
			'height' => 130,
			'quality' => 80
		],

		// Используется в переписке
		'message' => [
			'width' => 50,
			'height' => 50,
			'quality' => 80
		]
	],

    'user.passwordResetTokenExpire' => 3600,
    'enableRestrictionOnDisposableEmails' => false,

	// Показывать кпапчу после 5 неудачных попыток
	'showCaptchaAfterNTries' => 5,
    'userAgent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36',

    // Количество элементов на странице
    'itemsPerPage' => [
        'defaultPageSize' => 20,
        'pageSizes' => ['20' => 20, '30' => 30, '50' => 50]
    ]
];
