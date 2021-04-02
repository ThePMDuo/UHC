<?php
declare(strict_types=1);

namespace AGTHARN\uhc;

use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat as TF;
use pocketmine\block\Block;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\Player;

use AGTHARN\uhc\event\PhaseChangeEvent;
use AGTHARN\uhc\game\type\GameTimer;
use AGTHARN\uhc\game\Border;
use AGTHARN\uhc\Loader;

use AGTHARN\uhc\libs\JackMD\ScoreFactory\ScoreFactory;

class EventListener implements Listener
{
    /** @var Loader */
    private $plugin;
    
    /** @var int */
    private $playerTimer = 1;
    
    /** @var Border */
    private $border;
    
    /** @var int */
    private $game = 0;
    
    /** @var int */
    private $countdown = GameTimer::TIMER_COUNTDOWN;
    /** @var float|int */
    private $grace = GameTimer::TIMER_GRACE;
    /** @var float|int */
    private $pvp = GameTimer::TIMER_PVP;
    /** @var float|int */
    private $normal = GameTimer::TIMER_NORMAL;
    /** @var int */
    private $winner = GameTimer::TIMER_WINNER;
    /** @var int */
    private $phase = PhaseChangeEvent::WAITING;
    /** @var int */
    private $reset = PhaseChangeEvent::RESET;
    
    /**
     * __construct
     *
     * @param  Loader $plugin
     * @return void
     */
    public function __construct(Loader $plugin)
    {
        $this->plugin = $plugin;
        $plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
        $this->border = new Border($plugin->getServer()->getDefaultLevel());
    }
        
    /**
     * getPhase
     *
     * @return int
     */
    public function getPhase(): int
    {
        return $this->phase;
    }
    
    /**
     * setPhase
     *
     * @param  int $phase
     * @return void
     */
    public function setPhase(int $phase): void
    {
        $this->phase = $phase;
    }
    
    /**
     * handleChat
     *
     * @param  PlayerChatEvent $ev
     * @return void
     */
    public function handleChat(PlayerChatEvent $ev): void
    {
        $player = $ev->getPlayer();
        if ($this->plugin->isGlobalMuteEnabled() && !$player->isOp()) {
            $player->sendMessage(TF::RED . "You cannot talk right now!");
            $ev->setCancelled();
        }
    }
    
    /**
     * handleJoin
     *
     * @param  PlayerJoinEvent $ev
     * @return void
     */
    public function handleJoin(PlayerJoinEvent $ev): void
    {
        $player = $ev->getPlayer();
        $server = $this->plugin->getServer();
        
        if ($this->plugin->getHeartbeat()->getPhase() === PhaseChangeEvent::WAITING || $this->plugin->getHeartbeat()->getPhase() === PhaseChangeEvent::COUNTDOWN && $this->countdown >= 31) {
            if (!$this->plugin->hasSession($player)) {
                $this->plugin->addSession(PlayerSession::create($player));
                $player->setGamemode(Player::SURVIVAL);
                } else {
                    $this->plugin->getSession($player)->setPlayer($player);
                    $player->setGamemode(Player::SURVIVAL);
                }
        } else {
            if ($this->plugin->hasSession($player)) {
                $this->plugin->removeFromGame($player);
            }
            $player->setGamemode(3);
            $player->sendMessage(TF::YELLOW . "Type /spectate to spectate a player.");
        }

        //if ($this->plugin->getHeartbeat()->getPhase() === PhaseChangeEvent::WAITING) {
            //$player->teleport($player->getLevel()->getSafeSpawn());
            //$player->setGamemode(Player::SURVIVAL);
        //}
        
        $x = 265;
        $y = 70;
        $z = 265;
        $level = $server->getLevelByName($this->plugin->getHeartbeat()->getMap());
        
        $player->teleport(new Position($x, $y, $z, $level));

        //$ev->setJoinMessage("");
    }
    
    /**
     * handlePhaseChange
     *
     * @param  PhaseChangeEvent $ev
     * @return void
     */
    public function handlePhaseChange(PhaseChangeEvent $ev): void
    {
        $player = $ev->getPlayer();
        if ($ev->getOldPhase() === PhaseChangeEvent::COUNTDOWN) {
            $player->getInventory()->addItem(ItemFactory::get(ItemIds::BAKED_POTATO, 0, 15));
            $player->getInventory()->addItem(Item::get(6, 0, 1));
        }
    }
    
    /**
     * handleQuit
     *
     * @param  PlayerQuitEvent $ev
     * @return void
     */
    public function handleQuit(PlayerQuitEvent $ev): void
    {
        $player = $ev->getPlayer();
        if ($this->plugin->hasSession($player)) {
                $this->plugin->removeFromGame($player);
        }
        $this->plugin->removeFromGame($player);
        ScoreFactory::removeScore($player);
        //$ev->setQuitMessage("");
    }
    
    /**
     * handleEntityRegain
     *
     * @param  EntityRegainHealthEvent $ev
     * @return void
     */
    public function handleEntityRegain(EntityRegainHealthEvent $ev): void
    {
        if ($ev->getRegainReason() === EntityRegainHealthEvent::CAUSE_SATURATION) {
            $ev->setCancelled(true);
        }
    }
    
    /**
     * handleDamage
     *
     * @param  EntityDamageEvent $ev
     * @return void
     */
    public function handleDamage(EntityDamageEvent $ev): void
    {
        $cause = $ev->getEntity()->getLastDamageCause();
        $entity = $ev->getEntity();
        
        if ($ev->getCause() !== EntityDamageEvent::CAUSE_MAGIC) {
            if ($this->plugin->getHeartbeat()->getPhase() === PhaseChangeEvent::WAITING || $this->plugin->getHeartbeat()->getPhase() === PhaseChangeEvent::COUNTDOWN || $this->plugin->getHeartbeat()->getPhase() === PhaseChangeEvent::WINNER) {
                $ev->setCancelled();
            }

            if ($this->plugin->getHeartbeat()->getPhase() === PhaseChangeEvent::GRACE) {
                if ($entity instanceof Player) {
                    $ev->setCancelled();
                }
            }
        }
    }
        
    /**
     * onRespawn
     *
     * @param  PlayerRespawnEvent $event
     * @return void
     */
    public function onRespawn(PlayerRespawnEvent $event): void
    {
        $level = $this->plugin->getServer()->getLevelByName($this->plugin->getHeartbeat()->getMap());
        
        $event->getPlayer()->teleport(new Position(265, 70, 265, $level));
    }
    
    /**
     * handleDeath
     *
     * @param  PlayerDeathEvent $ev
     * @return void
     */
    public function handleDeath(PlayerDeathEvent $ev): void
    {
        $player = $ev->getPlayer();
        $server = $this->plugin->getServer();
        $cause = $player->getLastDamageCause();
        $eliminatedSession = $this->plugin->getSession($player);
        
        $player->setGamemode(3);
        $player->sendMessage(TF::YELLOW . "You have been eliminated! Type /spectate to spectate a player.");

        if (!$this->plugin->hasSession($player) && $eliminatedSession->getEliminations() === null) return;
        
        if ($cause instanceof EntityDamageByEntityEvent) {
            $damager = $cause->getDamager();
            if ($damager instanceof Player) {
                if ($this->plugin->hasSession($damager)) {
                $damagerSession = $this->plugin->getSession($damager);
                $damagerSession->addElimination();
                $ev->setDeathMessage(TF::RED . $player->getName() . TF::GRAY . " (" . TF::WHITE . $eliminatedSession->getEliminations() . TF::GRAY . ")" . TF::YELLOW . " was eliminated by " . TF::RED . $damager->getName() . TF::GRAY . "(" . TF::WHITE . $damagerSession->getEliminations() . TF::GRAY . ")");
                }
            }
        } else {
            $ev->setDeathMessage(TF::RED . $player->getName() . TF::GRAY . " (" . TF::WHITE . $eliminatedSession->getEliminations() . TF::GRAY . ")" . TF::YELLOW . " has been eliminated!");
        }
    }
    
    /**
     * handleBreak
     *
     * @param  BlockBreakEvent $ev
     * @return void
     */
    public function handleBreak(BlockBreakEvent $ev): void
    {
        if ($this->plugin->getHeartbeat()->getPhase() === PhaseChangeEvent::WAITING || $this->plugin->getHeartbeat()->getPhase() === PhaseChangeEvent::COUNTDOWN || $this->plugin->getHeartbeat()->getPhase() === PhaseChangeEvent::WINNER) {
            $ev->setCancelled();
        }
    }
    
    /**
     * handlePlace
     *
     * @param  BlockPlaceEvent $ev
     * @return void
     */
    public function handlePlace(BlockPlaceEvent $ev): void
    {
        if ($this->plugin->getHeartbeat()->getPhase() === PhaseChangeEvent::WAITING || $this->plugin->getHeartbeat()->getPhase() === PhaseChangeEvent::COUNTDOWN || $this->plugin->getHeartbeat()->getPhase() === PhaseChangeEvent::WINNER) {
            $ev->setCancelled();
        }
    }
        
    /**
     * handleFallDamage
     *
     * @param  EntityDamageEvent $event
     * @return void
     */
    public function handleFallDamage(EntityDamageEvent $event): void
    {
        //grace falling handled by grace period
        if($event->getCause() === EntityDamageEvent::CAUSE_FALL && $this->plugin->getHeartbeat()->getPhase() === PhaseChangeEvent::WAITING || $event->getCause() === EntityDamageEvent::CAUSE_FALL && $this->plugin->getHeartbeat()->getPhase() === PhaseChangeEvent::COUNTDOWN || $event->getCause() === EntityDamageEvent::CAUSE_FALL && $this->plugin->getHeartbeat()->getPhase() === PhaseChangeEvent::NORMAL && $this->normal >= 800) {
            $event->setCancelled();
        }
    }
        
    /**
     * dropChance
     *
     * @param  BlockBreakEvent $event
     * @return void
     */
    public function dropChance(BlockBreakEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if($block->getId() === Block::LEAVES || $block->getId() === Block::LEAVES2) {
            $chance = mt_rand(1,100); 
            if($chance <= 10){ 
               $drops = array();
               $drops[] = Item::get(Item::APPLE, 0, 1);
               $event->setDrops($drops);
            }
        }
        if($block->getId() === Block::LOG || $block->getId() === Block::LOG2) {
            $drops = array();
            $drops[] = Item::get(Item::PLANKS, 0, 4);
            $event->setDrops($drops);
        }
        if($block->getId() === Block::IRON_ORE) {
            $drops = array();
            $drops[] = Item::get(Item::IRON_INGOT, 0, 2);
            $event->setDrops($drops);
        }
        if($block->getId() === Block::GOLD_ORE) {
            $drops = array();
            $drops[] = Item::get(Item::GOLD_INGOT, 0, 2);
            $event->setDrops($drops);
        }
    }
        
    /**
     * onInteract
     *
     * @param  PlayerInteractEvent $event
     * @return void
     */
    public function onInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();
        $item = $event->getItem();
        $itemID = $item->getId();
        
        if ($itemID === 355 && $item->hasEnchantment(17)) {
            $event->setCancelled(true);
            $this->plugin->getServer()->dispatchCommand($player, "transfer hub");
            //$player->kick();
        }elseif ($itemID === 35 && $item->hasEnchantment(17)) {
            $event->setCancelled(true);
            $this->plugin->getServer()->dispatchCommand($player, "report");
        }
    }
        
    /**
     * onInventoryTransaction
     *
     * @param  InventoryTransactionEvent $event
     * @return void
     */
    public function onInventoryTransaction(InventoryTransactionEvent $event): void
    {
        $transaction = $event->getTransaction();
        foreach($transaction->getActions() as $action){
            $item = $action->getSourceItem();
            $itemID = $item->getId();
            
            if ($itemID === 355 && $item->hasEnchantment(17) || $itemID === 35 && $item->hasEnchantment(17)) {
                $event->setCancelled(true);
            }
        }
    }
    
    /**
     * onPlayerDropItem
     *
     * @param  PlayerDropItemEvent $event
     * @return void
     */
    public function onPlayerDropItem(PlayerDropItemEvent $event){
        $item = $event->getItem();
        $itemID = $item->getId();

        if ($itemID === 355 && $item->hasEnchantment(17) || $itemID === 35 && $item->hasEnchantment(17)) {
            $event->setCancelled(true);
        }
    }
}
