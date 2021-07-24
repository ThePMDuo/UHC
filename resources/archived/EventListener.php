<?php
declare(strict_types=1);

namespace AGTHARN\uhc\listener\type;

use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\network\mcpe\protocol\ServerSettingsRequestPacket;
use pocketmine\network\mcpe\protocol\ServerSettingsResponsePacket;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\network\mcpe\protocol\EmotePacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\level\generator\object\OakTree;
use pocketmine\level\generator\object\Tree;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerGameModeChangeEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
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

use AGTHARN\uhc\event\phase\PhaseChangeEvent;
use AGTHARN\uhc\util\form\FormManager;
use AGTHARN\uhc\session\SessionManager;
use AGTHARN\uhc\task\VPNAsyncCheck;
use AGTHARN\uhc\game\GameProperties;
use AGTHARN\uhc\game\GameManager;
use AGTHARN\uhc\Main;

use jackmd\scorefactory\ScoreFactory;

use Exception;

class EventListener implements Listener
{
    /** @var Main */
    private $plugin;
    
    /** @var GameManager */
    private $gameManager;
    /** @var SessionManager */
    private $sessionManager;
    /** @var GameProperties */
    private $gameProperties;
            
    /**
     * __construct
     *
     * @param  Main $plugin
     * @return void
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;

        $this->gameManager = $plugin->getClass('GameManager');
        $this->sessionManager = $plugin->getClass('SessionManager');
        $this->gameProperties = $plugin->getClass('GameProperties');
    }
    
    /**
     * handleLogin
     *
     * @param  PlayerLoginEvent $event
     * @return void
     */
    public function handleLogin(PlayerLoginEvent $event): void
    {
        $player = $event->getPlayer();
        $name = $player->getName();

        $this->sessionManager->createSession($player);
        $this->gameProperties->allPlayers[$player->getName()]['pos'] = $this->plugin->getClass('UtilPlayer')->rGetPos($player);
        $this->gameProperties->allPlayers[$player->getName()]['afk_time'] = 0;

        if (!$player->hasPermission('uhc.vpn.bypass')) {
            $playerIP = $player->getXuid() === '' ? (isset($this->gameProperties->waterdogIPs[$name]) ? $this->gameProperties->waterdogIPs[$name] : 'error') : $player->getAddress();
            
            $this->plugin->getServer()->getAsyncPool()->submitTask(new VPNAsyncCheck($this->plugin->getClass('AntiVPN'), $playerIP, $name, [
                'check2.key' => '',
                'check4.key' => '',
                'check5.key' => 'demo',
                'check7.key' => 'aycnsvoAEjzp9YaDpDXqoPgF6Ek2SuIT',
                'check7.mobile' => true,
                'check7.fast' => false,
                'check7.strictness' =>  0,
                'check7.lighter_penalties' => true,
                'check8.key' => 'MTA5NzE6Q0Ezamh2ZUIyNlhHMlVHRWNhMVlSVXRqQk1ha3Uybm4=',
                'check9.key' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.WzEyNzksMTYwNDg4MjcwNCwyMDAwXQ.l7RyldlbPndXiVAyd_pNdDk6Y0nSF9Mh9N70XdA5RqY',
                'check10.key' => '8f068e4c4776db'
            ]));
            if (isset($this->gameProperties->waterdogIPs[$name])) {
                unset($this->gameProperties->waterdogIPs[$name]);
            }
        }

        switch ($this->gameManager->getPhase()) {
            case PhaseChangeEvent::WAITING:
                // since solo we wont handle joining available teams
                $this->sessionManager->getSession($player)->addToTeam($this->plugin->getClass('TeamManager')->createTeam($player));
                break;
            //case PhaseChangeEvent::COUNTDOWN:
                //if ($this->gameManager->getCountdownTimer() > 32) {
                    //$this->sessionManager->getSession($player)->addToTeam($this->plugin->getClass('TeamManager')->createTeam($player));
                //}
                //break;
            case PhaseChangeEvent::RESET:
                $player->kick('SERVER RESETTING: IF IT TAKES LONGER THAN 10 SECONDS, PLEASE CONTACT AN ADMIN!');
                break;
            default:
                $player->setGamemode(Player::SPECTATOR);
                $player->sendMessage(GameProperties::PREFIX_COSMIC . '§eType §b/spectate §eto spectate a player.');
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
        $session = $this->sessionManager->getSession($player);

        $numberPlayingMax = $this->gameProperties->startingPlayers === 0 ? $this->plugin->getServer()->getMaxPlayers() : $this->gameProperties->startingPlayers;
        $numberPlaying = $this->gameManager->hasStarted() ? count($this->sessionManager->getPlaying()) : count($this->plugin->getServer()->getOnlinePlayers());
        
        $team = $session->getTeam() ?? null;
        $teamNumber = $team->getNumber() !== null ? (string)$team->getNumber() : 'NO TEAM';

        $server = $this->plugin->getServer();
        $mUsage = Process::getAdvancedMemoryUsage();

        $event->setJoinMessage(GameProperties::PREFIX_JAX . '§e' . $player->getName() . ' has joined the server! §7(' . $numberPlaying . '/' . $numberPlayingMax . ') (#' . $teamNumber . ')');
        if (!$this->plugin->getOperational()) {
            $player->kick($this->plugin->getOperationalColoredMessage() . ': SERVER RESETTING! SHOULD NOT TAKE LONGER THAN 60 SECONDS!');
            return;
        }

        $player->sendMessage('Welcome to UHC! Build ' . $this->gameProperties->buildNumber . ' © 2021 MineUHC');
        $player->sendMessage('UHC-' . $this->gameProperties->uhcServer . ': ' . $this->plugin->getOperationalColoredMessage());
        $player->sendMessage('THREADS: ' . Process::getThreadCount() . ' | RAM: ' . number_format(round(($mUsage[2] / 1024) / 1024, 2), 2) . ' MB.');
        $player->sendMessage('NODE: ' . $this->gameProperties->node);
        
        $this->plugin->getClass('UtilPlayer')->playerJoinReset($player);
        //$this->plugin->getClass('Capes')->createNormalCape($player);

        $this->plugin->getClass('FormManager')->getForm($player, FormManager::NEWS_FORM)->sendNewsForm($player);

        $this->plugin->getClass('Database')->registerPlayer($player);
        $this->plugin->getClass('Database')->giveCape($player);
    
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
        $session = $this->sessionManager->getSession($player);
        $numberPlayingMax = $this->gameProperties->startingPlayers === 0 ? $this->plugin->getServer()->getMaxPlayers() : $this->gameProperties->startingPlayers;
        $numberPlaying = $this->gameManager->hasStarted() ? count($this->sessionManager->getPlaying()) : count($this->plugin->getServer()->getOnlinePlayers());
        
        $team = $session->getTeam() ?? null;
        $teamNumber = $team->getNumber() !== null ? (string)$team->getNumber() : 'NO TEAM';

        $event->setQuitMessage(GameProperties::PREFIX_JAX . '§e' . $player->getName() . ' has left the server! §7(' . $numberPlaying . '/' . $numberPlayingMax . ') (#' . $teamNumber . ')');
        if ($this->sessionManager->hasSession($player)) {
            if ($session->isInTeam()) {
                if (!$session->isTeamLeader()) {
                    $session->removeFromTeam(); 
                } else {
                    foreach ($team->getMembers() as $member) {
                        $this->sessionManager->getSession($member)->removeFromTeam();
                    }
                    $this->plugin->getClass('TeamManager')->disbandTeam($team->getNumber());
                }
            }
            $this->sessionManager->removeSession($player);
            $session->setPlaying(false);
        }
        ScoreFactory::removeScore($player);
        if ($this->gameManager->hasStarted()) {
            $this->plugin->getClass('DeathChest')->spawnChest($player);
        }
        unset($this->gameProperties->allPlayers[$player->getName()]);
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
            $this->plugin->getClass('UtilPlayer')->resetPlayer($player);
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
        if ($this->gameManager->isGlobalMuteEnabled() && !$player->isOp()) {
            $player->sendMessage(GameProperties::PREFIX_COSMIC . '§cYou cannot talk right now!');
            $event->setCancelled();
        }

        if ($this->plugin->getClass('Profanity')->hasProfanity($event->getMessage())) {
            $player->sendMessage(GameProperties::PREFIX_COSMIC . '§cPlease watch your language!');
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
        if (!$this->gameManager->hasStarted()) {
            $event->setCancelled();
            return;
        }
        switch ($this->gameManager->getPhase()) {
            case PhaseChangeEvent::GRACE:
                if ($entity instanceof Player) {
                    if ($event->getCause() === EntityDamageEvent::CAUSE_FALL) {
                        if ($this->gameManager->getGraceTimer() >= 1180) { // for elytra
                            $event->setCancelled();
                        }
                    } else {
                        $event->setCancelled();
                    }
                }
                break;
            case PhaseChangeEvent::DEATHMATCH:
                if ($this->gameManager->getDeathmatchTimer() >= 890) {
                    $event->setCancelled();
                }
                break;
            default:
                if ($event instanceof EntityDamageByEntityEvent) {
                    $damager = $event->getDamager();
                    $victim = $event->getEntity();
    
                    if ($damager instanceof Player && $victim instanceof Player) {
                        $damagerSession = $this->sessionManager->getSession($damager);
                        $victimSession = $this->sessionManager->getSession($victim);
                        if ($damagerSession->isInTeam() && $victimSession->isInTeam() && $damagerSession->getTeam()->memberExists($victim)) {
                            $event->setCancelled();
                        }
                    }
                }
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
        $eliminatedSession = $this->sessionManager->getSession($player);
        
        if ($this->gameManager->hasStarted()) {
            $this->plugin->getClass('DeathChest')->spawnChest($player);
            $event->setDrops([]);
        }
        $player->setGamemode(Player::SPECTATOR);
        $player->sendMessage(GameProperties::PREFIX_COSMIC . '§eYou have been eliminated! Type §b/spectate §eto spectate a player.');

        if (!$this->sessionManager->hasSession($player)) return;
        if ($cause instanceof EntityDamageByEntityEvent) {
            $damager = $cause->getDamager();
            if ($damager instanceof Player) {
                if ($this->sessionManager->hasSession($damager)) {
                    $damagerSession = $this->sessionManager->getSession($damager);

                    $event->setDeathMessage(GameProperties::PREFIX_JAX . '§c' . $player->getName() . '§7 (§f' . $eliminatedSession->getEliminations() . '§7) §ewas eliminated by §c' . $damager->getName() . '§7(§f' . $damagerSession->getEliminations() . '§7)');
                    $damagerSession->addEliminations();
                }
            }
        } else {
            $event->setDeathMessage(GameProperties::PREFIX_JAX . '§c' . $player->getName() . '§7 (§f' . $eliminatedSession->getEliminations() . '§7) §ehas been eliminated somehow!');
        }
    }
    
    /**public function handleEntityRegain(EntityRegainHealthEvent $event): void
    {
        switch ($event->getRegainReason()) {
            case EntityRegainHealthEvent::CAUSE_SATURATION:
            case EntityRegainHealthEvent::CAUSE_EATING:
                $entity = $event->getEntity();
                if ($entity instanceof Player) {
                    if (!in_array($entity->getName(), $this->plugin->entityRegainNote)) {
                        $entity->sendMessage(GameProperties::PREFIX_COSMIC . '§bNOTE: §rYou may be healing from Saturation but this is a client-side bug and the regenerated health is fake.');
                        $this->plugin->entityRegainNote[] = $entity->getName();
                    }
                    $event->setCancelled();
                }
                break;
        }
    }*/
    
    /**
     * handlePlayerLevelChange
     *
     * @param  EntityLevelChangeEvent $event
     * @return void
     */
    public function handlePlayerLevelChange(EntityLevelChangeEvent $event): void
    {
        $entity = $event->getEntity();
        if ($entity instanceof Player) {
            $level = $this->plugin->getServer()->getLevelByName($this->gameProperties->map);
            if ($event->getTarget() !== $level) {
                $entity->teleport(new Position($this->gameProperties->spawnPosX, $this->gameProperties->spawnPosY, $this->gameProperties->spawnPosZ, $level));
            }
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
        $event->getPlayer()->teleport(new Position($this->gameProperties->spawnPosX, $this->gameProperties->spawnPosY, $this->gameProperties->spawnPosZ, $this->plugin->getServer()->getLevelByName($this->gameProperties->map)));
    }
    
    /**
     * handleBreak
     *
     * @param  BlockBreakEvent $event
     * @return void
     */
    public function handleBreak(BlockBreakEvent $event): void
    {
        if (!$this->gameManager->hasStarted()) {
            $event->setCancelled();
            return;
        }

        switch ($event->getBlock()->getId()) { // drops
            case Block::LEAVES:
            case Block::LEAVES2:
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
                $drops[] = Item::get(Item::DIAMOND, 0, mt_rand(1, 2));
                $event->setDrops($drops);
                break;
        }

        if ($this->plugin->getClass('UtilPlayer')->playerTreeChop($event->getPlayer(), $event->getBlock(), $event->getItem())) {
            $event->setCancelled();
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
        if (!$this->gameManager->hasStarted()) {
            $event->setCancelled();
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
        if (!$this->gameManager->hasStarted()) {
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

        // to do use waterdogpe api instead
        switch ($item->getId()) {
            case Item::BED:
                if ($item->getNamedTagEntry('Report')) {
                    $event->setCancelled();
                    $this->plugin->getClass('FormManager')->getForm($player, FormManager::REPORT_FORM)->sendReportForm($player);
                }
                break;
            case Block::WOOL:
                if ($item->getNamedTagEntry('Capes')) {
                    $event->setCancelled();
                    $this->plugin->getClass('FormManager')->getForm($player, FormManager::CAPE_FORM)->sendCapesMenuForm($player);
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
                    $inv->setContents($this->plugin->getClass('ChestSort')->sortChest(array_values($inv->getContents(false))));
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

        if ($item->getNamedTagEntry('Report') || $item->getNamedTagEntry('Hub')) {
            $event->setCancelled();
        }
        if (!$this->gameManager->hasStarted()) {
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
            switch ($pk->type) {
                case TextPacket::TYPE_TIP:
                case TextPacket::TYPE_POPUP:
                case TextPacket::TYPE_JUKEBOX_POPUP:
                    return;
                case TextPacket::TYPE_TRANSLATION;
                    $pk->message = $this->plugin->getClass('UtilPlayer')->toThin($pk->message);
                    break;
                default:
                    $pk->message .= TextFormat::ESCAPE . '　';
                    break;
            }
            return;
        }
        if ($pk instanceof AvailableCommandsPacket) {
            foreach ($pk->commandData as $name => $commandData) {
                $commandData->commandDescription = $this->plugin->getClass('UtilPlayer')->toThin($commandData->commandDescription);
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
        $msg = explode(' ', trim($message));
        $m = substr("$message", 0, 1);
        $whitespace_check = substr($message, 1, 1);
        $slash_check = substr($msg[0], -1, 1);
        $quote_mark_check = substr($message, 1, 1) . substr($message, -1, 1);

        if ($m == '/') {
            if ($whitespace_check === ' ' or $whitespace_check === '\\' or $slash_check === '\\' or $quote_mark_check === '""') {
                $event->setCancelled();
            }
        }
    }
        
    /**
     * handleDataPacketReceive
     *
     * @param  DataPacketReceiveEvent $event
     * @return void
     */
    public function handleDataPacketReceive(DataPacketReceiveEvent $event): void
    {
        $packet = $event->getPacket();
        $player = $event->getPlayer();

        if ($packet instanceof EmotePacket) {
            $emoteId = $packet->getEmoteId();
            $this->plugin->getServer()->broadcastPacket($player->getViewers(), EmotePacket::create($player->getId(), $emoteId, 1 << 0));
        }

        if ($packet instanceof LoginPacket) {   
            // still get IP cuz we can still check if trying to ban evade or smth
            if (isset($packet->clientData['Waterdog_IP'])) {
                $this->waterdogIPs[$packet->username] = $packet->clientData['Waterdog_IP'];
                $player->kick('uh oh!!');
            }
        }

        // currently NOT WORKING (help ok)
        if ($packet instanceof ServerSettingsRequestPacket) {
            $packet = new ServerSettingsResponsePacket;
            $packet->formData = file_get_contents($this->plugin->getDataFolder() . 'settings/setting.json');
            $packet->formId = 8694;

            $player->dataPacket($packet);
        } elseif ($packet instanceof ModalFormResponsePacket) {
            $formId = $packet->formId;
            if ($formId !== 8694) {
                return;
            }
            $formData = (array) json_decode($packet->formData, true);
            $msg = $formData[1];
            $button = $formData[2];
            if ($button) {
                $player->sendMessage($msg);
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
        // nothing :)
    }
}
