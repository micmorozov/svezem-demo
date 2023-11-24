<?php

namespace common\components\morphy;

use phpMorphy;

require_once __DIR__."/phpmorphy-0.3.7/src/common.php";

class Morphy
{
    private $morphy;

    public function __construct()
    {
        // Укажите путь к каталогу со словарями
        $dir = __DIR__."/dicts";
        $lang = 'ru_RU';

        // Список поддерживаемых опций см. ниже
        $opts = array(
            'storage' => PHPMORPHY_STORAGE_FILE,
        );

        $this->morphy = new phpMorphy($dir, $lang, $opts);
    }

    /**
     * @return phpMorphy
     */
    public function getMorphy(){
        return $this->morphy;
    }
}
