<?php


namespace stock\commands;


use pocketmine\command\CommandSender;
use pocketmine\Player;
use stock\utils\StockExchange;

class StockExchangeCommand extends \pocketmine\command\Command
{

    public function __construct($name, $description = "", $usageMessage = null, array $aliases = [])
    {
        parent::__construct($name, $description, $usageMessage, $aliases);
    }

    /**
     * @param Player $sender
     * @param string $commandLabel
     * @param array $args
     * @return true
     */
    public function execute(CommandSender $sender, $commandLabel, array $args)
    {
        $sender->sendMessage("§aA bolsa de valores está em §f" . StockExchange::getPercentageValue() . "%%§a!");
        return true;
    }
}