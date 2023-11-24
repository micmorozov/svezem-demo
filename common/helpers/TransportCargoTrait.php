<?php

namespace common\helpers;

use common\models\Cargo;
use common\models\Transport;
use Exception;
use morphos\Cases;
use morphos\Russian\GeographicalNamesInflection;

trait TransportCargoTrait
{
    /**
     * @return string
     * @throws Exception
     */
    public function getDirection():string{
        /** @var $this Transport|Cargo */
        if($this->city_from == $this->city_to){
            return "по ".GeographicalNamesInflection::getCase($this->cityFrom->title_ru, Cases::DATIVE);
        }

        return $this->cityFrom->title_ru." - ".$this->cityTo->title_ru;
    }
}