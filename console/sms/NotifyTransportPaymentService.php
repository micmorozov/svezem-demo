<?php
/** @var int $daysBeforeEnd */
/** @var Transport $transport */
/** @var string $url */

use common\models\Transport; ?>
Через <?= $daysBeforeEnd ?> дня оплаченные услуги для транспорта <?= $transport->direction ?> завершатся. Продлить <?= $url ?>