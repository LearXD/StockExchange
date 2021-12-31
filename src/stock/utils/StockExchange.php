<?php


namespace stock\utils;


use pocketmine\item\Item;

final class StockExchange
{

    protected static $dataValues = [];
    protected static $value = 100;

    public static function init(array $dataValues) {
        return self::$dataValues = $dataValues;
    }

    public static function setPercentageValue(int $percentage): int {
        return self::$value = $percentage;
    }

    public static function getPercentageValue(): int {
        return self::$value;
    }


    public static function getPriceFromItem(Item $item) {
        if(isset(self::$dataValues[self::itemToString($item)])) {
            $data = self::$dataValues[self::itemToString($item)];
            return ((self::getPercentageValue() * $data['price']) / 100) * $item->getCount();
        }
        return false;
    }

    public static function itemToString(Item $item) {
        return $item->getId() . ':' . $item->getDamage();
    }

}