<?php


namespace stock\tasks;


use pocketmine\Server;
use stock\Loader;
use stock\utils\StockExchange;

class GraphicTask extends \pocketmine\scheduler\Task
{

    public function onRun($currentTick)
    {
        $rand = ceil(mt_rand(0, 100));
        StockExchange::setPercentageValue($rand);

        foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
            $onlinePlayer->sendMessage(str_ireplace('{VALOR}', $rand, Loader::$config['stock-change-value-message']));
        }
    }
}