<?php


namespace stock\commands;


use stock\libs\Window;
use pocketmine\block\Wool;
use pocketmine\command\CommandSender;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\item\Item;
use pocketmine\Player;
use stock\libs\WindowManager;
use stock\Loader;
use stock\utils\StockExchange;

class SellCommand extends \pocketmine\command\Command
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
        $window = new Window(Loader::get(), $sender->getPosition(), '§aVenda de Items', function (InventoryTransactionEvent $event, Player $player, Item $item) {
            $window = WindowManager::getPlayerWindow($player);
            switch ($item->getCustomName()) {
                case "§aVender itens da sua mão...":
                    if(is_numeric($price = StockExchange::getPriceFromItem($handItem = $player->getItemInHand()))) {
                        $player->getInventory()->setItemInHand(Item::get(Item::AIR));
                        Loader::$economyAPI->addMoney($player, $price);
                        $player->sendMessage("§aVocê vendeu §f" . $handItem->getName() . " x" . $handItem->getCount() . "§a por §f" . $price . "§a!");
                    } else {
                        $player->sendMessage("§cO item que está em sua mão não pode ser vendido!");
                    }
                    break;
                case "§aVender todos os itens...":
                    $message = "§aLista de itens vendidos:";
                    $collected = 0;
                    $items = [];
                    $contents = [];
                    foreach ($player->getInventory()->getContents() as $slot => $content) {
                        if($price = StockExchange::getPriceFromItem($content)) {
                            $collected += $price;
                            if(isset($items[$content->getName()])) {
                                $items[$content->getName()] += $content->getCount();
                            } else {
                                $items[$content->getName()] = $content->getCount();
                            }
                        } else {
                            $contents[$slot] = $content;
                        }
                    }

                    if(count($items) > 0) {
                        foreach ($items as $itemName => $count) {
                            $message .= "\n§a* §f" . $itemName . " x" . $count . "§a";
                        }
                        $message .= "\n§aTotalizando um valor de §f" . $collected . "§a!";
                        $player->getInventory()->setContents($contents);
                        Loader::$economyAPI->addMoney($player, $collected);
                    } else {
                        $message = "§cVocê não possuía nenhum item no inventario que poderia ser vendido!";
                    }

                    $player->sendMessage($message);
                    break;
            }
            $player->removeWindow($window);
            $event->setCancelled(true);
        });

        $window->setItem(12, Item::get(Item::MINECART)->setCustomName("§aVender itens da sua mão..."));
        $window->setItem(14, Item::get(Item::MINECART_WITH_CHEST)->setCustomName("§aVender todos os itens..."));
        $sender->addWindow($window);
        return true;
    }
}