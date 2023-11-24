<?php
/** @var Transport $transport */
/** @var string $passwd */

use common\models\Transport; ?>
Здравствуйте!<br>
<br>
Спасибо, что воспользовались сервисом Svezem.ru<br>
<br>
Ваш транспорт <?= $transport->direction ?> размещен на svezem.ru. <br><br>
Ваши реквизиты доступа на сайт https://svezem.ru:<br>
E-mail: <?= $user->email ?><br>
Пароль: <?= $passwd ?><br>
<br>
С уважением,<br>
Команда сервиса по поиску грузов и перевозчиков Svezem.ru<br>
https://svezem.ru<br>
admin@svezem.ru<br>