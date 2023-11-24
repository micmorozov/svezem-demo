<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 22.11.17
 * Time: 11:31
 */

namespace common\helpers\twig_extensions;

use Exception;
use function morphos\Russian\inflectName;

class NameRussianDecline extends RussianDeclineBase
{
    /**
     * @param $text
     * @param $case
     * @return false|mixed|string
     * @throws Exception
     */
    protected function padezhMethod($text, $case){
        return inflectName($text, $case);
    }
}