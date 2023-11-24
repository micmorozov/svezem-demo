<?php
/* @var $this yii\web\View */
/* @var $dataProvider ActiveDataProvider */
/* @var $searchModel ResponseSearch*/

use frontend\modules\cabinet\models\ResponseSearch;
use yii\bootstrap\ActiveForm;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\widgets\LinkPager;
use yii\widgets\ListView;
use yii\widgets\Pjax;

$this->title = "Личный кабинет - отклики на заявки"
?>

  <h2>Отклики на заявки</h2>

  <?php Pjax::begin() ?>

  <div class="bl-white responses">

    <?php ActiveForm::begin([
      'action' => ['/cabinet/responses'],
      'method' => 'get',
      'options' => ['data-pjax' => '1'],
      'id' => 'responses-form',
    ]); ?>

    <?php
    $get = Yii::$app->request->get('ResponseSearch');

    if (!empty($get['statuses'])) {
      foreach ($get['statuses'] as $status_id) {
        echo Html::input('hidden', 'ResponseSearch[statuses][]', $status_id, ['id' => 'hidden-status-' . $status_id]);
      }
    }

    if (!empty($get['pageSize'])) {
      echo Html::input('hidden', 'ResponseSearch[pageSize]', (int)$get['pageSize'], ['class' => 'response-hidden']);
    }

    function isTabActive($id) {
      $get = Yii::$app->request->get('ResponseSearch');
      if (!empty($get['statuses'])) {
        foreach ($get['statuses'] as $status_id) {
          if ($id == $status_id) return true;
        }
      }

      return false;
    }
    ?>

    <div class="ui-tabs">
      <div class="pjax-loader"></div>
      <ul class="ui-tabs-nav">
        <?php foreach(ResponseSearch::getStatusLabels() as $id => $statusLabel): ?>
          <li class="<?php echo (isTabActive($id)) ? 'ui-tabs-active' : '' ?>">
            <a class="ui-tabs-anchor" href="#" data-id="<?= $id ?>"><?= $statusLabel ?></a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <?php ActiveForm::end(); ?>

    <div style="padding: 0 15px;">
      <?php
      echo ListView::widget( [
        'dataProvider' => $dataProvider,
        'itemView' => '_response_item',
        'itemOptions' => [
          'tag' => false
        ],
        'options' => [
          'tag' => 'div',
          'class' => 'ui-tabs-panel'
        ],
        'layout' => "{items}"
      ]);
      ?>
    </div>


  </div>

  <?= LinkPager::widget([
    'pagination' => $dataProvider->getPagination(),
    'firstPageLabel' => 'Первая',
    'lastPageLabel' => 'Последняя',
    'prevPageLabel' => false,
    'nextPageLabel' => false,
  ]) ?>

  <div class="quantity-pages">
    <span>Показывать по:</span>
    <?= Html::dropDownList('', !empty($get['pageSize']) ? $get['pageSize'] : 10, ['10' => '10', '20' => '20', '30' => '30'], ['class' => 'selectbox', 'id' => 'select-per-page']) ?>
  </div>

  <?php Pjax::end() ?>

<?php
$this->registerJs("
  $(document).on('click', 'a.ui-tabs-anchor', function(a){
    if($(this).parent().hasClass('ui-tabs-active')) {
        $('#hidden-status-' + $(this).data('id')).remove();
    }
    else {
      $(this)
        .closest('form')
        .prepend('<input type=\'hidden\' value=\'' + $(this).data('id') + '\' name=\'ResponseSearch[statuses][]\'>');
    }
    $(this).closest('form').submit();
    $('div.pjax-loader').show();
    return false;
  });

  $(document).on('change', '#select-per-page', function(a){
    $('input.response-hidden').remove();
    $('#responses-form')
      .prepend('<input type=\'hidden\' value=\'' + $(this).find('option:selected').val() + '\' name=\'ResponseSearch[pageSize]\'>')
      .submit();
    return false;
  });

  $(document).on('pjax:success', function(){
      $('#select-per-page').styler();
  });
");