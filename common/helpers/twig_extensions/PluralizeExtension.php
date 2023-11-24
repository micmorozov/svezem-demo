<?php
namespace common\helpers\twig_extensions {

    use Twig\Extension\AbstractExtension;
    use Twig\TwigFilter;

    class PluralizeExtension extends AbstractExtension
    {
        /**
         * @return array|TwigFilter[]
         */
        public function getFilters()
        {
            return [
                new TwigFilter('pluralize', 'twig_pluralize_filter')
            ];
        }
    }
}

namespace {

    use Twig\Error\SyntaxError;

    /**
     * @param int $number
     * @param array $titles
     * @return string
     */
    function twig_pluralize_filter(int $number, array $titles = []){
        if (count($titles) != 3) {
            throw new SyntaxError('There should be 3 spellings');
        }

        $cases = [2, 0, 1, 1, 1, 2];
        return $number . ' ' . $titles[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[($number % 10 < 5) ? $number % 10 : 5]];
    }
}