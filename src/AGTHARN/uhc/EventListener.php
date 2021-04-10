<?php
declare(strict_types=1);

namespace AGTHARN\uhc;

use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
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
use pocketmine\utils\TextFormat as TF;
use pocketmine\utils\Process;
use pocketmine\block\Block;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\Player;

use AGTHARN\uhc\event\PhaseChangeEvent;
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
                $player->setGamemode(Player::SURVIVAL);
                // since solo we wont handle joining available teams
                $session = $this->plugin->getSessionManager()->getSession($player);
                $session->addToTeam($this->plugin->getTeamManager()->createTeam($player));
                break;
            case PhaseChangeEvent::RESET:
                $player->kick("SERVER RESETTING: IF IT TAKES LONGER THAN 10 SECONDS, PLEASE CONTACT AN ADMIN!");
                break;
            default:
                if ($sessionManager->hasSession($player)) {
                    $session = $this->plugin->getSessionManager()->getSession($player);
                    $session->setPlaying(false);
                }
                $player->setGamemode(3);
                $player->sendMessage(TF::YELLOW . "Type /spectate to spectate a player.");
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
        $bossBar = $this->plugin->getBossBar();

        $player->sendMessage("Welcome to UHC! Build " . $this->plugin->buildNumber);
        $player->sendMessage("UHC-" . $this->plugin->uhcServer . ": " . $this->plugin->getOperationalMessage());
        $player->sendMessage("THREADS: " . Process::getThreadCount() . " RAM USAGE: " . number_format(round(($mUsage[1] / 1024) / 1024, 2), 2) . " MB");

        if (!$this->plugin->getOperational()) {
            $player->kick($this->plugin->getOperationalMessage() . ": UHC LOADER HAS FAILED! PLEASE CONTACT AN ADMIN!");
            return;
        }

        $player->setFood($player->getMaxFood());
        $player->setHealth($player->getMaxHealth());
        $player->removeAllEffects();
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->getCursorInventory()->clearAll();

        $bossBar->setTitle("§fWAITING...");
        $bossBar->setPercentage(1.0);
        $bossBar->addPlayer($player);
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
        
        $player->setGamemode(3);
        $player->sendMessage(TF::YELLOW . "You have been eliminated! Type /spectate to spectate a player.");

        if (!$sessionManager->hasSession($player)) return;
        
        if ($cause instanceof EntityDamageByEntityEvent) {
            $damager = $cause->getDamager();
            if ($damager instanceof Player) {
                if ($sessionManager->hasSession($damager)) {
                    $damagerSession = $this->plugin->getSessionManager()->getSession($damager);

                    $damagerSession->addEliminations();
                    $event->setDeathMessage(TF::RED . $player->getName() . TF::GRAY . " (" . TF::WHITE . $eliminatedSession->getEliminations() . TF::GRAY . ")" . TF::YELLOW . " was eliminated by " . TF::RED . $damager->getName() . TF::GRAY . "(" . TF::WHITE . $damagerSession->getEliminations() . TF::GRAY . ")");
                }
            }
        } else {
            $event->setDeathMessage(TF::RED . $player->getName() . TF::GRAY . " (" . TF::WHITE . $eliminatedSession->getEliminations() . TF::GRAY . ")" . TF::YELLOW . " has been eliminated somehow!");
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

        switch ($event->getBlock()->getId()) {
            case Block::LEAVES:
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
                $drops[] = Item::get(Item::PLANKS, 0, 4);
                $event->setDrops($drops);
                break;
            case Block::IRON_ORE:
                $drops[] = Item::get(Item::IRON_INGOT, 0, 2);
                $event->setDrops($drops);
                break;
            case Block::GOLD_ORE:
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
                    if ($this->plugin->getManager()->deathmatch >= 850) {
                        $event->setCancelled();
                    }
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
}
