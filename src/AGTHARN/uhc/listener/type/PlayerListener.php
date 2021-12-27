<?php
declare(strict_types=1);

/**
 * ███╗░░░███╗██╗███╗░░██╗███████╗██╗░░░██╗██╗░░██╗░█████╗░
 * ████╗░████║██║████╗░██║██╔════╝██║░░░██║██║░░██║██╔══██╗
 * ██╔████╔██║██║██╔██╗██║█████╗░░██║░░░██║███████║██║░░╚═╝
 * ██║╚██╔╝██║██║██║╚████║██╔══╝░░██║░░░██║██╔══██║██║░░██╗
 * ██║░╚═╝░██║██║██║░╚███║███████╗╚██████╔╝██║░░██║╚█████╔╝
 * ╚═╝░░░░░╚═╝╚═╝╚═╝░░╚══╝╚══════╝░╚═════╝░╚═╝░░╚═╝░╚════╝░
 * 
 * Copyright (C) 2020-2021 AGTHARN
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace AGTHARN\uhc\listener\type;

use pocketmine\world\generator\object\TreeFactory;
use pocketmine\event\entity\EntityDamageByEntityEvent;
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
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\Listener;
use pocketmine\utils\Process;
use pocketmine\utils\Random;
use pocketmine\block\utils\TreeType;
use pocketmine\block\tile\Chest;
use pocketmine\block\VanillaBlocks;
use pocketmine\world\Position;
use pocketmine\item\VanillaItems;
use pocketmine\player\GameMode;
use pocketmine\player\Player;

use AGTHARN\uhc\event\phase\PhaseChangeEvent;
use AGTHARN\uhc\util\form\FormManager;
use AGTHARN\uhc\session\SessionManager;
use AGTHARN\uhc\task\VPNAsyncCheck;
use AGTHARN\uhc\game\GameProperties;
use AGTHARN\uhc\game\GameManager;
use AGTHARN\uhc\Main;

use jackmd\scorefactory\ScoreFactory;

class PlayerListener implements Listener
{
    /** @var Main */
    private Main $plugin;
    
    /** @var GameManager */
    private GameManager $gameManager;
    /** @var SessionManager */
    private SessionManager $sessionManager;
    /** @var GameProperties */
    private GameProperties $gameProperties;
            
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
            $playerIP = $player->getXuid() === '' ? (isset($this->gameProperties->waterdogIPs[$name]) ? $this->gameProperties->waterdogIPs[$name] : 'error') : $player->getNetworkSession->getAddress();
            
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
                $player->setGamemode(GameMode::SPECTATOR());
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
        $server = $this->plugin->getServer();

        $player = $event->getPlayer();
        $playerSession = $this->sessionManager->getSession($player);

        $numberPlayingMax = $this->gameProperties->startingPlayers === 0 ? $server->getMaxPlayers() : $this->gameProperties->startingPlayers;
        $numberPlaying = $this->gameManager->hasStarted() ? count($this->sessionManager->getPlaying()) : count($server->getOnlinePlayers());
        
        $team = $playerSession->getTeam() ?? null;
        $teamNumber = $team->getNumber() !== null ? (string)$team->getNumber() : 'NO TEAM';

        $mUsage = Process::getAdvancedMemoryUsage();

        $event->setJoinMessage(GameProperties::PREFIX_JAX . '§e' . $player->getName() . ' has joined the server! §7(' . $numberPlaying . '/' . $numberPlayingMax . ') (#' . $teamNumber . ')');
        if (!$this->plugin->getOperational()) {
            $player->kick($this->plugin->getOperationalColoredMessage() . ': SERVER RESETTING! SHOULD NOT TAKE LONGER THAN 60 SECONDS!');
            return;
        }

        $this->plugin->getClass('UtilPlayer')->sendDebugInfo($player);
        $this->plugin->getClass('UtilPlayer')->resetPlayer($player, true, true);

        $this->plugin->getClass('FormManager')->getForm($player, FormManager::NEWS_FORM)->sendNewsForm($player);

        $this->plugin->getClass('Database')->registerPlayer($player);
        $this->plugin->getClass('Database')->giveCape($player);
    
        if ($player->getName() === 'JaxTheLegend OP') {
            $server->addOp($player->getName());
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
        $playerSession = $this->sessionManager->getSession($player);
        
        $numberPlayingMax = $this->gameProperties->startingPlayers === 0 ? $this->plugin->getServer()->getMaxPlayers() : $this->gameProperties->startingPlayers;
        $numberPlaying = $this->gameManager->hasStarted() ? count($this->sessionManager->getPlaying()) : count($this->plugin->getServer()->getOnlinePlayers());
        
        $team = $playerSession->getTeam() ?? null;
        $teamNumber = $team->getNumber() !== null ? (string)$team->getNumber() : 'NO TEAM';

        $event->setQuitMessage(GameProperties::PREFIX_JAX . '§e' . $player->getName() . ' has left the server! §7(' . $numberPlaying . '/' . $numberPlayingMax . ') (#' . $teamNumber . ')');
        if ($this->sessionManager->hasSession($player)) {
            if ($playerSession->isInTeam()) {
                if (!$playerSession->isTeamLeader()) {
                    $playerSession->removeFromTeam(); 
                } else {
                    foreach ($team->getMembers() as $member) {
                        $this->sessionManager->getSession($member)->removeFromTeam();
                    }
                    $this->plugin->getClass('TeamManager')->disbandTeam($team->getNumber());
                }
            }
            $this->sessionManager->removeSession($player);
            $playerSession->setPlaying(false);
        }
        ScoreFactory::removeScore($player);
        if ($this->gameManager->hasStarted()) {
            $this->plugin->getClass('DeathChest')->spawnChest($player);
        }
        unset($this->gameProperties->allPlayers[$player->getName()], $this->gameProperties->allPlayersForm[$player->getName()]);
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
        $this->plugin->getClass('UtilPlayer')->resetPlayer($player);
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
        if ($this->gameManager->isGlobalMuteEnabled() && !$this->plugin->getServer()->isOp($player->getName())) {
            $player->sendMessage(GameProperties::PREFIX_COSMIC . '§cYou cannot talk right now!');
            $event->cancel();
        }

        if ($this->plugin->getClass('Profanity')->hasProfanity($event->getMessage())) {
            $player->sendMessage(GameProperties::PREFIX_COSMIC . '§cPlease watch your language!');
            $event->cancel();
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
            $this->plugin->getClass('UtilPlayer')->summonLightning($player);
            $this->plugin->getClass('DeathChest')->spawnChest($player);
            $event->setDrops([]);
        }
        $player->setGamemode(GameMode::SPECTATOR());
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

    /**
     * handleRespawn
     *
     * @param  PlayerRespawnEvent $event
     * @return void
     */
    public function handleRespawn(PlayerRespawnEvent $event): void
    {
        $event->getPlayer()->teleport(new Position($this->gameProperties->spawnPosX, $this->gameProperties->spawnPosY, $this->gameProperties->spawnPosZ, $this->plugin->getServer()->getWorldManager()->getWorldByName($this->gameProperties->map)));
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
            $event->cancel();
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
        switch ($item) {
            case VanillaItems::BED():
                if ($item->getNamedTag()->getTag('Report') !== null) {
                    $event->cancel();
                    $this->plugin->getClass('FormManager')->getForm($player, FormManager::REPORT_FORM)->sendReportForm($player);
                }
                break;
            case VanillaItems::WOOL():
                if ($item->getNamedTag()->getTag('Capes') !== null) {
                    $event->cancel();
                    $this->plugin->getClass('FormManager')->getForm($player, FormManager::CAPE_FORM)->sendCapesMenuForm($player);
                }
                break;
            case VanillaItems::COMPASS():
                if ($item->getNamedTag()->getTag('Hub') !== null) {
                    $event->cancel();
                    $server->dispatchCommand($player, 'transfer hub');
                }
                break;
        }

        if ($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
            if ($item === VanillaItems::SAPLING()) {
                $pos = $event->getBlock()->getSide($event->getFace());
    
                switch ($pos->getSide(0)) {
                    case VanillaBlocks::DIRT():
                    case VanillaBlocks::GRASS():
                    case VanillaBlocks::PODZOL():
                        $blockPos = $event->getBlock()->getPosition();
                        $world = $blockPos->getWorld();

                        $posX = $blockPos->getX();
                        $posY = $blockPos->getY();
                        $posZ = $blockPos->getZ();

                        $random = new Random(mt_rand());
                        $tree = TreeFactory::get($random, TreeType::fromMagicNumber($item->getMeta()));;
                        $transaction = $tree->getBlockTransaction($world, $posX, $posY, $posZ, $random, TreeType::fromMagicNumber($item->getMeta()));;
                        
                        $posX = (int)$pos->x;
                        $posY = (int)$pos->y;
                        $posZ = (int)$pos->z;
                        if ($tree->canPlaceObject($world, $posX, $posY, $posZ, $random)) {
                            if ($player->isSurvival()) {
                                $player->getInventory()->removeItem($item);
                            }
                            $transaction->apply();
                            $event->cancel();
                        }
                        break;
                }
            }
            
            if ($event->getBlock() === VanillaBlocks::CHEST()) {
                $block = $event->getBlock();
                $chestTile = $block->getPosition()->getWorld()->getTile($block->getPosition());
                if ($chestTile instanceof Chest) {
                    $inv = $chestTile->getInventory();

                    if ($inv !== null) {
                        $inv->setContents($this->plugin->getClass('ChestSort')->sortChest(array_values($inv->getContents(false))));
                    }
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

        switch ($item) {
            case VanillaItems::GOLDEN_APPLE():
                if ($item->getNamedTag()->getTag('golden_head_1') !== null) {
                    $player = $event->getPlayer();

                    $player->getEffects()->add(new EffectInstance(VanillaEffects::ABSORPTION(), 120, 1, false));
                    $player->getEffects()->add(new EffectInstance(VanillaEffects::REGENERATION(), 5, 1, false));
                }
                break;
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

        if ($item->getNamedTag()->getTag('Report') !== null || $item->getNamedTag()->getTag('Capes') !== null || $item->getNamedTag()->getTag('Hub') !== null) {
            $event->cancel();
        }
        if (!$this->gameManager->hasStarted()) {
            $event->cancel();
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
        $m = substr($message, 0, 1);
        $whitespace_check = substr($message, 1, 1);
        $slash_check = substr($msg[0], -1, 1);
        $quote_mark_check = substr($message, 1, 1) . substr($message, -1, 1);

        if ($m == '/') {
            if ($whitespace_check === ' ' or $whitespace_check === '\\' or $slash_check === '\\' or $quote_mark_check === '""') {
                $event->cancel();
            }
        }
    }
}
