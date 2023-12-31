# Формирование тэгов на страницах сайта svezem.ru

#### Тэги на странице груза `/cargo/<cargoid>/`

Тэги на странице груза `/cargo/<cargoid>/` берутся из таблицы `cargo_tags` и генерируются воркером `UpdateCargoTags` при добавлении или изменении груза. Либо командой `php yii cargo/tags-update`, которая
перегенерирует теги для всех грузов или для какого-то конкретного, переданного в параметре

При генерации тегов используются города отправки и доставки груза и категории груза. К этому добавляется доставка груза по России.
При генерации тегов используются шаблоны `cargo-transportation-view` и `main` для страны и города и ведут либо на `/cargo/transportation/<ЧПУ>/` либо на главную (`*.svezem.ru`)

### Тэги на странице поиска грузов `/cargo/search/` и странице со списком всех фильтров `/cargo/search/all/`

Тэги на страницах `/cargo/search/` и `/cargo/search/all/` берутся из таблицы `cargo_search_tags` (для `/cargo/search/` берется только ограниченное количество тэгов, например 10) и генерируются в кроне командой

`php yii cargo/search-tags-generate` с периодом __раз в сутки__

Генерция тегов основывается на шаблонах страниц `cargo-search-inside-country-view`, `cargo-search-from-city-view`, `cargo-search-to-city-view`,
`cargo-search-from-to-city-view`, т.е. перевозка по стране, из города, в город, из города в город.
Город берется из города домена, при необходимости пересекается с городами в которых работает сервис `fast_city`.

При генерации тегов с видами перевозки используются только те виды, для которых прописан шаблон страниц в админке.

Подробнее смотри код генератора.

При клике на тот или иной тэг происходит переход на страницу `/cargo/search/<ЧПУ>/`, которая представляет собой страницу поиска груза с предустановленными параметрами фильтра

### Тэги на странице списка видов перевозки `/cargo/transportation/`

Генерируются на самой странице. В генерации участвует шаблон `cargo-transportation-view` и все имеющиеся виды перевозок в пересечении с городом домена.

При клике на тот или иной тэг происходит переход на страницу `/cargo/transportation/<ЧПУ>/`, которая представляет собой главную страницу на которую накладывается фильтр из УРЛ. Т.е. отображаются только те грузы, перевозчики и ТК, которые
в городе домена и с видом перевозки `<ЧПУ>`

### Тэги на странице направлений перевозки `/intercity/`

__Этот адрес доступен только в поддомене.__

Генерируются на самой странице. В генерации участвуют все имеющиеся города в пересечении с городом домена.

При клике на тот или иной тэг происходит переход на страницу `/intercity/<city>/`, которая представляет собой главную страницу на которую накладывается фильтр из УРЛ. Т.е. отображаются только те грузы, перевозчики и ТК, которые
перевозят из города домена в город `<city>`

### Тэги на странице `/intercity/<city>/all/`

__Этот адрес доступен только в поддомене.__

Генерируются на самой странице. В генерации участвуют все имеющиеся виды перевозок по выбранному направлению(Т.е. город домена, город урла `<city>`)

При клике на тот или иной тэг происходит переход на страницу `/intercity/<city>/<ЧПУ>/`, которая представляет собой главную страницу на которую накладывается фильтр из УРЛ. Т.е. отображаются только те грузы, перевозчики и ТК, которые
перевозят из города домена в город `<city>` и у которых имеется вид перевозки `<ЧПУ>`

#### Тэги на странице перевозчика `/transporter/<transporterid>/`

Тэги на странице перевозчика `/transporter/<transporterid>/` берутся из таблицы `transporter_tags` и генерируются воркером `UpdateTransporterTags` при добавлении или изменении транспорта. Либо командой `php yii transporter/tags-update`, которая
перегенерирует теги для всех перевозчиков или для какого-то конкретного, переданного в параметре

При генерации тегов используются виды перевозок с которыми работал перевозчик, т.е. те виды, которые указаны у его транспорта

#### Тэги на странице поиска перевозчика `/transport/search/` и странице со списком всех фильтров `/transport/search/all/`

Тэги на страницах `/transport/search/` и `/transport/search/all/` берутся из таблицы `transport_search_tags` (для `/transport/search/` берется только ограниченное количество тэгов, например 10) и генерируются в кроне командой

`php yii transporter/search-tags-generate` с периодом __раз в сутки__

Генерция тегов основывается на шаблонах страниц `transport-search-inside-city-view`, `transport-search-from-city-view`, `transport-search-to-city-view`,
`transport-search-from-to-city-view`, т.е. перевозка по городу, из города, в город, из города в город.
Город берется из города домена, при необходимости пересекается с городами в которых работает сервис `fast_city`

При генерации тегов с видами перевозки используются только те виды, для которых прописан шаблон страниц в админке.

Подробнее смотри код генератора.

При клике на тот или иной тэг происходит переход на страницу `/transport/search/<ЧПУ>/`, которая представляет собой страницу поиска перевозчика с предустановленными параметрами фильтра

#### Тэги на странице ТК `/tk/<tkid>/`

Теги генерируруются на самой странице.

Для отображения тегов используются виды перевозок, указанные у самой ТК.

#### Тэги на странице поиска ТК `/tk/search/` и странице со списком всех фильтров `/tk/search/all/`

##### ВНИМАНИЕ!!! Пока от этих тегов отказались и убрали их в интерфейсе

Тэги на страницах `/tk/search/` и `/tk/search/all/` берутся из таблицы `tk_search_tags` (для `/tk/search/` берется только ограниченное количество тэгов, например 10) и генерируются в кроне командой

`php yii tk/search-tags-generate` с периодом __раз в сутки__

Генерция тегов основывается на шаблонах страниц `tk-search-category-view`, т.е. транспортные компании в городе и транспортные компании в городе по перевозке `<категория>`.
Город берется из города домена. Без города теги не генерятся

При генерации тегов с видами перевозки используются только те виды, для которых прописан шаблон страниц в админке.

Подробнее смотри код генератора.

При клике на тот или иной тэг происходит переход на страницу `/tk/search/<ЧПУ>/`, которая представляет собой страницу поиска перевозчика с предустановленными параметрами фильтра

#### Тэги на странице Статей `/articles/`
Тэги берутся из таблицы `article_tags` и генерируются в кроне командой
`php yii article-tags/generate`

#### Актуализация ссылок

Ссылки на страницы с малым количеством контента не выводятся. Для того что бы это работало используется "Матрица контента", которая содержит направление, категорию и
количество записей для груза, ТК и перевозчиков. Матрица заполняется по крону

`php yii matrix-content/build` с установленным интервалом.

Дополнительно к этому на самих странцах с контентом делается проверка на количество элементов. Если их недостаточно, то устанавливается noindex, что бы
поисковый робот не индексировал эту страницу

#### Запуск сборщика ресурсов
`composer build-assets

Все js и css собираются в один файл. js -> /js/all.js, css -> /css/screen.css
