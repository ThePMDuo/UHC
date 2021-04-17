<?php
declare(strict_types=1);

namespace AGTHARN\uhc;

use pocketmine\level\generator\object\Tree;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\player\PlayerGameModeChangeEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\utils\Process;
use pocketmine\utils\Random;
use pocketmine\block\Block;
use pocketmine\level\Position;
use pocketmine\item\Item;
use pocketmine\Player;

use AGTHARN\uhc\game\border\Border;
use AGTHARN\uhc\event\PhaseChangeEvent;
use AGTHARN\uhc\Main;

use AGTHARN\uhc\libs\JackMD\ScoreFactory\ScoreFactory;

use Exception;

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
    
    /**
     * __construct
     *
     * @param  Main $plugin
     * @return void
     */
    public function __construct(Main $plugin, Border $border)
    {
        $this->plugin = $plugin;
        $this->border = $border;
    }
    
    /**
     * handlePreLogin
     *
     * @param  PlayerPreLoginEvent $event
     * @return void
     */
    public function handlePreLogin(PlayerPreLoginEvent $event): void
    {
        $player = $event->getPlayer();
        $sessionManager = $this->plugin->getSessionManager();

        switch ($this->plugin->getManager()->getPhase()) {
            case PhaseChangeEvent::WAITING:
                $sessionManager->createSession($player);
                // since solo we wont handle joining available teams
                $session = $this->plugin->getSessionManager()->getSession($player);
                $session->addToTeam($this->plugin->getTeamManager()->createTeam($player));
                break;
            case PhaseChangeEvent::RESET:
                $player->kick("SERVER RESETTING: IF IT TAKES LONGER THAN 10 SECONDS, PLEASE CONTACT AN ADMIN!");
                break;
            default:
                $player->setGamemode(Player::SPECTATOR);
                $player->sendMessage("§eType /spectate to spectate a player.");
                break;
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
        $sessionManager = $this->plugin->getSessionManager();
        $session = $this->plugin->getSessionManager()->getSession($player);
        $server = $this->plugin->getServer();
        $mUsage = Process::getAdvancedMemoryUsage();

        $player->sendMessage("Welcome to UHC! Build " . $this->plugin->buildNumber . " © 2021 MineUHC");
        $player->sendMessage("UHC-" . $this->plugin->uhcServer . ": " . $this->plugin->getOperationalColoredMessage());
        $player->sendMessage("THREADS: " . Process::getThreadCount() . " | RAM USAGE: " . number_format(round(($mUsage[1] / 1024) / 1024, 2), 2) . " MB");
        $player->sendMessage("AntiCheat powered by BirdAC & MineGuard");

        if (!$this->plugin->getOperational()) {
            $player->kick($this->plugin->getOperationalColoredMessage() . ": SERVER RESETTING! SHOULD NOT TAKE LONGER THAN 10 SECONDS!");
            return;
        }

        $this->plugin->getUtilItems()->giveItems($player);

        $player->setFood($player->getMaxFood());
        $player->setHealth($player->getMaxHealth());
        $player->removeAllEffects();
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->getCursorInventory()->clearAll();
        $player->getOffHandInventory()->clearAll(); /** @phpstan-ignore-line */
        $player->setGamemode(Player::SURVIVAL);

        $player->teleport(new Position($this->plugin->spawnPosX, $this->plugin->spawnPosY, $this->plugin->spawnPosZ, $server->getLevelByName($this->plugin->map)));
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
        $session = $this->plugin->getSessionManager()->getSession($player);
        $sessionManager = $this->plugin->getSessionManager();

        if ($sessionManager->hasSession($player)) {
            if ($session->isInTeam()) {
                if (!$session->isTeamLeader()) {
                    $session->removeFromTeam(); 
                } else {
                    $teamNumber = $session->getTeam()->getNumber();
                    foreach ($session->getTeam()->getMembers() as $member) {
                        $this->plugin->getSessionManager()->getSession($member)->removeFromTeam();
                    }
                    $this->plugin->getTeamManager()->disbandTeam($teamNumber);
                }
            }
            $this->plugin->getSessionManager()->removeSession($player);
            $session->setPlaying(false);
        }
        ScoreFactory::removeScore($player);

        if ($this->plugin->getHandler()->bossBar !== null) {
            $this->plugin->getHandler()->bossBar->hideFrom($player);
        }
    }
    
    /**
     * handleGamemode
     *
     * @param  PlayerGameModeChangeEvent $event
     * @return void
     */
    public function handleGamemode(PlayerGameModeChangeEvent $event): void
    {
        $player = $event->getPlayer();

        try {
            $player->removeAllEffects();
            $player->getInventory()->clearAll();
            $player->getArmorInventory()->clearAll();
            $player->getCursorInventory()->clearAll();
            $player->getOffHandInventory()->clearAll(); /** @phpstan-ignore-line */
        } catch(Exception $error) {
            // thows error sometimes when player joins
        }
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
            $player->sendMessage("§cYou cannot talk right now!");
            $event->setCancelled();
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
                    if ($event->getCause() === EntityDamageEvent::CAUSE_FALL) {
                        if ($this->plugin->getManager()->grace >= 1180) {
                            $event->setCancelled();
                        }
                    } else {
                        $event->setCancelled();
                    }
                }
                break;
            case PhaseChangeEvent::DEATHMATCH:
                if ($this->plugin->getManager()->deathmatch >= 890) {
                    $event->setCancelled();
                }
                break;
            default:
                if ($event instanceof EntityDamageByEntityEvent) {
                    $damager = $event->getDamager();
                    $victim = $event->getEntity();
    
                    if ($damager instanceof Player && $victim instanceof Player) {
                        $damagerSession = $this->plugin->getSessionManager()->getSession($damager);
                        $victimSession = $this->plugin->getSessionManager()->getSession($victim);
                        if ($damagerSession->isInTeam() && $victimSession->isInTeam() && $damagerSession->getTeam()->memberExists($victim)) {
                            $event->setCancelled();
                        }
                    }
                }
                break;
        }
    }

    /**
     * handleStartFall
     *
     * @param  EntityDamageEvent $event
     * @return void
     */
    public function handleStartFall(EntityDamageEvent $event): void
    {
        if ($event->getCause() === EntityDamageEvent::CAUSE_FALL) {
            switch ($this->plugin->getManager()->getPhase()) {
                case PhaseChangeEvent::DEATHMATCH:
                    if ($this->plugin->getManager()->deathmatch >= 890) {
                        $event->setCancelled();
                    }
                    break;
                case PhaseChangeEvent::GRACE:
                    if ($this->plugin->getManager()->grace >= 1180) {
                        $event->setCancelled();
                    }
                    break;
            }
        }
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
            case EntityRegainHealthEvent::CAUSE_EATING:
            case EntityRegainHealthEvent::CAUSE_CUSTOM:
                $event->setCancelled();
                break;
        }
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
        $eliminatedSession = $this->plugin->getSessionManager()->getSession($player);
        $sessionManager = $this->plugin->getSessionManager();
        
        $player->setGamemode(Player::SPECTATOR);
        $player->sendMessage("§eYou have been eliminated! Type /spectate to spectate a player.");

        if (!$sessionManager->hasSession($player)) return;
        
        if ($cause instanceof EntityDamageByEntityEvent) {
            $damager = $cause->getDamager();
            if ($damager instanceof Player) {
                if ($sessionManager->hasSession($damager)) {
                    $damagerSession = $this->plugin->getSessionManager()->getSession($damager);

                    $damagerSession->addEliminations();
                    $event->setDeathMessage("§c" . $player->getName() . "§7 (§f" . $eliminatedSession->getEliminations() . "§7)" . "§e was eliminated by §c" . $damager->getName() . "§7(§f" . $damagerSession->getEliminations() . "§7)");
                }
            }
        } else {
            $event->setDeathMessage("§c" . $player->getName() . "§7 (§f" . $eliminatedSession->getEliminations() . "§7)" . "§e has been eliminated somehow!");
        }
    }

    /**
     * handleRespawn
     *
     * @param  PlayerRespawnEvent $event
     * @return void
     */
    public function handleRespawn(PlayerRespawnEvent $event): void
    {
        $event->getPlayer()->teleport(new Position($this->plugin->spawnPosX, $this->plugin->spawnPosY, $this->plugin->spawnPosZ, $this->plugin->getServer()->getLevelByName($this->plugin->map)));
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

        switch ($event->getBlock()->getId()) { // drops
            case Block::LEAVES:
                $rand = mt_rand(0, 100);
                if ($event->getItem()->equals(Item::get(Item::SHEARS, 0, 1), false, false)) {
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
                $drops[] = Item::get(Item::PLANKS, 0, 4);
                $event->setDrops($drops);
                break;
            case Block::IRON_ORE:
                $drops[] = Item::get(Item::IRON_INGOT, 0, mt_rand(2, 4));
                $event->setDrops($drops);
                break;
            case Block::GOLD_ORE:
                $drops[] = Item::get(Item::GOLD_INGOT, 0, mt_rand(2, 4));
                $event->setDrops($drops);
                break;
        }

        switch ($event->getBlock()->getId()) { // vein mine
            case Block::IRON_ORE:
            case Block::GOLD_ORE:
            case Block::COAL_ORE:
            case Block::LAPIS_ORE:
            case Block::DIAMOND_ORE:
            case Block::REDSTONE_ORE:
            case Block::EMERALD_ORE:
            case Block::NETHER_QUARTZ_ORE:
            case Block::LOG:
                //$this->plugin->veinMine($event->getBlock(), $event->getItem(), $event->getPlayer()); //current issue
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
        
    /**
     * handleInteract
     *
     * @param  PlayerInteractEvent $event
     * @return void
     */
    public function handleInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();
        $item = $event->getItem();
        $server = $this->plugin->getServer();

        if ($event->getItem()->getId() === Item::SAPLING) {
			$pos = $event->getBlock()->getSide($event->getFace());

			switch ($pos->getSide(0)->getId()) {
				case Block::DIRT:
				case Block::GRASS:
				case Block::PODZOL:
					Tree::growTree($event->getBlock()->getLevel(), (int)$pos->x, (int)$pos->y, (int)$pos->z, new Random(mt_rand()), $item->getDamage());
					if ($player->isSurvival()) {
						$player->getInventory()->removeItem($item);
					}
					$event->setCancelled();
					break;
			}
		}

        // to do use waterdogpe api instead
        switch ($item->getId()) {
            case Item::BED:
                if ($item->getNamedTagEntry("Report")) {
                    $event->setCancelled();
                    $server->dispatchCommand($player, "report");
                }
                break;
            case Item::COMPASS:
                if ($item->getNamedTagEntry("Hub")) {
                    $event->setCancelled();
                    $server->dispatchCommand($player, "transfer hub");
                }
                break;
        }
    }
        
    /**
     * handleInventoryTransaction
     *
     * @param  InventoryTransactionEvent $event
     * @return void
     */
    public function handleInventoryTransaction(InventoryTransactionEvent $event): void
    {
        $transaction = $event->getTransaction();
        foreach ($transaction->getActions() as $action) {
            $item = $action->getSourceItem();

            if ($item->getNamedTagEntry("Report") || $item->getNamedTagEntry("Hub")) {
                $event->setCancelled();
            }

            if ($item->getId() === 444) {
                $event->setCancelled();
            }
        }
    }
    
    /**
     * handlePlayerDropItem
     *
     * @param  PlayerDropItemEvent $event
     * @return void
     */
    public function handlePlayerDropItem(PlayerDropItemEvent $event)
    {
        $item = $event->getItem();
        $itemID = $item->getId();

        if (!$this->plugin->getManager()->hasStarted()) {
            $event->setCancelled();
        }

        if ($item->getNamedTagEntry("Report") || $item->getNamedTagEntry("Hub")) {
            $event->setCancelled();
        }
    }

    /**
     * handlePhaseChange
     *
     * @param  PhaseChangeEvent $event
     * @return void
     */
    public function handlePhaseChange(PhaseChangeEvent $event): void
    {
        switch ($event->getOldPhase()) {
            case PhaseChangeEvent::COUNTDOWN:
                $player = $event->getPlayer();

                $player->getInventory()->addItem(Item::get(Item::BAKED_POTATO, 0, 16));
                $player->getInventory()->addItem(Item::get(Item::SAPLING, 0, 1));
                break;
        }
    }
}
