<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 11.09.18
 * Time: 10:46
 */

namespace common\helpers;


use common\models\CargoCategory;

class CategoryHelper
{
    /**
     * Ф-ция получает ИД категорий транспорта и возвращает
     * категории груза, воходящие в указанные категории
     * @param $ids
     * @return CargoCategory[]
     */
    static public function transportToCargo($ids){
        /** @var CargoCategory[] $categories */
        $categories = CargoCategory::findAll($ids);

        $result = [];
        foreach($categories as $category){
            if( $category->show_moder_cargo )
                $result[] = $category;

            foreach($category->nodes as $node){
                if( $node->show_moder_cargo )
                    $result[] = $node;
            }
        }

        return $result;
    }

    /**
     * Из массива категорий строим массив slug
     * @param array $categories
     */
    public static function categoryToLineSlug(array $categories): array
    {
        return array_map(function(CargoCategory $item) {
            return $item->slug;
        }, $categories);
    }

    /**
     * Для переденной категории строим полный список родительских категорий включая переданную.
     * Если у категории несколько предков, берем первый попавшийся
     * @param CargoCategory $cargoCategory
     * @return array Массив родительских категорий
     */
    public static function buildFullParentSlug(CargoCategory $cargoCategory): array
    {
        $result[] = $cargoCategory;
        $parents = $cargoCategory->parents;
        if($parents){
            $parent = array_shift($parents);
            array_unshift($result, ...self::buildFullParentSlug($parent));
        }

        return $result;
    }
}