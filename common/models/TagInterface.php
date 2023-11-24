<?php
/**
 * Общий интерфейс для разных тегов
 */
namespace common\models;

interface TagInterface
{
    public function getTitle(): string;

    public function getUrl(): string;
}
