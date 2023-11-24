<?php
use common\models\Cargo;
use common\models\PaymentSystem;
use common\models\ServiceRate;
use common\models\Transport;
use common\models\TransportLocation;
use yii\helpers\ArrayHelper;
use yii\bootstrap\Html;
use yii\helpers\Url;

/* @var integer $payment_id */
/* @var integer $cargo_id */
/* @var integer $transport_id */
/* @var ServiceRate[] $serviceRates */
/* @var PaymentSystem[] $paymentSystems */
/* @var Cargo[] $cargos */
/* @var Transport[] $transports */
$selection = null;
if ($cargo_id){
  $selection = "#cargo-" . $cargo_id . "-styler";
}
else if ($transport_id) {
  $selection = "#transport-" . $transport_id . "-styler";
}
$this->title = "Личный кабинет - Платные услуги";
?>

  <h2>Платные услуги</h2>

  <div class="bl-white messages">
    <div class="item-service">
      <div class="icon-service">
        <img src="/images/icon-top.png" alt="">
      </div>
      <div class="info-service">
        <p class="title">
          <b>Закрепить в топ</b> &mdash; <span>Больше просмотров - больше откликов и предложений</span>
        </p>
        <p>
          Объявления отмеченные данной услугой будут размещены в первых местах поиска по соответствующему направлению перевозки.
        </p>
        <p class="number-step">
          <b>1. Выберите объявление, которе будет закреплено в первых местах поиска</b>
        </p>
        <div class="select-block">
          <select id="dd-items-list" class="selectbox">
            <?php if(count($cargos)): ?>
              <?php foreach($cargos as $cargo): ?>
                <option id="cargo-<?= $cargo->id ?>" value="cargo-<?= $cargo->id ?>"><?= e($cargo->name); ?></option>
              <?php endforeach; ?>
            <?php endif; ?>

            <?php if(count($transports)): ?>
              <?php foreach($transports as $transport): ?>
                <option id="transport-<?= $transport->id ?>" value="transport-<?= $transport->id ?>"><?= e($transport->name); ?></option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
          <div class="container-select">

            <?php if(count($cargos)): ?>
              <?php foreach($cargos as $cargo): ?>
                <div class="select-item cargo-<?= $cargo->id ?>">
                  <div class="item-left">
                    <?php
                    $loadLocations = $cargo->cargoLocationsFrom;
                    $loadLocations = array_shift($loadLocations);                                       
                    $loadLocations = '<i class="icon-flag flag-' . $loadLocations->city->country->code . '"></i> ' . $loadLocations->city->title_ru;                    

                    $unloadLocations = $cargo->cargoLocationsTo;
                    $unloadLocations = end($unloadLocations);                    
                    $unloadLocations = '<i class="icon-flag flag-' . $unloadLocations->city->country->code . '"></i> ' . $unloadLocations->city->title_ru;
                    $date = ($cargo->is_any_date == Cargo::ANY_DATE) ? "В любое время" : "с " . $cargo->date_from . " по " . $cargo->date_to;
                    ?>
                    <p>
                      <b>Груз:</b> <?= $loadLocations ?> &nbsp;&mdash;&nbsp; <?= $unloadLocations ?>
                    </p>
                    <p>
                      <span>
                        <b><?= $cargo->name ?></b>
                      </span>                      
                    </p>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>

            <?php if(count($transports)): ?>
              <?php foreach($transports as $transport): ?>
                <div class="select-item transport-<?= $transport->id ?>">
                  <div class="item-left">
                    <?php
                    $loadLocations = $transport->getLocationsString(TransportLocation::TYPE_LOADING);
                    $unloadLocations = $transport->getLocationsString(TransportLocation::TYPE_UNLOADING);                                                            
                    $date = ($transport->is_any_date == Cargo::ANY_DATE) ? "В любое время" : "с " . $transport->date_from . " по " . $transport->date_to;
                    ?>
                    <p>
                      <b>Транспорт:</b> <?= $loadLocations ?> &nbsp;&mdash;&nbsp; <?= $unloadLocations ?>
                    </p>
                    <p>
                      <span>
                        <b><?= $transport->name ?></b>
                      </span>
                    </p>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>

          </div>
        </div>

        <p class="number-step">
          <b>2. Выберите период закрепления</b>
        </p>
        <div class="select-block service-rates">
          <select id="dd-service-rates" class="selectbox">
            <?php if(count($serviceRates)): ?>
              <?php foreach($serviceRates as $service_rate): ?>
                <option id="sr-<?= $service_rate->id ?>" value="<?= $service_rate->id ?>"></option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
          <div class="container-select">
            <?php if(count($serviceRates)): ?>
              <?php foreach($serviceRates as $service_rate): ?>
                <div class="select-item sr-<?= $service_rate->id?>">
                  <p>
                    <b>Закрепить на <?= $service_rate->amount?> д.</b>
                    (<b><?= number_format($service_rate->price/$service_rate->amount, 2, '.', ' ')?> руб. в день</b>)
                  </p>
                  <p>
                    Стоимость: <b class="price-blue"><span class="price-value"><?= number_format($service_rate->price, 2, '.', ' ')?></span> руб.</b>
                  </p>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <p class="number-step">
          <b>3. Выберите способ оплаты</b>
        </p>
        <div class="select-block">
          <select id="dd-payment-types" class="selectbox">
            <?php if(count($paymentSystems)): ?>
              <?php foreach($paymentSystems as $payment_system): ?>
                <option id="ps-<?= $payment_system->code ?>" value="<?= $payment_system->code ?>" data-rate="<?= $payment_system->rate ?>"></option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
          <div class="container-select">
            <?php if(count($paymentSystems)): ?>
              <?php foreach($paymentSystems as $payment_system): ?>
                <div class="select-item ps-<?= $payment_system->code?>">
                  <p>
                    <b><?= $payment_system->name?></b>
                  </p>
                  <p>
                    К оплате
                    <b class="price-blue"><span class="price-value"></span> <?= $payment_system->currency_short_name?></b>
                  </p>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
        <div>
          <?= Html::submitButton('Оплатить', ['class' => 'btn btn-blue', 'id'=>'paybtn']) ?> <div class="pjax-loader-blue"></div>
        </div>
        <div id="payment-container"></div>
      </div>
    </div>
  </div>

<?php
$this->registerJs("
  $(document).on('change', 'select', function(e){
    updateForm();
  });
  
  $('#paybtn').on('click', function(sender){      
    if($(this).hasClass('btn')){
      $(this).prop('disabled', true);
      $(this).addClass('btn-grey');
    }
    
    // Показываем лоадер
    $('div.pjax-loader-blue').css('display', 'inline-block');
     
    var form_id = $('#dd-payment-types').find('option:selected').val();
    var service_rate_id = $('#dd-service-rates').find('option:selected').val();
    var item = $('#dd-items-list').find('option:selected').val();
    
    var cargo_id = null;
    var transport_id = null;
    if (item !== undefined){
      var item_id = item.replace(RegExp('([^0-9])', 'g'), '');
      var pattern = new RegExp('cargo', 'g');
      if (pattern.test(item)){
        cargo_id = item_id;
      }
      pattern = new RegExp('transport', 'g');
      if (pattern.test(item)){
        transport_id = item_id;
      }
    }
    
   $.get('" . Url::toRoute(['/cabinet/service/get-ajax-form']) . "', {
      form_id: form_id,      
      service_rate_id: service_rate_id,
      cargo_id: cargo_id,
      transport_id: transport_id
    })
    .done(function( data ) {    
      $('#payment-container').html(data);
    });
  });

  $(function() {
    if ('" . $selection . "' != ''){
      $(this).find('" . $selection . "').trigger('click');
    }
    else {
      $('#dd-items-list').closest('div.select-block').find('li.selected').trigger('click');
    }
    updateForm();
  });

  function updateForm(){
    
    
    var item = $('#dd-items-list').find('option:selected').val();
    var price = $('div.service-rates li.selected').find('span.price-value').html();

    $.each($('#dd-payment-types option'), function( i, item ) {
      var converted_price = price;
      var rate = $(item).data('rate');
      if (rate != 1){
        converted_price = converted_price.replace(/ /g,'');
        converted_price = Math.round(parseFloat(converted_price) / parseFloat(rate) * 100 ) / 100;
        converted_price = converted_price.toString().split( /(?=(?:\d{3})+$)/ ).join(' ');
      }
      $('#ps-' + $(item).val() + '-styler span.price-value').html(converted_price);
      $('div.ps-' + $(item).val() + ' span.price-value').html(converted_price);
    });      
  }
");
