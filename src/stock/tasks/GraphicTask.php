<?php


namespace stock\tasks;


use pocketmine\Server;
use stock\Loader;
use stock\utils\StockExchange;

class GraphicTask extends \pocketmine\scheduler\Task
{

    /** @var int[] */
    protected $lastValues = [];

    public function onRun($currentTick)
    {
        $this->lastValues[] = $value = ceil(mt_rand(Loader::$config['min-percentage'] ?? 1, Loader::$config['max-percentage'] ?? 100));

        foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
            $onlinePlayer->sendMessage(str_ireplace('{VALOR}', $value, Loader::$config['stock-change-value-message']));
        }
        StockExchange::setPercentageValue($value);
    }
}