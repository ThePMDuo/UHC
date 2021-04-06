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
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat as TF;
use pocketmine\utils\Process;
use pocketmine\block\Block;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\Player;

use AGTHARN\uhc\event\PhaseChangeEvent;
use AGTHARN\uhc\game\type\GameTimer;
use AGTHARN\uhc\game\Border;
use AGTHARN\uhc\Main;

use AGTHARN\uhc\libs\JackMD\ScoreFactory\ScoreFactory;

class EventListener implements Listener
{
    /** @var Main */
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
    private $deathmatch = GameTimer::TIMER_DEATHMATCH;
    /** @var int */
    private $winner = GameTimer::TIMER_WINNER;
    /** @var int */
    private $phase = PhaseChangeEvent::WAITING;
    /** @var int */
    private $reset = PhaseChangeEvent::RESET;
    
    /**
     * __construct
     *
     * @param  Main $plugin
     * @return void
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
        $this->border = new Border($plugin->getServer()->getDefaultLevel());

        $plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
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
     * @param  PlayerChatEvent $event
     * @return void
     */
    public function handleChat(PlayerChatEvent $event): void
    {
        $player = $event->getPlayer();
        if ($this->plugin->isGlobalMuteEnabled() && !$player->isOp()) {
            $player->sendMessage(TF::RED . "You cannot talk right now!");
            $event->setCancelled();
        }
    }
    
    /**
     * handleJoin
     *
     * @param  PlayerJoinEvent $event
     * @return void
     */
    public function handleJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();
        $server = $this->plugin->getServer();
        $session = $this->plugin->getSession($player);
        $mUsage = Process::getAdvancedMemoryUsage();

        $player->sendMessage("Welcome to UHC! Build " . $this->plugin->buildNumber);
        $player->sendMessage("UHC-" . $this->plugin->uhcServer . ": " . $this->plugin->getOperationalMessage());
        $player->sendMessage("THREADS: " . Process::getThreadCount() . " RAM USAGE: " . number_format(round(($mUsage[1] / 1024) / 1024, 2), 2) . " MB");

        if (!$this->plugin->getOperational()) {
            $player->kick($this->plugin->getOperationalMessage() . ": UHC LOADER HAS FAILED! PLEASE CONTACT AN ADMIN!");
            return;
        }

        switch ($this->plugin->getManager()->getPhase()) {
            case PhaseChangeEvent::WAITING:
            case PhaseChangeEvent::COUNTDOWN:
                if ($this->countdown >= 31) {
                    if ($this->plugin->hasSession($player)) {
                        $session->updatePlayer($player);
                        $player->setGamemode(Player::SURVIVAL);
                    } else {
                        $this->plugin->addSession(new PlayerSession($player));
                        $player->setGamemode(Player::SURVIVAL);
                    }
                    // since solo we wont handle joining available teams
                    $session->addToTeam($this->plugin->getTeamManager()->createTeam($player));
                }
                break;
            default:
                if ($this->plugin->hasSession($player)) {
                    $this->plugin->removeFromGame($player);
                }
                $player->setGamemode(3);
                $player->sendMessage(TF::YELLOW . "Type /spectate to spectate a player.");
                break;
        }
        $player->teleport(new Position(265, 70, 265, $server->getLevelByName($this->plugin->getManager()->getMap())));
    }
    
    /**
     * handlePhaseChange
     *
     * @param  PhaseChangeEvent $event
     * @return void
     */
    public function handlePhaseChange(PhaseChangeEvent $event): void
    {
        $player = $event->getPlayer();

        switch ($event->getOldPhase()) {
            case PhaseChangeEvent::COUNTDOWN:
                $player->getInventory()->addItem(ItemFactory::get(ItemIds::BAKED_POTATO, 0, 16));
                $player->getInventory()->addItem(Item::get(6, 0, 1));
                break;
        }
    }
    
    /**
     * handleQuit
     *
     * @param  PlayerQuitEvent $event
     * @return void
     */
    public function handleQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();
        $session = $this->plugin->getSession($player);

        if ($session->isInTeam()) {
            if (!$session->isTeamLeader()) {
                $session->removeFromTeam(); 
            } else {
                foreach ($session->getTeam()->getMembers() as $member) {
					$this->plugin->getSession($member)->removeFromTeam();
				}
                $this->plugin->getTeamManager()->disbandTeam($session->getTeam()->getNumber());
            }
        }

        if ($this->plugin->hasSession($player)) {
            $this->plugin->removeFromGame($player);
        }
        $this->plugin->removeFromGame($player);
        ScoreFactory::removeScore($player);
    }
    
    /**
     * handleEntityRegain
     *
     * @param  EntityRegainHealthEvent $event
     * @return void
     */
    public function handleEntityRegain(EntityRegainHealthEvent $event): void
    {
        switch ($event->getRegainReason()) {
            case EntityRegainHealthEvent::CAUSE_SATURATION:
                $event->setCancelled();
                break;
        }
    }
    
    /**
     * handleDamage
     *
     * @param  EntityDamageEvent $event
     * @return void
     */
    public function handleDamage(EntityDamageEvent $event): void
    {
        $cause = $event->getEntity()->getLastDamageCause();
        $entity = $event->getEntity();
        
        if ($event->getCause() === EntityDamageEvent::CAUSE_MAGIC) return;
        switch ($this->plugin->getManager()->getPhase()) {
            case PhaseChangeEvent::WAITING:
            case PhaseChangeEvent::COUNTDOWN:
            case PhaseChangeEvent::WINNER:
                $event->setCancelled();
                break;
            case PhaseChangeEvent::GRACE:
                if ($entity instanceof Player) {
                    $event->setCancelled();
                }
                break;
            default:
                if ($event instanceof EntityDamageByEntityEvent) {
                    $damager = $event->getDamager();
                    $victim = $event->getEntity();
    
                    if ($damager instanceof Player && $victim instanceof Player) {
                        $damagerSession = $this->plugin->getSession($damager);
                        $victimSession = $this->plugin->getSession($victim);
                        if ($damagerSession->isInTeam() && $victimSession->isInTeam() && $damagerSession->getTeam()->memberExists($victim)) {
                            $event->setCancelled();
                        }
                    }
                }
                break;
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
        $event->getPlayer()->teleport(new Position(265, 70, 265, $this->plugin->getServer()->getLevelByName($this->plugin->getManager()->getMap())));
    }
    
    /**
     * handleDeath
     *
     * @param  PlayerDeathEvent $event
     * @return void
     */
    public function handleDeath(PlayerDeathEvent $event): void
    {
        $player = $event->getPlayer();
        $cause = $player->getLastDamageCause();
        $eliminatedSession = $this->plugin->getSession($player);
        
        $player->setGamemode(3);
        $player->sendMessage(TF::YELLOW . "You have been eliminated! Type /spectate to spectate a player.");

        if (!$this->plugin->hasSession($player)) return;
        
        if ($cause instanceof EntityDamageByEntityEvent) {
            $damager = $cause->getDamager();
            if ($damager instanceof Player) {
                if ($this->plugin->hasSession($damager)) {
                    $damagerSession = $this->plugin->getSession($damager);

                    $damagerSession->addEliminations();
                    $event->setDeathMessage(TF::RED . $player->getName() . TF::GRAY . " (" . TF::WHITE . $eliminatedSession->getEliminations() . TF::GRAY . ")" . TF::YELLOW . " was eliminated by " . TF::RED . $damager->getName() . TF::GRAY . "(" . TF::WHITE . $damagerSession->getEliminations() . TF::GRAY . ")");
                }
            }
        } else {
            $event->setDeathMessage(TF::RED . $player->getName() . TF::GRAY . " (" . TF::WHITE . $eliminatedSession->getEliminations() . TF::GRAY . ")" . TF::YELLOW . " has been eliminated somehow!");
        }
    }
    
    /**
     * handleBreak
     *
     * @param  BlockBreakEvent $event
     * @return void
     */
    public function handleBreak(BlockBreakEvent $event): void
    {
        switch ($this->plugin->getManager()->getPhase()) {
            case PhaseChangeEvent::WAITING:
            case PhaseChangeEvent::COUNTDOWN:
            case PhaseChangeEvent::WINNER:
                $event->setCancelled();
                return;
        }

        switch ($event->getBlock()) {
            case Block::LEAVES:
            case Block::LEAVES2:
                $rand = mt_rand(0, 100);
                if ($event->getItem()->equals(Item::get(Item::APPLE, 0, 1), false, false)) {
                    if ($rand <= 6) {
                        $event->setDrops([Item::get(Item::APPLE, 0, 1)]);
                    }
                } else {
                    if ($rand <= 3) {
                        $event->setDrops([Item::get(Item::APPLE, 0, 1)]);
                    }
                }
                break;
            case Block::LOG:
            case Block::LOG2:
                $drops = array();
                $drops[] = Item::get(Item::PLANKS, 0, 4);
                $event->setDrops($drops);
                break;
            case Block::IRON_ORE:
                $drops = array();
                $drops[] = Item::get(Item::IRON_INGOT, 0, 2);
                $event->setDrops($drops);
                break;
            case Block::GOLD_ORE:
                $drops = array();
                $drops[] = Item::get(Item::GOLD_INGOT, 0, 2);
                $event->setDrops($drops);
                break;
        }
    }
    
    /**
     * handlePlace
     *
     * @param  BlockPlaceEvent $event
     * @return void
     */
    public function handlePlace(BlockPlaceEvent $event): void
    {
        switch ($this->plugin->getManager()->getPhase()) {
            case PhaseChangeEvent::WAITING:
            case PhaseChangeEvent::COUNTDOWN:
            case PhaseChangeEvent::WINNER:
                $event->setCancelled();
                break;
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
        if ($event->getCause() === EntityDamageEvent::CAUSE_FALL) {
            switch ($this->plugin->getManager()->getPhase()) {
                case PhaseChangeEvent::WAITING:
                case PhaseChangeEvent::COUNTDOWN:
                case PhaseChangeEvent::DEATHMATCH:
                    if ($this->deathmatch >= 850) {
                        $event->setCancelled();
                    }
                    break;
            }
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

        // to do use waterdogpe api instead
        if ($item->hasEnchantment(17)) {
            switch ($item->getId()) {
                case 355:
                    $event->setCancelled();
                    $this->plugin->getServer()->dispatchCommand($player, "transfer hub");
                    break;
                case 35:
                    $event->setCancelled();
                    $this->plugin->getServer()->dispatchCommand($player, "report");
                    break;
            }
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
        foreach ($transaction->getActions() as $action) {
            $item = $action->getSourceItem();

            if ($item->hasEnchantment(17)) {
                switch ($item->getId()) {
                    case 355:
                    case 35:
                        $event->setCancelled();
                        break;
                }
            }

            if ($item->getId() === 444) {
                $event->setCancelled();
            }
        }
    }
    
    /**
     * onPlayerDropItem
     *
     * @param  PlayerDropItemEvent $event
     * @return void
     */
    public function onPlayerDropItem(PlayerDropItemEvent $event)
    {
        $item = $event->getItem();
        $itemID = $item->getId();

        if (!$this->plugin->getManager()->hasStarted()) {
			$event->setCancelled();
		}

        if ($item->hasEnchantment(17)) {
            switch ($item->getId()) {
                case 355:
                case 35:
                    $event->setCancelled();
                    break;
            }
        }
    }
    
    /**
     * handleExhaust
     *
     * @param  PlayerExhaustEvent $event
     * @return void
     */
    public function handleExhaust(PlayerExhaustEvent $event): void
	{
		if (!$this->plugin->getManager()->hasStarted()) {
			$event->setCancelled();
		}
	}
}
