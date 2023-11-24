<?php
use common\models\Payment;

/* @var $payment Payment*/
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <title></title>
</head>
<body>

<style>

  * {
    font-family: DejaVu Sans, serif;
  }
  table * {
    font-size: 13px;
  }
  table {
    border-collapse: collapse;
  }

  table.root > tbody > tr > td {
    border: 1px solid black;
  }

  hr {
    margin: -3px;
    padding: 0;
    border: 0;
    border-top: 1px solid black;
  }

  td {
    padding: 3px;
  }

  td {
    vertical-align: top;
  }

</style>

<table class="root" style="width: 100%">
  <tr>
    <td style="width: 70%">НОВОСИБИРСКИЙ ФИЛИАЛ ОАО "БАНК МОСКВЫ"
      <br />
      Г. НОВОСИБИРСК
      <br><br><br>      
          Банк получателя      
    </td>
    <td style="width: 5%">
      <p>БИК</p>
      <hr>
      <p>Сч. №</p>
    </td>
    <td style="width: 25%">
      <p>045004762</p>
      <hr>
      <p>30101810900000000762</p>
    </td>
  </tr>
  <tr>
    <td style="width: 70%">
      <div style="position: relative; width: 50%; border-right: 1px solid black; border-bottom: 1px solid black; float: left; padding: 10px 0">
        ИНН 2463082935
      </div>
      <div style="position: relative; width: 49%; border-bottom: 1px solid black; float: left; padding: 10px 0 10px 3px">
        КПП 246301001
      </div>
      <div style="clear: both"></div>
      <div style="position: relative; width: 100%; height: auto">
        <p>
          ООО "Иновика"
        </p>
        <p>
          Получатель
        </p>
      </div>
      <br>
      <br>
    </td>
    <td style="width: 5%">
      <p>Сч. №</p>
    </td>
    <td style="width: 25%">
      <p>40702810600600001848</p>
    </td>
  </tr>
</table>
<h3>
  <strong>
    Счёт №<?= $payment->id?> от <?= Yii::$app->formatter->asDate($payment->created_at, 'dd.MM.y');?>
  </strong>
</h3>

<hr>
<p>
  Поставщик:
  <strong>
    ИНН 2463082935 КПП 246301001 ООО "Иновика" 660130, г. Красноярск, а/я 28725, тел. +7(908)019-18-86
  </strong>
</p>
<p>
  Покупатель:
  <strong>
    <?= $payment->juridical_name . ", " . $payment->juridical_address?>
  </strong>
</p>
<br>
<table class="root" style="width: 100%">
  <tr>
    <td>
      №
    </td>
    <td>
      Товар
    </td>
    <td>
      Кол-во
    </td>
    <td>
      Ед.
    </td>
    <td>
      Цена
    </td>
    <td>
      Сумма
    </td>
  </tr>
  <tr>
    <td>
      1
    </td>
    <td>
      <?= $payment->serviceRate->service->name ?>
    </td>
    <td>
      <?= $payment->serviceRate->amount ?>
    </td>
    <td>
      Шт.
    </td>
    <td>
      <?= number_format($payment->serviceRate->price / $payment->serviceRate->amount, 2, '.', ' ')?>
    </td>
    <td>
      <?= number_format($payment->serviceRate->price, 2, '.', ' ') ?>
    </td>
  </tr>
  <tr>
    <td colspan="5">
      <strong>
        Итого
      </strong>
    </td>
    <td>
      <strong>
        <?= number_format($payment->serviceRate->price, 2, '.', ' ') ?>
      </strong>
    </td>
  </tr>
</table>
<br><br><br><br>
<b>
  Руководитель
</b>
____________________ (Морозов М.А.)
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<b>
  Бухгалтер
</b>
____________________
</body>
</html>