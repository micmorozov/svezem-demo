<?php

namespace console\helpers\tomita\parsers;

use common\models\CargoCategory;

class CategoriesParser
{
    private $tomitaData = null;

    private $_categoriesId = null;

    private $_processResult = null;

    public function __construct($data)
    {
        $this->tomitaData = json_decode($data, 1);
    }

    /**
     * @return array|null
     */
    public function getCategoriesId()
    {
        if (!$this->_categoriesId) {
            if (!$this->tomitaData) {
                return null;
            }

            $data = $this->tomitaData;

            $facts = $data[0]['FactGroup'][0]['Fact'];

            //Определяем категории
            $categories = array_map(function ($item) {
                $catName = $item['Field'][0]['Name'];

                if (preg_match('/Category_(\d+)/', $catName, $match)) {
                    return $match[1];
                }

            }, $facts);

            $categories = array_unique($categories);
            $this->_categoriesId = $this->filter($categories);
        }

        return $this->_categoriesId;
    }

    /**
     * @param array $categories
     * @return array
     */
    private function filter(array $categories)
    {
        // Если определены категории
        // 18 - Вывоз мебели и хлама
        // или
        // 36 - Вывоз диванов,
        // то исключаем категории
        // 3 - Перевозка вещей
        // 47 - Перевозка мебели
        // 49 - Перевозка дивана
        if (in_array(18, $categories) || in_array(36, $categories)) {
            $categories = array_diff($categories, [3, 47, 49]);
        }

        // Если определены категории
        // 37 - Вывоз пианино,
        // то исключаем категории
        // 48 - Перевозка пианино
        if (in_array(37, $categories)) {
            $categories = array_diff($categories, [48]);
        }

        // Если определены категории
        // 95 - Аренда экскаватора,
        // то исключаем категории
        // 27 - Перевозка экскаваторов
        if (in_array(95, $categories)) {
            $categories = array_diff($categories, [27]);
        }

        // Если определены категории
        // 96 - Аренда погрузчика,
        // то исключаем категории
        // 27 - Перевозка погрузчиков
        if (in_array(96, $categories)) {
            $categories = array_diff($categories, [28]);
        }

        return $categories;
    }

    /**
     * @return array|null
     */
    public function process()
    {
        if (!$this->tomitaData) {
            return null;
        }

        if (!$this->_processResult) {
            /** @var CargoCategory[] $categories */
            $categories = CargoCategory::find()
                ->select('id, category')
                ->where(['id' => $this->getCategoriesId()])
                ->all();

            $catsName = [];
            foreach ($categories as $category) {
                $catsName['Category_' . $category->id] = [
                    'id' => $category->id,
                    'name' => $category->category,
                    'words' => []
                ];
            }

            $facts = $this->tomitaData[0]['FactGroup'][0]['Fact'];

            //Список категорий, ключевых слов данной категории
            //позиция слова в тексте
            $cats = [];
            foreach ($facts as $index => $fact) {
                $catNameId = $fact['Field'][0]['Name'];

                if (!isset($catsName[$catNameId])) {
                    continue;
                }

                $words = $catsName[$catNameId]['words'];

                $words[] = [
                    'word' => $fact['Field'][0]['Value'],
                    'TextPos' => $fact['Attr']['TextPos'],
                    'TextLen' => $fact['Attr']['TextLen']
                ];

                $catsName[$catNameId]['words'] = $words;
            }

            $this->_processResult = [
                'categories' => $catsName
            ];
        }

        return $this->_processResult;
    }
}
