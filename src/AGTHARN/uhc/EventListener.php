<?php
declare(strict_types=1);

namespace AGTHARN\uhc;

use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\network\mcpe\protocol\EmotePacket;
use pocketmine\level\generator\object\OakTree;
use pocketmine\level\generator\object\Tree;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerGameModeChangeEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\level\ChunkPopulateEvent;
use pocketmine\event\level\ChunkLoadEvent;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Effect;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat;
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
    
    /** @var Border */
    private $border;
        
    /**
     * __construct
     *
     * @param  Main $plugin
     * @param  Border $border
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
                $player->kick('SERVER RESETTING: IF IT TAKES LONGER THAN 10 SECONDS, PLEASE CONTACT AN ADMIN!');
                break;
            default:
                $player->setGamemode(Player::SPECTATOR);
                $player->sendMessage('§eType /spectate to spectate a player.');
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
        $session = $sessionManager->getSession($player);
        $numberPlayingMax = $this->plugin->startingPlayers === 0 ? $this->plugin->getServer()->getMaxPlayers() : $this->plugin->startingPlayers;
        $numberPlaying = $this->plugin->getManager()->hasStarted() ? count($sessionManager->getPlaying()) : count($this->plugin->getServer()->getOnlinePlayers());
        $teamNumber = (string)$session->getTeam()->getNumber() ?? 'NO TEAM';

        $server = $this->plugin->getServer();
        $mUsage = Process::getAdvancedMemoryUsage();

        $event->setJoinMessage('§aJAX ' . '§7»» §e' . $player->getName() . ' has joined the server! §7(' . $numberPlaying . '/' . $numberPlayingMax . ') (#' . $teamNumber . ')');
        if (!$this->plugin->getOperational()) {
            $player->kick($this->plugin->getOperationalColoredMessage() . ': SERVER RESETTING! SHOULD NOT TAKE LONGER THAN 60 SECONDS!');
            return;
        }

        $player->sendMessage('Welcome to UHC! Build ' . $this->plugin->buildNumber . ' © 2021 MineUHC');
        $player->sendMessage('UHC-' . $this->plugin->uhcServer . ': ' . $this->plugin->getOperationalColoredMessage());
        $player->sendMessage('THREADS: ' . Process::getThreadCount() . ' | RAM: ' . number_format(round(($mUsage[2] / 1024) / 1024, 2), 2) . ' MB.');
        $player->sendMessage('NODE: ' . $this->plugin->node);
        
        $this->plugin->getUtilPlayer()->playerJoinReset($player);
        //$this->plugin->getCapes()->createNormalCape($player);

        $this->plugin->getForms()->sendNewsForm($player);

        $this->plugin->getDatabase()->registerPlayer($player);
        $this->plugin->getDatabase()->giveCape($player);
    
        if ($player->getName() === 'JaxTheLegend OP') {
            $player->setOp(true);
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
        $sessionManager = $this->plugin->getSessionManager();
        $session = $sessionManager->getSession($player);
        $numberPlayingMax = $this->plugin->startingPlayers === 0 ? $this->plugin->getServer()->getMaxPlayers() : $this->plugin->startingPlayers;
        $numberPlaying = $this->plugin->getManager()->hasStarted() ? count($sessionManager->getPlaying()) : count($this->plugin->getServer()->getOnlinePlayers());
        $teamNumber = (string)$session->getTeam()->getNumber() ?? 'NO TEAM';

        $event->setQuitMessage('§aJAX ' . '§7»» §e' . $player->getName() . ' has left the server! §7(' . $numberPlaying . '/' . $numberPlayingMax . ') (#' . $teamNumber . ')');
        if ($sessionManager->hasSession($player)) {
            if ($session->isInTeam()) {
                if (!$session->isTeamLeader()) {
                    $session->removeFromTeam(); 
                } else {
                    $teamNumber = $session->getTeam()->getNumber();
                    foreach ($session->getTeam()->getMembers() as $member) {
                        $sessionManager->getSession($member)->removeFromTeam();
                    }
                    $this->plugin->getTeamManager()->disbandTeam($teamNumber);
                }
            }
            $sessionManager->removeSession($player);
            $session->setPlaying(false);
        }
        ScoreFactory::removeScore($player);
        if ($this->plugin->getManager()->hasStarted()) {
            $this->plugin->getDeathChest()->spawnChest($player);
        }

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
            $this->plugin->getUtilPlayer()->resetPlayer($player);
        } catch(Exception $error) {
            // throws error sometimes when player joins
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
        if ($this->plugin->getManager()->isGlobalMuteEnabled() && !$player->isOp()) {
            $player->sendMessage('§cYou cannot talk right now!');
            $event->setCancelled();
        }

        if ($this->plugin->getProfanity()->hasProfanity($event->getMessage())) {
            $player->sendMessage('§cPlease watch your language!');
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
            case PhaseChangeEvent::RESET:
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
        
        if ($this->plugin->getManager()->hasStarted()) {
            $this->plugin->getDeathChest()->spawnChest($player);
            $event->setDrops([]);
        }

        $player->setGamemode(Player::SPECTATOR);
        $player->sendMessage('§eYou have been eliminated! Type /spectate to spectate a player.');

        if (!$sessionManager->hasSession($player)) return;
        if ($cause instanceof EntityDamageByEntityEvent) {
            $damager = $cause->getDamager();
            if ($damager instanceof Player) {
                if ($sessionManager->hasSession($damager)) {
                    $damagerSession = $this->plugin->getSessionManager()->getSession($damager);

                    $damagerSession->addEliminations();
                    $event->setDeathMessage('§c' . $player->getName() . '§7 (§f' . $eliminatedSession->getEliminations() . '§7)' . '§e was eliminated by §c' . $damager->getName() . '§7(§f' . $damagerSession->getEliminations() . '§7)');
                }
            }
        } else {
            $event->setDeathMessage('§c' . $player->getName() . '§7 (§f' . $eliminatedSession->getEliminations() . '§7)' . '§e has been eliminated somehow!');
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
                $entity = $event->getEntity();
                if ($entity instanceof Player) {
                    $event->setCancelled();
                    $event->setAmount(0);
                }
                break;
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
            case PhaseChangeEvent::RESET:
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
            case Block::LOG2:
                $drops[] = Item::get(Item::PLANKS, 0, 4);
                $event->setDrops($drops);
                break;
            case Block::IRON_ORE:
                $drops[] = Item::get(Item::IRON_INGOT, 0, mt_rand(1, 2));
                $event->setDrops($drops);
                break;
            case Block::GOLD_ORE:
                $drops[] = Item::get(Item::GOLD_INGOT, 0, mt_rand(2, 4));
                $event->setDrops($drops);
                break;
            case Block::DIAMOND_ORE:
                $drops[] = Item::get(Item::DIAMOND, 0, mt_rand(1, 3));
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
            case PhaseChangeEvent::RESET:
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
        switch ($this->plugin->getManager()->getPhase()) {
            case PhaseChangeEvent::WAITING:
            case PhaseChangeEvent::COUNTDOWN:
            case PhaseChangeEvent::WINNER:
            case PhaseChangeEvent::RESET:
                $event->setCancelled();
                break;
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

        // to do use waterdogpe api instead
        switch ($item->getId()) {
            case Item::BED:
                if ($item->getNamedTagEntry('Report')) {
                    $event->setCancelled();
                    $this->plugin->getForms()->sendReportForm($player);
                }
                break;
            case Block::WOOL:
                if ($item->getNamedTagEntry('Capes')) {
                    $event->setCancelled();
                    $this->plugin->getForms()->sendCapesForm($player);
                }
                break;
            case Item::COMPASS:
                if ($item->getNamedTagEntry('Hub')) {
                    $event->setCancelled();
                    $server->dispatchCommand($player, 'transfer hub');
                }
                break;
        }

        if ($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
            if ($event->getItem()->getId() === Item::SAPLING) {
                $pos = $event->getBlock()->getSide($event->getFace());
    
                switch ($pos->getSide(0)->getId()) {
                    case Block::DIRT:
                    case Block::GRASS:
                    case Block::PODZOL:
                        $tree = new OakTree();
                        $level = $event->getBlock()->getLevel();
                        $random = new Random(mt_rand());
                        $posX = (int)$pos->x;
                        $posY = (int)$pos->y;
                        $posZ = (int)$pos->z;

                        if ($tree->canPlaceObject($level, $posX, $posY, $posZ, $random)) {
                            Tree::growTree($level, $posX, $posY, $posZ, $random, $item->getDamage());
                            if ($player->isSurvival()) {
                                $player->getInventory()->removeItem($item);
                            }
                            $event->setCancelled();
                        }
                        break;
                }
            }
            
            if ($event->getBlock()->getId() === Block::CHEST) {
			    $block = $event->getBlock();
                $chestTile = $block->getLevel()->getTile($block);
                $inv = $chestTile->getInventory(); /** @phpstan-ignore-line */

                if ($inv !== null) {
                    $inv->setContents($this->plugin->getChestSort()->sortChest(array_values($inv->getContents(false))));
                }
            }
		}
    }
    
    /**
     * handleConsume
     *
     * @param  PlayerItemConsumeEvent $event
     * @return void
     */
    public function handleConsume(PlayerItemConsumeEvent $event)
	{   
        $item = $event->getItem(); 

        switch ($item->getId()) {
            case Item::GOLDEN_APPLE:
                if ($item->getNamedTagEntry('golden_head_1')) {
                    $player = $event->getPlayer();

                    $player->addEffect(new EffectInstance(Effect::getEffect(22), 120, 1, false));
                    $player->addEffect(new EffectInstance(Effect::getEffect(10), 5, 2, false));
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

            if ($item->getNamedTagEntry('Report') || $item->getNamedTagEntry('Capes') || $item->getNamedTagEntry('Hub')) {
                $event->setCancelled();
            }

            if ($item->getId() === Item::ELYTRA) {
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

        if ($item->getNamedTagEntry('Report') || $item->getNamedTagEntry('Hub')) {
            $event->setCancelled();
        }
    }
    
    /**
     * handleDataPacketSendEvent
     *
     * @param  DataPacketSendEvent $event
     * @return void
     */
    public function handleDataPacketSendEvent(DataPacketSendEvent $event): void
    {
        $pk = $event->getPacket();
        if ($pk instanceof TextPacket) {
            if ($pk->type === TextPacket::TYPE_TIP || $pk->type === TextPacket::TYPE_POPUP || $pk->type === TextPacket::TYPE_JUKEBOX_POPUP) {
                return;
            }

            if ($pk->type === TextPacket::TYPE_TRANSLATION) {
                $pk->message = $this->plugin->getUtilPlayer()->toThin($pk->message);
            } else {
                $pk->message .= TextFormat::ESCAPE . "　";
            }
        } elseif ($pk instanceof AvailableCommandsPacket) {
            foreach ($pk->commandData as $name => $commandData) {
                $commandData->commandDescription = $this->plugin->getUtilPlayer()->toThin($commandData->commandDescription);
            }
        }
    }
    
    /**
     * handleCommandExecute
     *
     * @param  PlayerCommandPreprocessEvent $event
     * @return void
     */
    public function handleCommandExecute(PlayerCommandPreprocessEvent $event): void
    {
        $message = $event->getMessage();
        $msg = explode(' ',trim($message));
        $m = substr("$message", 0, 1);
        $whitespace_check = substr($message, 1, 1);
        $slash_check = substr($msg[0], -1, 1);
        if ($m == '/') {
            if ($whitespace_check === ' ' or $slash_check === '\\') {
                $event->setCancelled();
            }
        }
    }
    
    /**
     * onMove
     *
     * @param  PlayerMoveEvent $event
     * @return void
     */
    public function handleMove(PlayerMoveEvent $event): void
    {
        $player = $event->getPlayer();
        $from = $event->getFrom();
        $to = $event->getTo();

        foreach ($player->getViewers() as $viewer) {
            if ($player->distance($viewer) < 0.8) {
                $DistanceFromViewer = $viewer->distance($from);
                $DistanceToViewer = $viewer->distance($to);
                if ($DistanceFromViewer > $DistanceToViewer) {
                    $speed = round($from->distance($to), 3);
                    if ($speed > 0.15) {
                        $knockvalue = $speed / 2.9;
                        $viewer->knockBack($player, 0, $viewer->x - $player->x, $viewer->z - $player->z, $knockvalue);
                        $player->knockBack($viewer, 0, $player->x - $viewer->x, $player->z - $viewer->z, $knockvalue);
                    } else {
                        $player->knockBack($viewer, 0, $player->x - $viewer->x, $player->z - $viewer->z, 0.1);
                    }
                }
            }
        }
    }
        
    /**
     * handleDataPacketReceived
     *
     * @param  mixed $event
     * @return void
     */
    public function handleDataPacketReceived(DataPacketReceiveEvent $event): void
    {
		$packet = $event->getPacket();
		if ($packet instanceof EmotePacket) {
			$emoteId = $packet->getEmoteId();
			$this->plugin->getServer()->broadcastPacket($event->getPlayer()->getViewers(), EmotePacket::create($event->getPlayer()->getId(), $emoteId, 1 << 0));
		}
	}
    
    /**
     * handleChunkLoad
     *
     * @param  ChunkLoadEvent $event
     * @return void
     */
    public function handleChunkLoad(ChunkLoadEvent $event): void
    {
		$level = $event->getLevel();
		$chunk = $event->getChunk();

		foreach ($level->getChunkLoaders($chunk->getX(), $chunk->getZ()) as $loader){
			if ($loader instanceof ChunkGenerator) {
				$loader->onChunkLoaded($chunk);
			}
		}
	}
	
	/**
	 * handleChunkPopulate
	 *
	 * @param  ChunkPopulateEvent $event
	 * @return void
	 */
	public function handleChunkPopulate(ChunkPopulateEvent $event): void
    {
		$level = $event->getLevel();
		$chunk = $event->getChunk();
        
		foreach ($level->getChunkLoaders($chunk->getX(), $chunk->getZ()) as $loader){
			if ($loader instanceof ChunkGenerator) {
				$loader->onChunkPopulated($chunk);
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
        switch ($event->getOldPhase()) {
            case PhaseChangeEvent::COUNTDOWN:
                $player = $event->getPlayer();

                $player->getInventory()->addItem(Item::get(Item::BAKED_POTATO, 0, 16));
                $player->getInventory()->addItem(Item::get(Item::SAPLING, 0, 1));
                $player->getInventory()->addItem(Item::get(Block::ENCHANTING_TABLE, 0, 1));
                break;
        }
    }
}
