<?php

namespace stock;

use stock\commands\StockExchangeCommand;
use stock\libs\Window;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use stock\commands\SellCommand;
use stock\tasks\GraphicTask;
use stock\utils\StockExchange;

class Loader extends PluginBase implements Listener {

    private static $instance = null;

    public static $config = [];

    public static $economyAPI = null;

    public static function get() {
        return self::$instance;
    }

    public function onEnable()
    {

        self::$instance = $this;

        if(!(self::$economyAPI = $this->getServer()->getPluginManager()->getPlugin('EconomyAPI'))) {
            $this->getServer()->getLogger()->alert("§cO plugin StockExchange não funciona sem o EconomyAPI!");
            $this->getServer()->getLogger()->alert("§cDESATIVANDO...");
            $this->setEnabled(false);
            return;
        }

        Window::registerHandler($this);

        @mkdir($folder = $this->getDataFolder());
        $this->saveResource('config.yml');
        $this->saveResource('items.yml');

        self::$config = @yaml_parse_file($folder . 'config.yml');
        StockExchange::init(@yaml_parse_file($folder . 'items.yml'));

        $this->getServer()->getCommandMap()->register('sell', new SellCommand('sell', 'Venda seus drops...', '', ['vender']));
        $this->getServer()->getCommandMap()->register('exchange', new StockExchangeCommand('exchange', 'Veja o valor da bolsa de valores...', '', ['bolsa']));

        $this->getServer()->getScheduler()->scheduleRepeatingTask(new GraphicTask(), ((20 * 60) * self::$config['stock-exchange-refresh']));

        $this->getServer()->getLogger()->info("§aStock Exchange funcionando normalmente!");

    }

}