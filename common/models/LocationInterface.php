<?php


namespace common\models;


interface LocationInterface
{
    /**
     * Метод возвращает ИД локации: Города, Региона или Страны
     * @return int
     */
    public function getId(): int;

    /**
     * Метод возвращает код локации.
     * @example omsk или omskaya_oblast
     * @return string
     */
    public function getCode(): string;

    /**
     * Метод возвращает полное наименование локации.
     * Для города это город, регион, страна. Для региона это регион, страна и т.д.
     * @return string
     */
    public function getFullTitle(): string;

    /**
     * Метод возвращает короткое наименование локации.
     * Если это город, то наименование города, без региона и страны
     * @return string
     */
    public function getTitle(): string;

    /**
     * Метод возвращает массив родительских локаций включая текущую
     * @return array
     */
    public function getParentLocation(): array;

    /**
     * Метод сравнивает два объекта
     * @param LocationInterface $eq
     * @return bool
     */
    public function equal(LocationInterface $eq = null): bool;
}
