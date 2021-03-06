<?php

namespace bedwars\libs {


    use pocketmine\block\Air;
    use pocketmine\entity\FallingSand;
    use pocketmine\event\inventory\InventoryTransactionEvent;
    use pocketmine\event\Listener;
    use pocketmine\event\player\PlayerDropItemEvent;
    use pocketmine\event\server\DataPacketReceiveEvent;
    use pocketmine\inventory\CustomInventory;
    use pocketmine\inventory\Inventory;
    use pocketmine\inventory\InventoryHolder;
    use pocketmine\inventory\InventoryType;
    use pocketmine\item\Item;
    use pocketmine\level\Position;
    use pocketmine\math\Vector3;
    use pocketmine\nbt\NBT;
    use pocketmine\nbt\tag\CompoundTag;
    use pocketmine\nbt\tag\IntTag;
    use pocketmine\nbt\tag\StringTag;
    use pocketmine\network\protocol\BlockEntityDataPacket;
    use pocketmine\network\protocol\ContainerSetSlotPacket;
    use pocketmine\network\protocol\UpdateBlockPacket;
    use pocketmine\Player;
    use pocketmine\plugin\Plugin;
    use pocketmine\scheduler\PluginTask;
    use pocketmine\Server;

    final class WindowManager
    {

        /** @var Window[] */
        protected static $players = [];

        /**
         * @param Player $player
         * @param Inventory $inventory
         * @return Inventory
         */
        public static function addPlayerWindow(Player $player, Window $inventory): Window
        {
            return self::$players[strtolower($player->getName())] = $inventory;
        }

        /**
         * @param Player $player
         * @return bool
         */
        public static function removePlayerWindow(Player $player): bool
        {
            if (isset(self::$players[strtolower($player->getName())])) unset(self::$players[strtolower($player->getName())]);
            return true;
        }

        /**
         * @param Player $player
         * @return Window|null
         */
        public static function getPlayerWindow(Player $player)
        {
            if (isset(self::$players[strtolower($player->getName())]))
                return self::$players[strtolower($player->getName())];
            else
                return null;
        }
    }

    class WindowHolder extends Vector3 implements InventoryHolder
    {

        protected $inventory;

        public function __construct($x, $y, $z, Inventory $inventory)
        {
            parent::__construct($x, $y, $z);
            $this->inventory = $inventory;
        }

        public function getInventory(): Inventory
        {
            return $this->inventory;
        }

    }

    class WindowHandler implements Listener
    {

        /** @var  Plugin */
        protected $owner = null;

        /** @var Item[] */
        protected $transitionedItems = [];

        /** @var Player[] */
        protected $lastTransitioned = [];

        public function __construct(Plugin $plugin)
        {
            $this->owner = $plugin;
        }

        public function drop(PlayerDropItemEvent $event) {
            $item = $event->getItem();
            if(isset($this->transitionedItems[$item->getCustomName()])) {
                unset($this->transitionedItems[$item->getCustomName()]);
                $event->setCancelled(true);
            }
        }

        public function transaction(InventoryTransactionEvent $event): bool
        {
            $transaction = $event->getQueue();
            $player = $transaction->getPlayer();

            if ($event->isCancelled())
                return false;

            if ($window = WindowManager::getPlayerWindow($player)) {
                foreach ($transaction->getTransactions() as $trans) {
                    /** @var Item $item */
                    $item = $trans->getTargetItem();
                    try {
                        if ($item->getId() > 0) {
                            $window->getClosure()->call($window, $event, $player, $item);
                            if($event->isCancelled()) {
                                if($item->getCustomName() != "") {
                                    $player->getInventory()->remove($item);
                                    $this->transitionedItems[$item->getCustomName()] = $item;
                                }
                                $player->getFloatingInventory()->remove($item);
                            }
                        }
                    } catch (\Exception $exception) {
                        var_dump($exception->getMessage());
                    }
                }
            }

            return true;
        }
    }

    class Window extends CustomInventory implements Listener
    {

        /** @var string */
        protected $customName = "";

        /** @var int */
        protected $countdown = 10;

        /** @var int */
        protected $type = InventoryType::CHEST;
        /** @var int */
        protected $size = 27;

        /** @var Position */
        protected $pos = null;
        /** @var WindowHolder */
        protected $holder = null;

        /** @var \Closure|callable */
        protected $closure = null;

        /** @var Plugin */
        protected $owner = null;

        const ALIGN_CENTER = 14;

        public static function registerHandler(Listener $plugin)
        {
            if ($plugin instanceof Plugin) {
                $plugin->getServer()->getPluginManager()->registerEvents(new WindowHandler($plugin), $plugin);
            } else {
                Server::getInstance()->getLogger()->alert('??cIt was not possible to register the Window system handler, as the passed class is not extended to Plugin');
            }
        }

        /**
         * Window constructor.
         * @param Plugin $provider
         * @param Position $position
         * @param string $name
         * @param callable $context
         * @param int $countdown
         * @param int $type
         * @param int $size
         */
        public function __construct(Plugin $provider, Position $position, string $name, callable $context, int $countdown = 10, int $type = InventoryType::CHEST, int $size = 27)
        {
            $this->owner = $provider;
            $this->pos = $position->add(0, 2);

            $this->customName = $name;
            $this->closure = $context;

            $this->countdown = $countdown;

            $this->type = $type;
            $this->size = $size;

            $this->holder = new WindowHolder($this->pos->x, $this->pos->y, $this->pos->z, $this);
            $provider->getServer()->getPluginManager()->registerEvents($this, $provider);
            parent::__construct($this->holder, InventoryType::get($type));
        }

        /**
         * @param Player $who
         */
        public function onOpen(Player $who)
        {

            $pk = new UpdateBlockPacket();
            $pk->x = $this->holder->x;
            $pk->y = $this->holder->y;
            $pk->z = $this->holder->z;
            $pk->blockId = 54;
            $pk->blockData = 0;
            $pk->flags = UpdateBlockPacket::FLAG_ALL;
            $who->dataPacket($pk);

            $this->pos->getLevel()->updateAround($this->pos);

            $c = new CompoundTag("", [
                new StringTag("id", InventoryType::CHEST),
                new IntTag("x", (int)$this->pos->x),
                new IntTag("y", (int)$this->pos->y),
                new IntTag("z", (int)$this->pos->z),
                new StringTag("CustomName", $this->customName)
            ]);

            $nbt = new NBT(NBT::LITTLE_ENDIAN);
            $nbt->setData($c);

            $pk = new BlockEntityDataPacket();
            $pk->x = $this->holder->x;
            $pk->y = $this->holder->y;
            $pk->z = $this->holder->z;
            $pk->namedtag = $nbt->write();
            $who->dataPacket($pk);

            $this->owner->getServer()->getScheduler()->scheduleDelayedTask(new class($this->owner, $this, $who, function (Player $who) {
                parent::onOpen($who);
                $this->sendContents($who);
                WindowManager::addPlayerWindow($who, $this);
            }) extends PluginTask {

                /** @var \Closure */
                protected $closure = null;

                /** @var Window */
                protected $window = null;

                /** @var Player */
                protected $who = null;

                public function __construct(Plugin $owner, Window $window, Player $who, callable $closure)
                {
                    $this->window = $window;
                    $this->closure = $closure;
                    $this->who = $who;
                    parent::__construct($owner);
                }

                public function onRun($currentTick)
                {
                    $this->closure->call($this->window, $this->who);
                }

            }, $this->countdown);
        }

        public function onClose(Player $who)
        {
            $pk = new UpdateBlockPacket();
            $pk->x = $this->holder->x;
            $pk->y = $this->holder->y;
            $pk->z = $this->holder->z;
            $pk->blockId = $who->getLevel()->getBlockIdAt($this->holder->x, $this->holder->y, $this->holder->z);
            $pk->blockData = $who->getLevel()->getBlockDataAt($this->holder->x, $this->holder->y, $this->holder->z);
            $pk->flags = UpdateBlockPacket::FLAG_ALL;
            $who->dataPacket($pk);

            $this->owner->getServer()->getScheduler()->scheduleDelayedTask(new class($this->owner, $this, $who, function (Player $who) {
                parent::onClose($who);
                WindowManager::removePlayerWindow($who);
            }) extends PluginTask {

                /** @var \Closure */
                protected $closure = null;

                /** @var Window */
                protected $window = null;

                /** @var Player */
                protected $who = null;

                public function __construct(Plugin $owner, Window $window, Player $who, callable $closure)
                {
                    $this->window = $window;
                    $this->closure = $closure;
                    $this->who = $who;
                    parent::__construct($owner);
                }

                public function onRun($currentTick)
                {
                    $this->closure->call($this->window, $this->who);
                }

            }, $this->countdown);

        }

        /**
         * @return callable|\Closure
         */
        public function getClosure()
        {
            return $this->closure;
        }
    }
}