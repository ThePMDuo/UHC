<?php
declare(strict_types=1);

namespace AGTHARN\uhc\util;

use pocketmine\level\sound\BlazeShootSound;
use pocketmine\level\sound\ClickSound;
use pocketmine\level\Position;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Effect;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\item\Item;
use pocketmine\Player;

use AGTHARN\uhc\game\border\Border;
use AGTHARN\uhc\event\PhaseChangeEvent;
use AGTHARN\uhc\Main;

use AGTHARN\uhc\libs\JackMD\ScoreFactory\ScoreFactory;

class Handler
{
    /** @var int */
    public const MIN_PLAYERS = 2;
    
    /** @var Border */
    private $border;
    /** @var Main */
    private $plugin;
    /** @var mixed */
    public $bossBar;

    /** @var array */
    private $winnerNames = [];

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
     * handlePlayers
     *
     * @return void
     */
    public function handlePlayers(): void
    {
        $server = $this->plugin->getServer();
        foreach ($this->plugin->getSessionManager()->getSessions() as $session) {
            $player = $session->getPlayer();
            $name = (string)$session->getTeam()->getNumber() ?? 'NO TEAM';
            $gameManager = $this->plugin->getManager();

            if ($player->isSurvival()) {
                $session->setPlaying(true);
            } else {
                $this->plugin->getUtilItems()->giveItems($player);
                $session->setPlaying(false);
            }

            if (!$player->hasEffect(16)) {
                $player->addEffect(new EffectInstance(Effect::getEffect(16), 1000000, 1, false));
            }
            if ($player->getLevel()->getName() !== $this->plugin->map) {
                $level = $server->getLevelByName($this->plugin->map);
                $player->teleport(new Position($this->plugin->spawnPosX, $this->plugin->spawnPosY, $this->plugin->spawnPosZ, $level));
            }
            $this->handleScoreboard($player);
            $player->setNameTag('§7(#' . $name . ') §r' . $player->getDisplayName());
        }
    }
        
    /**
     * handleWaiting
     *
     * @return void
     */
    public function handleWaiting(): void
    {
        $gameManager = $this->plugin->getManager();
        $sessionManager = $this->plugin->getSessionManager();
        $playersRequired = self::MIN_PLAYERS - count($sessionManager->getPlaying());

        $this->border->setSize(500);
        if (count($sessionManager->getPlaying()) >= self::MIN_PLAYERS) {
            $gameManager->setPhase(PhaseChangeEvent::COUNTDOWN);
        }

        foreach ($sessionManager->getPlaying() as $session) {
            $player = $session->getPlayer();
            $inventory = $player->getInventory();
            
            $this->handleScoreboard($player);
            if (count($sessionManager->getPlaying()) < self::MIN_PLAYERS) {
                $player->sendPopup('§c' . $playersRequired . ' more players required...');
            }
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
        $gameManager = $this->plugin->getManager();
        $sessionManager = $this->plugin->getSessionManager();

        switch ($gameManager->countdown) {
            case 60:
                $gameManager->setResetTimer(60);
                $gameManager->setGameTimer(0);

                $server->broadcastMessage('§aJAX §7»» §rGame starting in §b60 seconds!');
                $server->broadcastMessage('§aJAX §7»» §rAll players will be teleported in §b30 seconds!');
                $this->sendSound(1);
                break;
            case 45:
                $server->broadcastMessage('§aJAX §7»» §rAll players will be teleported in §b15 seconds!');
                $this->sendSound(1);
                break;
            case 30:
                $server->broadcastMessage('§aJAX §7»» §rThe game will begin in §b30 seconds.');
                $this->sendSound(1);
                foreach ($sessionManager->getPlaying() as $session) {
                    $player = $session->getPlayer();

                    $gameManager->randomizeCoordinates(-450, 450, 180, 200, -450, 450);
                    $this->plugin->getUtilPlayer()->resetPlayer($player);
                    $player->setImmobile(true);
                }
                break;
            case 20:
                $server->broadcastMessage('§aJAX §7»» §rAll kits will be deployed in §b40 seconds.');
                $server->broadcastMessage('§aJAX §7»» §rElytras have been deployed.');
                $this->sendSound(1);
                foreach ($sessionManager->getPlaying() as $session) {
                    $session->getPlayer()->getArmorInventory()->setChestplate(Item::get(Item::ELYTRA));
                }
                break;
            case 10:
                $server->broadcastMessage('§aJAX §7»» §rThe game will begin in §b10 seconds.');
                $this->sendSound(1);
                break;
            case 5:
            case 4:
            case 3:
            case 2:
            case 1:
                $server->broadcastMessage('§aJAX §7»» §rThe game will begin in §b' . $gameManager->countdown . ' second(s).');
                $this->sendSound(1);
                break;
            case 0:
                $server->broadcastMessage('§aJAX §7»» §r§c§lThe match has begun!');
                $this->sendSound(2);
                foreach ($sessionManager->getPlaying() as $session) {
                    $session->getPlayer()->setImmobile(false);
                }
                $this->plugin->startingPlayers = count($this->plugin->getSessionManager()->getPlaying());
                $this->plugin->startingTeams = count($this->plugin->getTeamManager()->getTeams());
                $gameManager->setPhase(PhaseChangeEvent::GRACE);
                break;
        }
        $gameManager->countdown--;
    }
    
    /**
     * handleGrace
     *
     * @return void
     */
    public function handleGrace(): void
    {
        $server = $this->plugin->getServer();
        $gameManager = $this->plugin->getManager();
        $sessionManager = $this->plugin->getSessionManager();

        switch ($gameManager->getGraceTimer()) {
            case 1190:
                $server->broadcastMessage('§aJAX §7»» §rFinal heal in §b10 minutes.');
                $this->sendSound(1);
                break;
            case 1180:
                foreach ($sessionManager->getPlaying() as $session) {
                    $player = $session->getPlayer();
                    $kit = $this->plugin->getKits()->giveKit($player);

                    $player->getArmorInventory()->clearAll();
                    $player->sendMessage('§aJAX §7»» §rAll kits have been deployed. You have gotten: §b' . $kit);
                }
                $this->sendSound(2);
                break;
            case 600:
                $server->broadcastMessage('§aJAX §7»» §rFinal Heal has §boccurred!');
                $server->broadcastMessage('§aJAX §7»» §r§cPVP will enable in §b10 minutes.');
                $this->sendSound(1);
                foreach ($sessionManager->getPlaying() as $session) {
                    $player = $session->getPlayer();
                    $player->setHealth($player->getMaxHealth());
                }
                break;
            case 300:
                $server->broadcastMessage('§aJAX §7»» §r§cPVP will enable in §b5 minutes.');
                $server->broadcastMessage('§aJAX §7»» §rThe border will start shrinking to §b400 §fin §b10 minutes.');
                $this->sendSound(1);
                break;
            case 60:
                $server->broadcastMessage('§aJAX §7»» §r§cPVP will enable in §b1 minute.');
                $this->sendSound(1);
                break;
            case 30:
                $server->broadcastMessage('§aJAX §7»» §r§cPVP will enable in §b30 seconds.');
                $this->sendSound(1);
                break;
            case 10:
                $server->broadcastMessage('§aJAX §7»» §r§cPVP will enable in §b10 seconds.');
                $this->sendSound(1);
                break;
            case 5:
            case 4:
            case 3:
            case 2:
            case 1:
                $server->broadcastMessage('§aJAX §7»» §r§cPvP will be enabled in §b' . $gameManager->grace . ' second(s).');
                $this->sendSound(1);
                break;
            case 0:
                $server->broadcastMessage('§aJAX §7»» §r§cPvP has been enabled!');
                $this->sendSound(2);
                $gameManager->setPhase(PhaseChangeEvent::PVP);
                break;
        }
        $gameManager->grace--;
    }
    
    /**
     * handlePvP
     *
     * @return void
     */
    public function handlePvP(): void
    {
        $server = $this->plugin->getServer();
        $gameManager = $this->plugin->getManager();

        $gameManager->setShrinking(true);
        switch ($gameManager->getPVPTimer()) {
            case 1199:
                $server->broadcastMessage('§aJAX §7»» §rThe border will start shrinking to §b400 §fin §b5 minutes');
                $this->sendSound(1);
                break;
            case 900:
                $server->broadcastMessage('§aJAX §7»» §rThe border is now shrinking to §b400.');
                $server->broadcastMessage('§aJAX §7»» §rShrinking to §b300 §fin §b5 minutes.');
                $this->border->setReduction(100);
                $this->sendSound(1);
                break;
            case 600:
                $server->broadcastMessage('§aJAX §7»» §rThe border is now shrinking to §b300.');
                $server->broadcastMessage('§aJAX §7»» §rShrinking to §b200 §fin §b5 minutes.');
                $this->border->setReduction(100);
                $this->sendSound(1);
                break;
            case 300:
                $server->broadcastMessage('§aJAX §7»» §rThe border is now shrinking to §b200.');
                $server->broadcastMessage('§aJAX §7»» §rShrinking to §b100 §fin §b5 minutes.');
                $server->broadcastMessage('§aJAX §7»» §r§cDeathmatch starts in §b10 minutes.');
                $server->broadcastMessage('§aJAX §7»» §r§cAll players would be teleported before the Deathmatch starts.');
                $this->border->setReduction(100);
                $this->sendSound(1);
                break;
            case 0:
                $server->broadcastMessage('§aJAX §7»» §rThe border is now shrinking to §b100.');
                $server->broadcastMessage('§aJAX §7»» §r§cDeathmatch starts in §b5 minutes.');
                $server->broadcastMessage('§aJAX §7»» §r§cAll players would be teleported before the Deathmatch starts.');
                $this->border->setReduction(100);
                $this->sendSound(2);
                $gameManager->setPhase(PhaseChangeEvent::DEATHMATCH);
                break;
        }
        $gameManager->pvp--;
    }
    
    /**
     * handleDeathmatch
     *
     * @return void
     */
    public function handleDeathmatch(): void
    {
        $server = $this->plugin->getServer();
        $gameManager = $this->plugin->getManager();
        $sessionManager = $this->plugin->getSessionManager();

        switch ($gameManager->getDeathmatchTimer()) {
            case 960:
                $server->broadcastMessage('§aJAX §7»» §r§cDeathmatch starts in §b1 minute.');
                $server->broadcastMessage('§aJAX §7»» §r§cAll players would be teleported in §b30 seconds.');
                $this->sendSound(1);
                break;
            case 930:
                $server->broadcastMessage('§aJAX §7»» §r§cDeathmatch starts in §b30 seconds.');
                $server->broadcastMessage('§aJAX §7»» §r§cAll players have been teleported.');
                $this->sendSound(1);
                foreach ($sessionManager->getPlaying() as $session) {
                    $gameManager->randomizeCoordinates(-99, 99, 180, 200, -99, 99);
                    $session->getPlayer()->setImmobile(true);
                }
                break;
            case 900:
                $server->broadcastMessage('§aJAX §7»» §rDeathmatch has started, §bGOOD LUCK!');
                $server->broadcastMessage('§aJAX §7»» §rBorder is shrinking to §b100 §fin §b5 minutes.');
                $this->sendSound(1);
                foreach ($sessionManager->getPlaying() as $session) {
                    $session->getPlayer()->setImmobile(false);
                }
                break;
            case 700:
                $server->broadcastMessage('§aJAX §7»» §rThe border is now shrinking to §b50.');
                $server->broadcastMessage('§aJAX §7»» §rShrinking to §b75 §fin §b5 minutes.');
                $this->border->setReduction(50);
                $this->sendSound(1);
                break;
            case 400:
                $server->broadcastMessage('§aJAX §7»» §rThe border is now shrinking to §b10.');
                $server->broadcastMessage('§aJAX §7»» §rShrinking to §b50 §fin §b5 minutes.');
                $this->border->setReduction(40);
                $this->sendSound(1);
                break;
            case 300:
                $server->broadcastMessage('§aJAX §7»» §rThe border is now shrinking to §b1.');
                $server->broadcastMessage('§aJAX §7»» §r§cTHE GAME IS ENDING IN 5 MINS!!!');
                $this->border->setReduction(9);
                $this->sendSound(1);
                break;
            case 0:
                $server->broadcastMessage('§aJAX §7»» §r§cGAME OVER!');
                $this->sendSound(2);
                foreach ($sessionManager->getPlaying() as $session) {
                    $this->winnerNames[] = $session->getPlayer()->getName();
                }
                $gameManager->setPhase(PhaseChangeEvent::WINNER);
                break;
        }
        $gameManager->deathmatch--;
    }
        
    /**
     * handleWinner
     *
     * @return void
     */
    public function handleWinner(): void
    {
        $server = $this->plugin->getServer();
        $gameManager = $this->plugin->getManager();
        $sessionManager = $this->plugin->getSessionManager();
        
        switch ($gameManager->getWinnerTimer()) {
            case 60:
                $this->sendSound(1);
                foreach ($server->getOnlinePlayers() as $player) {
                    $session = $this->plugin->getSessionManager()->getSession($player);

                    $player->setImmobile(false);
                    $this->plugin->getUtilPlayer()->resetPlayer($player, true);
                    $this->handleScoreboard($player);

                    $player->teleport(new Position($this->plugin->spawnPosX, $this->plugin->spawnPosY, $this->plugin->spawnPosZ, $this->plugin->getServer()->getLevelByName($this->plugin->map)));
                    $player->setGamemode(Player::SURVIVAL);

                    if ($session->isPlaying()) {
                        $server->broadcastMessage('§aJAX §7»» §r§aCongratulations to the winner(s)! ' . implode(', ', $this->winnerNames));
                    }
                }
                $gameManager->setShrinking(false);
                $this->border->setSize(500);
                break;
            case 45:
                $server->broadcastMessage('§aJAX §7»» §rServer would reset in §b40 seconds!');
                $this->sendSound(1);
                break;
            case 30:
                $server->broadcastMessage('§aJAX §7»» §rServer would reset in §b25 seconds!');
                $this->sendSound(1);
                break;
            case 10:
                $server->broadcastMessage('§aJAX §7»» §rServer would reset in §b5 seconds!');
                $this->sendSound(1);
                break;
            case 7:
                $server->broadcastMessage('§aJAX §7»» §rThanks for playing on §bMineUHC!');
                $this->sendSound(1);
                break;
            case 0:
                $gameManager->setPhase(PhaseChangeEvent::RESET);
                $this->plugin->setOperational(false); // still required cuz we wont try to handle join events at that time
                break;
        }
        $gameManager->winner--;
    }
        
    /**
     * handleReset
     *
     * @return void
     */
    public function handleReset(): void
    {
        $server = $this->plugin->getServer();
        $gameManager = $this->plugin->getManager();
        $resetStatus = $this->plugin->getResetStatus();
        
        switch ($gameManager->getResetTimer()) {
            case 10: // entities
                $server->getLogger()->info('Starting Reset - Entities');
                $resetStatus->entitiesReset = true;

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
                $resetStatus->worldReset = true;

                $this->plugin->getGenerators()->prepareWorld();
                //$this->plugin->getGenerators()->prepareNether();

                $server->getLogger()->info('Completed Reset - Worlds');
                break;
            case 4: // timers
                $server->getLogger()->info('Starting Reset - Timers');
                $resetStatus->timerReset = true;

                $gameManager->setGameTimer(0);
                $gameManager->setCountdownTimer(60);
                $gameManager->setGraceTimer(60 * 20);
                $gameManager->setPVPTimer(60 * 20);
                $gameManager->setDeathmatchTimer(60 * 20);
                $gameManager->setWinnerTimer(60);
            
                $server->getLogger()->info('Completed Reset - Timers');
                break;
            case 2: // teams
                $server->getLogger()->info('Starting Reset - Teams');
                $resetStatus->teamReset = true;

                $this->plugin->getTeamManager()->resetTeams();

                foreach ($server->getOnlinePlayers() as $player) {
                    $session = $this->plugin->getSessionManager()->getSession($player);
                    $session->addToTeam($this->plugin->getTeamManager()->createTeam($player));
                }

                $server->getLogger()->info('Completed Reset - Teams');
                break;
            case 1: // others (arrays)
                $server->getLogger()->info('Starting Reset - Others');
                $resetStatus->othersReset = true;

                unset($this->plugin->entityRegainNote);
                unset($this->plugin->getForms()->playerArray);
                unset($this->plugin->getForms()->reportsArray);
                unset($this->winnerNames);

                $server->getLogger()->info('Completed Reset - Others');
            case 0: // complete
                $resetStatus->entitiesReset = false;
                $resetStatus->worldReset = false;
                $resetStatus->timerReset = false;
                $resetStatus->teamReset = false;
                $resetStatus->othersReset = false;

                $this->plugin->setOperational(true);
                $gameManager->setPhase(PhaseChangeEvent::WAITING);

                $server->getLogger()->info('Completed Reset Phase');
                break;
        }
        $gameManager->reset--;
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
        $gameManager = $this->plugin->getManager();
        $sessionManager = $this->plugin->getSessionManager();
        $resetStatus = $this->plugin->getResetStatus();

        $numberPlayingMax = $this->plugin->startingPlayers === 0 ? $this->plugin->getServer()->getMaxPlayers() : $this->plugin->startingPlayers;
        $numberPlaying = $this->plugin->getManager()->hasStarted() ? count($sessionManager->getPlaying()) : count($this->plugin->getServer()->getOnlinePlayers());

        $teamsMax = $this->plugin->startingTeams;

        $session = $sessionManager->getSession($player);

        ScoreFactory::setScore($player, '§7»» §f§eMineUHC UHC-' . $this->plugin->uhcServer . ' §7««');
        switch ($gameManager->getPhase()) {
            case PhaseChangeEvent::WAITING:
            case PhaseChangeEvent::COUNTDOWN:
                ScoreFactory::setScoreLine($player, 1, '§7§l[-------------------]');
                ScoreFactory::setScoreLine($player, 2, ' §fPlayers §f');
                ScoreFactory::setScoreLine($player, 3, ' §a' . $numberPlaying . '§f§7/' . $numberPlayingMax);
                ScoreFactory::setScoreLine($player, 4, ' ');
                ScoreFactory::setScoreLine($player, 5, $gameManager->getPhase() === PhaseChangeEvent::WAITING ? '§7 Waiting for more players...' : '§7 Starting in: §f' . $gameManager->countdown);
                ScoreFactory::setScoreLine($player, 6, '  ');
                ScoreFactory::setScoreLine($player, 7, '§7§l[-------------------] ');
                ScoreFactory::setScoreLine($player, 8, ' §eplay.mineuhc.xyz');
                break;
            case PhaseChangeEvent::GRACE:
            case PhaseChangeEvent::PVP:
            case PhaseChangeEvent::DEATHMATCH:
                $teamMembers = $session->getTeam()->getMembers();

                ScoreFactory::setScoreLine($player, 1, '§7§l[-------------------]');
                ScoreFactory::setScoreLine($player, 2, ' §fGame Time: §a' . gmdate('H:i:s', $gameManager->game));
                ScoreFactory::setScoreLine($player, 3, ' §fPlayers: §a' . $numberPlaying . '§f§7/' . $numberPlayingMax);
                ScoreFactory::setScoreLine($player, 4, ' ');
                ScoreFactory::setScoreLine($player, 5, ' §fTeams: §a' . count($this->plugin->getTeamManager()->getTeams()) . '§f§7/' . $teamsMax);
                ScoreFactory::setScoreLine($player, 6, ' §fTeam Number: §a' . $session->getTeam()->getNumber() ?? 'NO TEAM');
                ScoreFactory::setScoreLine($player, 7, ' §fTeam Members: §a' . implode(", ", $teamMembers));
                ScoreFactory::setScoreLine($player, 8, '  ');
                ScoreFactory::setScoreLine($player, 9, $sessionManager->hasSession($player) !== true ? ' §fKills: §a0' : ' §fKills: §a' . $session->getEliminations());
                ScoreFactory::setScoreLine($player, 10, ' §fTPS: §a' . $server->getTicksPerSecond());
                ScoreFactory::setScoreLine($player, 11, '   ');
                ScoreFactory::setScoreLine($player, 12, ' §fBorder: §a± ' . $this->border->getSize());
                //ScoreFactory::setScoreLine($player, 10, ' §fCenter: §a0, 0');
                ScoreFactory::setScoreLine($player, 13, '    ');
                ScoreFactory::setScoreLine($player, 14, '§7§l[-------------------] ');
                ScoreFactory::setScoreLine($player, 15, ' §eplay.mineuhc.xyz');
                break;
            case PhaseChangeEvent::WINNER:
                ScoreFactory::setScoreLine($player, 1, '§7§l[-------------------]');
                ScoreFactory::setScoreLine($player, 2, ' §fPlayers §f');
                ScoreFactory::setScoreLine($player, 3, ' §a' . $numberPlaying . '§f§7/' . $numberPlayingMax);
                ScoreFactory::setScoreLine($player, 4, ' ');
                ScoreFactory::setScoreLine($player, 5, ' §fWinners:');
                ScoreFactory::setScoreLine($player, 6, ' §a' . implode(', ', $this->winnerNames));
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
                ScoreFactory::setScoreLine($player, 6, ' §aEntities Reset: §f[§r' . ($resetStatus->entitiesReset === true ? '✔' : '⛌') . '§f]');
                ScoreFactory::setScoreLine($player, 7, ' §aWorlds Reset: §f[§r' . ($resetStatus->worldReset === true ? '✔' : '⛌') . '§f]');
                ScoreFactory::setScoreLine($player, 8, ' §aTimers Reset: §f[§r' . ($resetStatus->timerReset === true ? '✔' : '⛌') . '§f]');
                ScoreFactory::setScoreLine($player, 9, ' §aTeams Reset: §f[§r' . ($resetStatus->teamReset === true ? '✔' : '⛌') . '§f]');
                ScoreFactory::setScoreLine($player, 10, ' §aOthers Reset: §f[§r' . ($resetStatus->othersReset === true ? '✔' : '⛌') . '§f]');
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
        $gameManager = $this->plugin->getManager();

        if (isset($this->bossBar)) {
            foreach ($this->plugin->getSessionManager()->getSessions() as $session) {
                $this->bossBar->hideFrom($session->getPlayer());
            }
        }

        switch ($gameManager->getPhase()) {
            case PhaseChangeEvent::GRACE:
                $changedTimeGrace = $gameManager->getGraceTimer() - 601;
                $changedTimePVP = $gameManager->getGraceTimer();

                if ($changedTimeGrace >= 0) {
                    $this->bossBar = $this->plugin->getBossBar('§fPVP Enables In: §a' . gmdate('i:s', $changedTimePVP) . "\n\n" . '§fFinal Heal In: §a' . gmdate('i:s', $changedTimeGrace), floatval($changedTimeGrace / 599));
                } else {
                    if ($gameManager->getGraceTimer() >= 300) {
                        $this->bossBar = $this->plugin->getBossBar('§fPVP Enables In: §a' . gmdate('i:s', $changedTimePVP), floatval($changedTimePVP / 599));
                    } else {
                        $this->bossBar = $this->plugin->getBossBar('§fPVP Enables In: §c' . gmdate('i:s', $changedTimePVP), floatval($changedTimePVP / 599));
                    }
                }
                break;
            case PhaseChangeEvent::PVP:
                if ($this->border->getSize() >= 499) {
                    $changedTime = $gameManager->getPVPTimer() - 900;
                    if ($gameManager->getPVPTimer() - 900 >= 61) {
                        $this->bossBar = $this->plugin->getBossBar('§fBorder Shrinks(400): §a' . gmdate('i:s', $changedTime), floatval($changedTime / 300));
                    } elseif ($gameManager->getPVPTimer() - 900 <= 60) {
                        $this->bossBar = $this->plugin->getBossBar('§fBorder Shrinks(400): §c' . gmdate('i:s', $changedTime), floatval($changedTime / 300));
                    }
                } elseif ($this->border->getSize() >= 399) {
                    $changedTime = $gameManager->getPVPTimer() - 600;
                    if ($gameManager->getPVPTimer() - 600 >= 61) {
                        $this->bossBar = $this->plugin->getBossBar('§fBorder Shrinks(300): §a' . gmdate('i:s', $changedTime), floatval($changedTime / 300));
                    } elseif ($gameManager->getPVPTimer() - 600 <= 60) {
                        $this->bossBar = $this->plugin->getBossBar('§fBorder Shrinks(300): §c' . gmdate('i:s', $changedTime), floatval($changedTime / 300));
                    }
                } elseif ($this->border->getSize() >= 299) {
                    $changedTime = $gameManager->getPVPTimer() - 300;
                    if ($gameManager->getPVPTimer() - 300 >= 61) {
                        $this->bossBar = $this->plugin->getBossBar('§fBorder Shrinks(200): §a' . gmdate('i:s', $changedTime), floatval($changedTime / 300));
                    } elseif ($gameManager->getPVPTimer() - 300 <= 60) {
                        $this->bossBar = $this->plugin->getBossBar('§fBorder Shrinks(200): §c' . gmdate('i:s', $changedTime), floatval($changedTime / 300));
                    }
                } elseif ($this->border->getSize() >= 199) {
                    $changedTime = $gameManager->getPVPTimer() - 0;
                    if ($gameManager->getPVPTimer() - 0 >= 61) { // reason why i leave it as - 0 is to note myself
                        $this->bossBar = $this->plugin->getBossBar('§fBorder Shrinks(100): §a' . gmdate('i:s', $changedTime), floatval($changedTime / 300));
                    } elseif ($gameManager->getPVPTimer() - 0 <= 60) {
                        $this->bossBar = $this->plugin->getBossBar('§fBorder Shrinks(100): §c' . gmdate('i:s', $changedTime), floatval($changedTime / 300));
                    }
                }
                break;
            case PhaseChangeEvent::DEATHMATCH:
                if ($gameManager->getDeathmatchTimer() >= 900) { // needs testing
                    $changedTime = $gameManager->getDeathmatchTimer() - 900;
                    $this->bossBar = $this->plugin->getBossBar('§fDeathmatch In: §c' . gmdate('i:s', $changedTime), floatval($changedTime / 300));
                } elseif ($this->border->getSize() >= 99) {
                    $changedTime = $gameManager->getDeathmatchTimer() - 700;
                    if ($gameManager->getDeathmatchTimer() - 700 >= 61) {
                        $this->bossBar = $this->plugin->getBossBar('§fBorder Shrinks(50): §a' . gmdate('i:s', $changedTime), floatval($changedTime / 300));
                    } elseif ($gameManager->getDeathmatchTimer() - 700 <= 60) {
                        $this->bossBar = $this->plugin->getBossBar('§fBorder Shrinks(50): §c' . gmdate('i:s', $changedTime), floatval($changedTime / 300));
                    }
                } elseif ($this->border->getSize() >= 49) {
                    $changedTime = $gameManager->getDeathmatchTimer() - 400;
                    if ($gameManager->getDeathmatchTimer() - 400 >= 61) {
                        $this->bossBar = $this->plugin->getBossBar('§fBorder Shrinks(10): §a' . gmdate('i:s', $changedTime), floatval($changedTime / 300));
                    } elseif ($gameManager->getDeathmatchTimer() - 400 <= 60) {
                        $this->bossBar = $this->plugin->getBossBar('§fBorder Shrinks(10): §c' . gmdate('i:s', $changedTime), floatval($changedTime / 300));
                    }
                } elseif ($this->border->getSize() >= 9) {
                    $changedTime = $gameManager->getDeathmatchTimer() - 300;
                     if ($gameManager->getDeathmatchTimer() - 300 >= 61) {
                        $this->bossBar = $this->plugin->getBossBar('§fBorder Shrinks(1): §a' . gmdate('i:s', $changedTime), floatval($changedTime / 100));
                    } elseif ($gameManager->getDeathmatchTimer() - 300 <= 60) {
                        $this->bossBar = $this->plugin->getBossBar('§fBorder Shrinks(1): §c' . gmdate('i:s', $changedTime), floatval($changedTime / 100));
                    }
                }
                break;
        }

        if (isset($this->bossBar)) {
            foreach ($this->plugin->getSessionManager()->getSessions() as $session) {
                $this->bossBar->showTo($session->getPlayer());
            }
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
        $gameManager = $this->plugin->getManager();
        $borderSize = $this->border->getSize();

        if ($gameManager->shrinking === true) {
            if ($this->border->reductionSize !== 0) {
                $this->border->setSize($this->border->getSize() - 1);
                $this->border->reductionSize--;
            }
        }

        foreach ($server->getOnlinePlayers() as $player) {
            $minX = -$borderSize;
			$maxX = $borderSize;
			$minZ = -$borderSize;
			$maxZ = $borderSize;

            $playerx = $player->getFloorX();
            $playery = $player->getFloorY();
            $playerz = $player->getFloorZ();
			
			$aabb = new AxisAlignedBB($minX, 0, $minZ, $maxX, $player->getLevel()->getWorldHeight(), $maxZ);
			if (!$aabb->isVectorInXZ($player->getPosition())) {
				switch ($gameManager->getPhase()) {
                    case PhaseChangeEvent::WAITING:
                    case PhaseChangeEvent::COUNTDOWN:
                        $level = $server->getLevelByName($this->plugin->map);
                        $player->teleport(new Position($this->plugin->spawnPosX, $this->plugin->spawnPosY, $this->plugin->spawnPosZ, $level));
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
    
    /**
     * sendSound
     *
     * @param  int $soundType
     * @return void
     */
    public function sendSound(int $soundType = 1): void
    {      
        switch ($soundType) {
            case 1:
                foreach ($this->plugin->getSessionManager()->getSessions() as $session) {
                    $player = $session->getPlayer();
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 2:
                foreach ($this->plugin->getSessionManager()->getSessions() as $session) {
                    $player = $session->getPlayer();
                    $player->getLevel()->addSound(new BlazeShootSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
        }
    }
}
