<?php
declare(strict_types=1);

namespace AGTHARN\uhc\game;

use pocketmine\level\sound\BlazeShootSound;
use pocketmine\level\sound\ClickSound;
use pocketmine\level\Position;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Effect;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\item\Item;
use pocketmine\Player;

use AGTHARN\uhc\event\phase\PhaseChangeEvent;
use AGTHARN\uhc\session\SessionManager;
use AGTHARN\uhc\game\border\Border;
use AGTHARN\uhc\game\GameProperties;
use AGTHARN\uhc\game\GameManager;
use AGTHARN\uhc\Main;

use jackmd\scorefactory\ScoreFactory;

class GameHandler
{
    /** @var Main */
    private $plugin;

    /** @var GameManager */
    private $gameManager;
    /** @var SessionManager */
    private $sessionManager;
    /** @var GameProperties */
    private $gameProperties;
    /** @var UtilPlayer */
    private $utilPlayer;
    /** @var Border */
    private $border;

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
        $this->utilPlayer = $plugin->getClass('UtilPlayer');
        $this->border = $plugin->getClass('Border');
    }

    /**
     * handlePlayers
     *
     * @return void
     */
    public function handlePlayers(): void
    {
        $server = $this->plugin->getServer();
        foreach ($this->sessionManager->getSessions() as $session) {
            $player = $session->getPlayer();
            $playerLevel = $player->getLevel();
            $name = $session->getTeam() !== null ? ((string)$session->getTeam()->getNumber()) : 'NO TEAM';

            if ($player->isOnline()) {
                if ($player->isSurvival()) {
                    $session->setPlaying(true);
                } elseif ($player->isSpectator()) {
                    $this->utilPlayer->giveSpecItems($player);
                    $session->setPlaying(false);
                }
    
                if (!$player->hasEffect(16)) {
                    $player->addEffect(new EffectInstance(Effect::getEffect(16), 1000000, 1, false));
                }
                $this->handleScoreboard($player);
                $player->setNameTag('§7(#' . $name . ') §r' . $player->getDisplayName());

                $this->utilPlayer->checkAFK($player);
            }
        }
    }
        
    /**
     * handleWaiting
     *
     * @return void
     */
    public function handleWaiting(): void
    {
        $playersRequired = GameProperties::MIN_PLAYERS - count($this->sessionManager->getPlaying());
        
        $onlinePlayers = $this->plugin->getServer()->getOnlinePlayers();
        $loggedInPlayers = $this->plugin->getServer()->getLoggedInPlayers();

        $this->border->setSize(500);
        foreach ($this->sessionManager->getPlaying() as $session) {
            $player = $session->getPlayer();
            $inventory = $player->getInventory();
            
            $this->handleScoreboard($player);
            if ($playersRequired > 0) {
                $player->sendPopup('§c' . $playersRequired . ' more players required...');
            }
        }

        if ($playersRequired < 1) {
            if (sort($onlinePlayers) !== sort($loggedInPlayers) && $this->gameProperties->playersJoiningTime <= 20) {
                $player->sendPopup('§gWaiting for all players to join... §7(' . (string)($this->gameProperties->playersJoiningTime - 20) . ')');
                $this->gameProperties->playersJoiningTime++;
                return;
            }
            $this->gameManager->setPhase(PhaseChangeEvent::COUNTDOWN);
        }
    }
    
    /**
     * handleCountdown
     *
     * @return void
     */
    public function handleCountdown(): void
    {
        $server = $this->plugin->getServer();

        switch ($this->gameProperties->countdown) {
            case 60:
                $this->gameManager->setResetTimer(10);
                $this->gameManager->setGameTimer(0);

                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§rGame starting in §b60 seconds!');
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§rAll players will be teleported in §b30 seconds!');
                $this->utilPlayer->sendSound(1);
                break;
            case 45:
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§rAll players will be teleported in §b15 seconds!');
                $this->utilPlayer->sendSound(1);
                break;
            case 30:
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§rThe game will begin in §b30 seconds.');
                $this->utilPlayer->sendSound(1);
                foreach ($this->sessionManager->getPlaying() as $session) {
                    $player = $session->getPlayer();

                    $this->gameManager->randomizeCoordinates(-450, 450, 180, 200, -450, 450);
                    $this->utilPlayer->resetPlayer($player);
                    $player->setImmobile(true);
                }
                break;
            case 20:
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§rAll kits will be deployed in §b40 seconds.');
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§rElytras have been deployed.');
                $this->utilPlayer->sendSound(1);
                foreach ($this->sessionManager->getPlaying() as $session) {
                    $session->getPlayer()->getArmorInventory()->setChestplate(Item::get(Item::ELYTRA));
                }
                break;
            case 10:
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§rThe game will begin in §b10 seconds.');
                $this->utilPlayer->sendSound(1);
                break;
            case 5:
            case 4:
            case 3:
            case 2:
            case 1:
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§rThe game will begin in §b' . $this->gameProperties->countdown . ' second(s).');
                $this->utilPlayer->sendSound(1);
                break;
            case 0:
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§r§c§lThe match has begun!');
                $this->utilPlayer->sendSound(2);
                foreach ($this->sessionManager->getPlaying() as $session) {
                    $session->getPlayer()->setImmobile(false);
                }
                $this->gameProperties->startingPlayers = count($this->sessionManager->getPlaying());
                $this->gameProperties->startingTeams = count($this->plugin->getClass('TeamManager')->getTeams());
                $this->gameManager->setPhase(PhaseChangeEvent::GRACE);
                break;
        }
        $this->gameProperties->countdown--;
    }
    
    /**
     * handleGrace
     *
     * @return void
     */
    public function handleGrace(): void
    {
        $server = $this->plugin->getServer();

        switch ($this->gameManager->getGraceTimer()) {
            case 1190:
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§rFinal heal in §b10 minutes.');
                $this->utilPlayer->sendSound(1);
                break;
            case 1180:
                foreach ($this->sessionManager->getPlaying() as $session) {
                    $player = $session->getPlayer();
                    $kit = $this->plugin->getClass('KitManager')->giveKit($player);

                    $player->getArmorInventory()->clearAll();
                    $player->sendMessage(GameProperties::PREFIX_JAX . '§rAll kits have been deployed. You have gotten: §b' . $kit);

                    $this->utilPlayer->giveRoundStart($player);
                }
                $this->utilPlayer->sendSound(2);
                break;
            case 600:
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§rFinal Heal has §boccurred!');
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§r§cPVP will enable in §b10 minutes.');
                $this->utilPlayer->sendSound(1);
                foreach ($this->sessionManager->getPlaying() as $session) {
                    $player = $session->getPlayer();
                    $player->setHealth($player->getMaxHealth());
                }
                break;
            case 300:
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§r§cPVP will enable in §b5 minutes.');
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§rThe border will start shrinking to §b400 §fin §b10 minutes.');
                $this->utilPlayer->sendSound(1);
                break;
            case 60:
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§r§cPVP will enable in §b1 minute.');
                $this->utilPlayer->sendSound(1);
                break;
            case 30:
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§r§cPVP will enable in §b30 seconds.');
                $this->utilPlayer->sendSound(1);
                break;
            case 10:
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§r§cPVP will enable in §b10 seconds.');
                $this->utilPlayer->sendSound(1);
                break;
            case 5:
            case 4:
            case 3:
            case 2:
            case 1:
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§r§cPvP will be enabled in §b' . $this->gameProperties->grace . ' second(s).');
                $this->utilPlayer->sendSound(1);
                break;
            case 0:
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§r§cPvP has been enabled!');
                $this->utilPlayer->sendSound(2);
                $this->gameManager->setPhase(PhaseChangeEvent::PVP);
                break;
        }
        $this->gameProperties->grace--;
    }
    
    /**
     * handlePvP
     *
     * @return void
     */
    public function handlePvP(): void
    {
        $server = $this->plugin->getServer();

        $this->gameManager->setShrinking(true);
        switch ($this->gameManager->getPVPTimer()) {
            case 1199:
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§rThe border will start shrinking to §b400 §fin §b5 minutes');
                $this->utilPlayer->sendSound(1);
                break;
            case 900:
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§rThe border is now shrinking to §b400.');
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§rShrinking to §b300 §fin §b5 minutes.');
                $this->border->setReduction(100);
                $this->utilPlayer->sendSound(1);
                break;
            case 600:
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§rThe border is now shrinking to §b300.');
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§rShrinking to §b200 §fin §b5 minutes.');
                $this->border->setReduction(100);
                $this->utilPlayer->sendSound(1);
                break;
            case 300:
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§rThe border is now shrinking to §b200.');
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§rShrinking to §b100 §fin §b5 minutes.');
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§r§cDeathmatch starts in §b10 minutes.');
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§r§cAll players would be teleported before the Deathmatch starts.');
                $this->border->setReduction(100);
                $this->utilPlayer->sendSound(1);
                break;
            case 0:
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§rThe border is now shrinking to §b100.');
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§r§cDeathmatch starts in §b5 minutes.');
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§r§cAll players would be teleported before the Deathmatch starts.');
                $this->border->setReduction(100);
                $this->utilPlayer->sendSound(2);
                $this->gameManager->setPhase(PhaseChangeEvent::DEATHMATCH);
                break;
        }
        $this->gameProperties->pvp--;
    }
    
    /**
     * handleDeathmatch
     *
     * @return void
     */
    public function handleDeathmatch(): void
    {
        $server = $this->plugin->getServer();

        switch ($this->gameManager->getDeathmatchTimer()) {
            case 960:
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§r§cDeathmatch starts in §b1 minute.');
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§r§cAll players would be teleported in §b30 seconds.');
                $this->utilPlayer->sendSound(1);
                break;
            case 930:
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§r§cDeathmatch starts in §b30 seconds.');
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§r§cAll players have been teleported.');
                $this->utilPlayer->sendSound(1);
                foreach ($this->sessionManager->getPlaying() as $session) {
                    $this->gameManager->randomizeCoordinates(-99, 99, 180, 200, -99, 99);
                    $session->getPlayer()->setImmobile(true);
                }
                break;
            case 900:
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§rDeathmatch has started, §bGOOD LUCK!');
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§rBorder is shrinking to §b100 §fin §b5 minutes.');
                $this->utilPlayer->sendSound(1);
                foreach ($this->sessionManager->getPlaying() as $session) {
                    $session->getPlayer()->setImmobile(false);
                }
                break;
            case 700:
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§rThe border is now shrinking to §b50.');
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§rShrinking to §b75 §fin §b5 minutes.');
                $this->border->setReduction(50);
                $this->utilPlayer->sendSound(1);
                break;
            case 400:
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§rThe border is now shrinking to §b10.');
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§rShrinking to §b50 §fin §b5 minutes.');
                $this->border->setReduction(40);
                $this->utilPlayer->sendSound(1);
                break;
            case 300:
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§rThe border is now shrinking to §b1.');
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§r§cTHE GAME IS ENDING IN 5 MINS!!!');
                $this->border->setReduction(9);
                $this->utilPlayer->sendSound(1);
                break;
            case 0:
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§r§cGAME OVER!');
                $this->utilPlayer->sendSound(2);
                foreach ($this->sessionManager->getPlaying() as $session) {
                    $this->gameProperties->winnerNames[] = $session->getPlayer()->getName();
                }
                $this->gameManager->setPhase(PhaseChangeEvent::WINNER);
                break;
        }
        $this->gameProperties->deathmatch--;
    }
        
    /**
     * handleWinner
     *
     * @return void
     */
    public function handleWinner(): void
    {
        $server = $this->plugin->getServer();
        
        switch ($this->gameManager->getWinnerTimer()) {
            case 60:
                $this->utilPlayer->sendSound(1);
                foreach ($server->getOnlinePlayers() as $player) {
                    $session = $this->sessionManager->getSession($player);

                    $player->setImmobile(false);
                    $this->utilPlayer->resetPlayer($player, true);
                    $this->handleScoreboard($player);

                    $player->teleport(new Position($this->gameProperties->spawnPosX, $this->gameProperties->spawnPosY, $this->gameProperties->spawnPosZ, $this->plugin->getServer()->getLevelByName($this->gameProperties->map)));
                    $player->setGamemode(Player::SURVIVAL);

                    if ($session->isPlaying()) {
                        $server->broadcastMessage(GameProperties::PREFIX_JAX . '§r§aCongratulations to the winner(s)! ' . implode(', ', $this->gameProperties->winnerNames));
                    }
                }
                $this->gameManager->setShrinking(false);
                $this->border->setSize(500);
                break;
            case 45:
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§rServer would reset in §b40 seconds!');
                $this->utilPlayer->sendSound(1);
                break;
            case 30:
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§rServer would reset in §b25 seconds!');
                $this->utilPlayer->sendSound(1);
                break;
            case 10:
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§rServer would reset in §b5 seconds!');
                $this->utilPlayer->sendSound(1);
                break;
            case 7:
                $server->broadcastMessage(GameProperties::PREFIX_JAX . '§rThanks for playing on §bMineUHC!');
                $this->utilPlayer->sendSound(1);
                break;
            case 0:
                $this->gameManager->setPhase(PhaseChangeEvent::RESET);
                $this->plugin->setOperational(false); // still required cuz we wont try to handle join events at that time
                break;
        }
        $this->gameProperties->winner--;
    }
        
    /**
     * handleReset
     *
     * @return void
     */
    public function handleReset(): void
    {
        $server = $this->plugin->getServer();
        
        switch ($this->gameManager->getResetTimer()) {
            case 10: // entities
                $server->getLogger()->info('Starting Reset - Entities');
                $this->gameProperties->entitiesReset = true;

                foreach ($server->getLevels() as $level) {
                    foreach ($level->getEntities() as $entity) {
                        if (!$entity instanceof Player) {
                            $entity->close(); 
                        }
                    }
                }
                $server->getLogger()->info('Completed Reset - Entities');
                break;
            case 9: // worlds
                $server->getLogger()->info('Starting Reset - Worlds');
                $this->gameProperties->worldReset = true;

                $this->plugin->getClass('Generators')->prepareWorld();
                $this->plugin->getClass('Generators')->prepareNether();

                $server->getLogger()->info('Completed Reset - Worlds');
                break;
            case 4: // timers
                $server->getLogger()->info('Starting Reset - Timers');
                $this->gameProperties->timerReset = true;

                $this->gameManager->setGameTimer(0);
                $this->gameManager->setCountdownTimer(60);
                $this->gameManager->setGraceTimer(60 * 20);
                $this->gameManager->setPVPTimer(60 * 20);
                $this->gameManager->setDeathmatchTimer(60 * 20);
                $this->gameManager->setWinnerTimer(60);
            
                $server->getLogger()->info('Completed Reset - Timers');
                break;
            case 2: // teams
                $server->getLogger()->info('Starting Reset - Teams');
                $this->gameProperties->teamReset = true;

                $this->plugin->getClass('TeamManager')->resetTeams();

                foreach ($server->getOnlinePlayers() as $player) {
                    $session = $this->sessionManager->getSession($player);
                    $session->addToTeam($this->plugin->getClass('TeamManager')->createTeam($player));
                }

                $server->getLogger()->info('Completed Reset - Teams');
                break;
            case 1: // others (arrays)
                $server->getLogger()->info('Starting Reset - Others');
                $this->gameProperties->othersReset = true;

                //unset($this->plugin->getClass('FormManager')->playerArray);
                //unset($this->plugin->getClass('FormManager')->reportsArray);
                unset($this->gameProperties->winnerNames);

                $server->getLogger()->info('Completed Reset - Others');
            case 0: // complete
                $this->gameProperties->entitiesReset = false;
                $this->gameProperties->worldReset = false;
                $this->gameProperties->timerReset = false;
                $this->gameProperties->teamReset = false;
                $this->gameProperties->othersReset = false;

                $this->plugin->setOperational(true);
                $this->gameManager->setPhase(PhaseChangeEvent::WAITING);

                $server->getLogger()->info('Completed Reset Phase');
                break;
        }
        $this->gameProperties->reset--;
    }
        
    /**
     * handleScoreboard
     *
     * @param  Player $player
     * @return void
     */
    public function handleScoreboard(Player $player): void
    {
        $server = $this->plugin->getServer();

        $numberPlayingMax = $this->gameProperties->startingPlayers === 0 ? $this->plugin->getServer()->getMaxPlayers() : $this->gameProperties->startingPlayers;
        $numberPlaying = $this->gameManager->hasStarted() ? count($this->sessionManager->getPlaying()) : count($this->plugin->getServer()->getOnlinePlayers());

        $teamsMax = $this->gameProperties->startingTeams;

        $session = $this->sessionManager->getSession($player);

        ScoreFactory::setScore($player, '§7»» §f§eMineUHC UHC-' . $this->gameProperties->uhcServer . ' §7««');
        switch ($this->gameManager->getPhase()) {
            case PhaseChangeEvent::WAITING:
            case PhaseChangeEvent::COUNTDOWN:
                ScoreFactory::setScoreLine($player, 1, '§7§l[-------------------]');
                ScoreFactory::setScoreLine($player, 2, ' §fPlayers §f');
                ScoreFactory::setScoreLine($player, 3, ' §a' . $numberPlaying . '§f§7/' . $numberPlayingMax);
                ScoreFactory::setScoreLine($player, 4, ' ');
                ScoreFactory::setScoreLine($player, 5, $this->gameManager->getPhase() === PhaseChangeEvent::WAITING ? '§7 Waiting for more players...' : '§7 Starting in: §f' . $this->gameProperties->countdown);
                ScoreFactory::setScoreLine($player, 6, '  ');
                ScoreFactory::setScoreLine($player, 7, '§7§l[-------------------] ');
                ScoreFactory::setScoreLine($player, 8, ' §eplay.mineuhc.xyz');
                break;
            case PhaseChangeEvent::GRACE:
            case PhaseChangeEvent::PVP:
            case PhaseChangeEvent::DEATHMATCH:
                ScoreFactory::setScoreLine($player, 1, '§7§l[-------------------]');
                ScoreFactory::setScoreLine($player, 2, ' §fGame Time: §a' . gmdate('H:i:s', $this->gameProperties->game));
                ScoreFactory::setScoreLine($player, 3, ' §fPlayers: §a' . $numberPlaying . '§f§7/' . $numberPlayingMax);
                ScoreFactory::setScoreLine($player, 4, ' ');
                ScoreFactory::setScoreLine($player, 5, ' §fTeams: §a' . count($this->plugin->getClass('TeamManager')->getTeams()) . '§f§7/' . $teamsMax);
                ScoreFactory::setScoreLine($player, 6, ' §fTeam Number: §a' . $session->getTeam()->getNumber() ?? 'NO TEAM');
                //ScoreFactory::setScoreLine($player, 7, ' §fTeam Members: §a' . implode(', ', $session->getTeam()->getOtherMemberNames($player)));
                ScoreFactory::setScoreLine($player, 7, '  ');
                ScoreFactory::setScoreLine($player, 8, $this->sessionManager->hasSession($player) !== true ? ' §fKills: §a0' : ' §fKills: §a' . $session->getEliminations());
                ScoreFactory::setScoreLine($player, 9, ' §fTPS/Ping: §a' . $server->getTicksPerSecond() . '§f§7/' . $player->getPing());
                ScoreFactory::setScoreLine($player, 10, '   ');
                ScoreFactory::setScoreLine($player, 11, ' §fBorder: §a± ' . $this->border->getSize());
                //ScoreFactory::setScoreLine($player, 10, ' §fCenter: §a0, 0');
                ScoreFactory::setScoreLine($player, 12, '    ');
                ScoreFactory::setScoreLine($player, 13, '§7§l[-------------------] ');
                ScoreFactory::setScoreLine($player, 14, ' §eplay.mineuhc.xyz');
                break;
            case PhaseChangeEvent::WINNER:
                ScoreFactory::setScoreLine($player, 1, '§7§l[-------------------]');
                ScoreFactory::setScoreLine($player, 2, ' §fPlayers §f');
                ScoreFactory::setScoreLine($player, 3, ' §a' . $numberPlaying . '§f§7/' . $numberPlayingMax);
                ScoreFactory::setScoreLine($player, 4, ' ');
                ScoreFactory::setScoreLine($player, 5, ' §fWinners:');
                ScoreFactory::setScoreLine($player, 6, ' §a' . implode(",\n ", $this->gameProperties->winnerNames));
                ScoreFactory::setScoreLine($player, 7, '  ');
                ScoreFactory::setScoreLine($player, 8, '§7§l[-------------------] ');
                ScoreFactory::setScoreLine($player, 9, ' §eplay.mineuhc.xyz');
                break;
            case PhaseChangeEvent::RESET:
                ScoreFactory::setScoreLine($player, 1, '§7§l[-------------------]');
                ScoreFactory::setScoreLine($player, 2, ' §fPlayers §f');
                ScoreFactory::setScoreLine($player, 3, ' §a' . $numberPlaying . '§f§7/' . $numberPlayingMax);
                ScoreFactory::setScoreLine($player, 4, ' ');
                ScoreFactory::setScoreLine($player, 5, ' §fCurrently ongoing a reset:');
                ScoreFactory::setScoreLine($player, 6, ' §aEntities Reset: §f[§r' . ($this->gameProperties->entitiesReset === true ? '✔' : '⛌') . '§f]');
                ScoreFactory::setScoreLine($player, 7, ' §aWorlds Reset: §f[§r' . ($this->gameProperties->worldReset === true ? '✔' : '⛌') . '§f]');
                ScoreFactory::setScoreLine($player, 8, ' §aTimers Reset: §f[§r' . ($this->gameProperties->timerReset === true ? '✔' : '⛌') . '§f]');
                ScoreFactory::setScoreLine($player, 9, ' §aTeams Reset: §f[§r' . ($this->gameProperties->teamReset === true ? '✔' : '⛌') . '§f]');
                ScoreFactory::setScoreLine($player, 10, ' §aOthers Reset: §f[§r' . ($this->gameProperties->othersReset === true ? '✔' : '⛌') . '§f]');
                ScoreFactory::setScoreLine($player, 11, '  ');
                ScoreFactory::setScoreLine($player, 12, '§7§l[-------------------] ');
                ScoreFactory::setScoreLine($player, 13, ' §eplay.mineuhc.xyz');
                break;
        }
        ScoreFactory::send($player);
    }
    
    /**
     * handleBossBar
     *
     * @return void
     */
    public function handleBossBar(): void
    {   
        $server = $this->plugin->getServer();
        $sessions = $this->sessionManager->getSessions();

        $bossBarAPI = $this->plugin->getClass('BossBarAPI');

        switch ($this->gameManager->getPhase()) {
            case PhaseChangeEvent::GRACE:
                $changedTimeGrace = $this->gameManager->getGraceTimer() - 601;
                $changedTimePVP = $this->gameManager->getGraceTimer();

                if ($changedTimeGrace >= 0) {
                    $bossBarAPI->sendBossBarSessions($sessions, '§fPVP Enables In: §a' . gmdate('i:s', $changedTimePVP) . "\n\n" . '§fFinal Heal In: §a' . gmdate('i:s', $changedTimeGrace), floatval($changedTimeGrace / 599));
                } else {
                    if ($this->gameManager->getGraceTimer() >= 300) {
                        $bossBarAPI->sendBossBarSessions($sessions, '§fPVP Enables In: §a' . gmdate('i:s', $changedTimePVP), floatval($changedTimePVP / 599));
                    } else {
                        $bossBarAPI->sendBossBarSessions($sessions, '§fPVP Enables In: §c' . gmdate('i:s', $changedTimePVP), floatval($changedTimePVP / 599));
                    }
                }
                break;
            case PhaseChangeEvent::PVP:
                if ($this->border->getSize() >= 499) {
                    $changedTime = $this->gameManager->getPVPTimer() - 900;
                    if ($this->gameManager->getPVPTimer() - 900 >= 61) {
                        $bossBarAPI->sendBossBarSessions($sessions, '§fBorder Shrinks(400): §a' . gmdate('i:s', $changedTime), floatval($changedTime / 300));
                    } elseif ($this->gameManager->getPVPTimer() - 900 <= 60) {
                        $bossBarAPI->sendBossBarSessions($sessions, '§fBorder Shrinks(400): §c' . gmdate('i:s', $changedTime), floatval($changedTime / 300));
                    }
                } elseif ($this->border->getSize() >= 399) {
                    $changedTime = $this->gameManager->getPVPTimer() - 600;
                    if ($this->gameManager->getPVPTimer() - 600 >= 61) {
                        $bossBarAPI->sendBossBarSessions($sessions, '§fBorder Shrinks(300): §a' . gmdate('i:s', $changedTime), floatval($changedTime / 300));
                    } elseif ($this->gameManager->getPVPTimer() - 600 <= 60) {
                        $bossBarAPI->sendBossBarSessions($sessions, '§fBorder Shrinks(300): §c' . gmdate('i:s', $changedTime), floatval($changedTime / 300));
                    }
                } elseif ($this->border->getSize() >= 299) {
                    $changedTime = $this->gameManager->getPVPTimer() - 300;
                    if ($this->gameManager->getPVPTimer() - 300 >= 61) {
                        $bossBarAPI->sendBossBarSessions($sessions, '§fBorder Shrinks(200): §a' . gmdate('i:s', $changedTime), floatval($changedTime / 300));
                    } elseif ($this->gameManager->getPVPTimer() - 300 <= 60) {
                        $bossBarAPI->sendBossBarSessions($sessions, '§fBorder Shrinks(200): §c' . gmdate('i:s', $changedTime), floatval($changedTime / 300));
                    }
                } elseif ($this->border->getSize() >= 199) {
                    $changedTime = $this->gameManager->getPVPTimer() - 0;
                    if ($this->gameManager->getPVPTimer() - 0 >= 61) { // reason why i leave it as - 0 is to note myself
                        $bossBarAPI->sendBossBarSessions($sessions, '§fBorder Shrinks(100): §a' . gmdate('i:s', $changedTime), floatval($changedTime / 300));
                    } elseif ($this->gameManager->getPVPTimer() - 0 <= 60) {
                        $bossBarAPI->sendBossBarSessions($sessions, '§fBorder Shrinks(100): §c' . gmdate('i:s', $changedTime), floatval($changedTime / 300));
                    }
                }
                break;
            case PhaseChangeEvent::DEATHMATCH:
                if ($this->gameManager->getDeathmatchTimer() >= 900) { // needs testing
                    $changedTime = $this->gameManager->getDeathmatchTimer() - 900;
                    $bossBarAPI->sendBossBarSessions($sessions, '§fDeathmatch In: §c' . gmdate('i:s', $changedTime), floatval($changedTime / 300));
                } elseif ($this->border->getSize() >= 99) {
                    $changedTime = $this->gameManager->getDeathmatchTimer() - 700;
                    if ($this->gameManager->getDeathmatchTimer() - 700 >= 61) {
                        $bossBarAPI->sendBossBarSessions($sessions, '§fBorder Shrinks(50): §a' . gmdate('i:s', $changedTime), floatval($changedTime / 300));
                    } elseif ($this->gameManager->getDeathmatchTimer() - 700 <= 60) {
                        $bossBarAPI->sendBossBarSessions($sessions, '§fBorder Shrinks(50): §c' . gmdate('i:s', $changedTime), floatval($changedTime / 300));
                    }
                } elseif ($this->border->getSize() >= 49) {
                    $changedTime = $this->gameManager->getDeathmatchTimer() - 400;
                    if ($this->gameManager->getDeathmatchTimer() - 400 >= 61) {
                        $bossBarAPI->sendBossBarSessions($sessions, '§fBorder Shrinks(10): §a' . gmdate('i:s', $changedTime), floatval($changedTime / 300));
                    } elseif ($this->gameManager->getDeathmatchTimer() - 400 <= 60) {
                        $bossBarAPI->sendBossBarSessions($sessions, '§fBorder Shrinks(10): §c' . gmdate('i:s', $changedTime), floatval($changedTime / 300));
                    }
                } elseif ($this->border->getSize() >= 9) {
                    $changedTime = $this->gameManager->getDeathmatchTimer() - 300;
                     if ($this->gameManager->getDeathmatchTimer() - 300 >= 61) {
                        $bossBarAPI->sendBossBarSessions($sessions, '§fBorder Shrinks(1): §a' . gmdate('i:s', $changedTime), floatval($changedTime / 100));
                    } elseif ($this->gameManager->getDeathmatchTimer() - 300 <= 60) {
                        $bossBarAPI->sendBossBarSessions($sessions, '§fBorder Shrinks(1): §c' . gmdate('i:s', $changedTime), floatval($changedTime / 100));
                    }
                }
                break;
        }
    }
    
    /**
     * handleBorder
     *
     * @return void
     */
    public function handleBorder(): void
    {   
        $server = $this->plugin->getServer();
        $borderSize = $this->border->getSize();

        if ($this->gameManager->getShrinking() === true) {
            if ($this->border->reductionSize !== 0) {
                $this->border->setSize($this->border->getSize() - 1);
                $this->border->reductionSize--;
            }
        }

        foreach ($this->sessionManager->getSessions() as $session) {
            $player = $session->getPlayer();
            
            $minX = -$borderSize;
			$maxX = $borderSize;
			$minZ = -$borderSize;
			$maxZ = $borderSize;

            $playerx = $player->getFloorX();
            $playery = $player->getFloorY();
            $playerz = $player->getFloorZ();
			
			$aabb = new AxisAlignedBB($minX, 0, $minZ, $maxX, $player->getLevel()->getWorldHeight(), $maxZ);
			if (!$aabb->isVectorInXZ($player->getPosition())) {
				switch ($this->gameManager->getPhase()) {
                    case PhaseChangeEvent::WAITING:
                    case PhaseChangeEvent::COUNTDOWN:
                        $level = $server->getLevelByName($this->gameProperties->map);
                        $player->teleport(new Position($this->gameProperties->spawnPosX, $this->gameProperties->spawnPosY, $this->gameProperties->spawnPosZ, $level));
                        break;
                    default:
                        $player->addEffect(new EffectInstance(Effect::getEffect(19), 60, 1, false));
                        if ($player->getHealth() <= 2) {
                            $player->addEffect(new EffectInstance(Effect::getEffect(7), 100, 1, false));
                        }
                        break;
                }
			}

            $aabb2 = new AxisAlignedBB($minX + 20, 0, $minZ + 20, $maxX - 20, $player->getLevel()->getWorldHeight(), $maxZ - 20);
            if (!$aabb2->isVectorInXZ($player->getPosition())) {
                $player->sendPopup('§cBORDER IS CLOSE!');
            }
        }
    }
}
