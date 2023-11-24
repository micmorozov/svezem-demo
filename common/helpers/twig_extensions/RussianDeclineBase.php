<?php

namespace common\helpers\twig_extensions;

use morphos\Cases;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

abstract class RussianDeclineBase extends AbstractExtension
{
    protected $filters = [];

    /**
     * RussianDeclineBase constructor.
     * @param array $filters
     */
    public function __construct($filters = []){
        //фильтр "падеж" это базовый фильтр
        $this->filters = array_merge([
            new TwigFilter('padezh', [$this, 'padezhFilter'])
        ], $filters);
    }

    /**
     * @return array|TwigFilter[]
     */
    public function getFilters(){
        return $this->filters;
    }

    /**
     * @param $text
     * @param $case
     * @return mixed
     */
    abstract protected function padezhMethod($text, $case);

    /**
     * @param $text
     * @param string $case
     * @return mixed
     */
    public function padezhFilter($text, $case = 'im'){
        return $this->padezhMethod($text, $this->getCase($case));
    }

    /**
     * @param $case
     * @return mixed|string
     */
    protected function getCase($case){
        $cases = [
            'im' => Cases::NOMINATIVE,
            'rod' => Cases::GENITIVE,
            'dat' => Cases::DATIVE,
            'vin' => Cases::ACCUSATIVE,
            'tvo' => Cases::ABLATIVE,
            'pre' => Cases::PREPOSITIONAL
        ];

        return isset($cases[$case]) ? $cases[$case] : Cases::NOMINATIVE;
    }
}