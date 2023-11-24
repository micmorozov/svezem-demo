<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 09.11.18
 * Time: 12:03
 */

namespace frontend\modules\tk\models;

use yii\sphinx\ActiveRecord;

/**
 * @property Tk $tk
 * @property TkDetails $details
 */

class SphinxTk extends ActiveRecord
{
    public static function indexName()
    {
        return 'svezem_tk';
    }

    public function getSnippetSource()
    {
        return $this->tk->describe;
    }

    public function getTk()
    {
        return $this->hasOne(Tk::class, ['id' => 'id']);
    }
}