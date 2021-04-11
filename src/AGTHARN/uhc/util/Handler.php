<?php
declare(strict_types=1);

namespace AGTHARN\uhc\util;

use pocketmine\level\sound\BlazeShootSound;
use pocketmine\level\sound\ClickSound;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Effect;
use pocketmine\level\Position;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;

use AGTHARN\uhc\event\PhaseChangeEvent;
use AGTHARN\uhc\game\border\Border;
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
        foreach ($server->getOnlinePlayers() as $player) {
            $session = $this->plugin->getSessionManager()->getSession($player);
            $gameManager = $this->plugin->getManager();

            $this->handleScoreboard($player);
            if ($player->isSurvival()) {
                $session->setPlaying(true);
            } else {
                $this->plugin->getUtilItems()->giveItems($player);
                $session->setPlaying(false);
            }

            if ($session !== null) {
                $name = (string)$session->getTeam()->getNumber() ?? "NO TEAM";
                $player->setNameTag("§6[$name] " . $player->getDisplayName());
            }

            if (!$player->hasEffect(16)) {
                $player->addEffect(new EffectInstance(Effect::getEffect(16), 1000000, 1, false));
            }
            
            if ($player->getLevel()->getName() !== $this->plugin->map) {
                $level = $server->getLevelByName($this->plugin->map);
                $player->teleport(new Position($this->plugin->spawnPosX, $this->plugin->spawnPosY, $this->plugin->spawnPosZ, $level));
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
        $server = $this->plugin->getServer();
        $gameManager = $this->plugin->getManager();
        $playerstartcount = self::MIN_PLAYERS - count($server->getOnlinePlayers());

        $this->border->setSize(500);
        
        if (count($server->getOnlinePlayers()) >= self::MIN_PLAYERS) {
            $gameManager->setPhase(PhaseChangeEvent::COUNTDOWN);
        }

        foreach ($server->getOnlinePlayers() as $player) {
            $inventory = $player->getInventory();
            $session = $this->plugin->getSessionManager()->getSession($player);
            
            $this->handleScoreboard($player);

            $player->setFood($player->getMaxFood());
            $player->setHealth($player->getMaxHealth());
            $player->removeAllEffects();
            $player->getInventory()->clearAll();
            $player->getArmorInventory()->clearAll();
            $player->getCursorInventory()->clearAll();
            $player->getOffHandInventory()->clearAll(); /** @phpstan-ignore-line */
            if (count($server->getOnlinePlayers()) <= self::MIN_PLAYERS) {
                $player->sendPopup("§c" . $playerstartcount . " more players required...");
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

        switch ($gameManager->countdown) {
            case 60:
                $gameManager->setResetTimer(3);
                $gameManager->setGameTimer(0);
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage("§aJAX " . "§7»» " . "§rGame starting in " . "§b60 seconds!");
                    $player->sendMessage("§aJAX " . "§7»» " . "§rAll players will be teleported in " . "§b30 seconds!");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 45:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage("§aJAX " . "§7»» " . "§rAll players will be teleported in " . "§b15 seconds!");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 30:
                foreach ($server->getOnlinePlayers() as $player) {
                    $gameManager->randomizeCoordinates(-250, 250, 180, 200, -250, 250);
                    
                    $player->setFood($player->getMaxFood());
                    $player->setHealth($player->getMaxHealth());
                    $player->removeAllEffects();
                    $player->getInventory()->clearAll();
                    $player->getArmorInventory()->clearAll();
                    $player->getCursorInventory()->clearAll();
                    $player->getOffHandInventory()->clearAll(); /** @phpstan-ignore-line */
                    $player->setImmobile(true);

                    $player->sendMessage("§aJAX " . "§7»» " . "§rThe game will begin in " . "§b30 seconds.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 10:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage("§aJAX " . "§7»» " . "§rThe game will begin in " . "§b10 seconds.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 3:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage("§aJAX " . "§7»» " . "§rThe game will begin in " . "§b3 seconds.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 2:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage("§aJAX " . "§7»» " . "§rThe game will begin in " . "§b2 seconds.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 1:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage("§aJAX " . "§7»» " . "§rThe game will begin in " . "§b1 second.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 0:
                foreach ($this->plugin->getServer()->getDefaultLevel()->getEntities() as $entity) {
                    if (!$entity instanceof Player) {
                        $entity->flagForDespawn();
                    }
                }

                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage("§aJAX " . "§7»» §r§c§lThe match has begun!");
                    $player->getLevel()->addSound(new BlazeShootSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                    $player->getArmorInventory()->setChestplate(Item::get(Item::ELYTRA));
                    $player->setImmobile(false);
                }
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

        switch ($gameManager->getGraceTimer()) {
            case 1190:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage("§aJAX " . "§7»» " . "§rFinal heal in " . "§b10 minutes.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 1180:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->getArmorInventory()->clearAll();
                    // to do kits
                }
            case 601:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->setHealth($player->getMaxHealth());
                    $player->sendMessage("§aJAX " . "§7»» " . "§rHeal has " . "§boccurred!");
                    $player->setHealth($player->getMaxHealth());
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 600:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage("§aJAX " . "§7»» " . "§r§cPVP will enable in 10 minutes.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 300:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage("§aJAX " . "§7»» " . "§r§cPVP will enable in 5 minutes.");
                    $player->sendMessage("§aJAX " . "§7»» " . "§rThe border will start shrinking to " . "§b400" . "§f in " . "§b10 minutes.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 60:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage("§aJAX " . "§7»» " . "§r§cPVP will enable in 1 minute.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 30:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage("§aJAX " . "§7»» " . "§r§cPVP will enable in 30 seconds.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 10:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage("§aJAX " . "§7»» " . "§r§cPVP will enable in 10 seconds.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 3:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage("§aJAX " . "§7»» " . "§r§cPvP will be enabled in 3 seconds.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 2:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage("§aJAX " . "§7»» " . "§r§cPvP will be enabled in 2 seconds.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 1:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage("§aJAX " . "§7»» " . "§r§cPvP will be enabled in 1 second.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 0:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage("§aJAX " . "§7»» " . "§r§cPvP has been enabled!");
                    $player->getLevel()->addSound(new BlazeShootSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
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
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage("§aJAX " . "§7»» " . "§rThe border will start shrinking to " . "§b400" . "§f in " . "§b5 minutes");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 900:
                foreach ($server->getOnlinePlayers() as $player) {
                    //$this->border->setSize(400);
                    $player->sendMessage("§aJAX " . "§7»» " . "§rThe border is now shrinking to " . "§b400.\n" . "§aJAX " . "§7»» " . "§rShrinking to " . "§b300" . "§f in " . "§b5 minutes.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 600:
                foreach ($server->getOnlinePlayers() as $player) {
                    //$this->border->setSize(300);
                    $player->sendMessage("§aJAX " . "§7»» " . "§rThe border is now shrinking to " . "§b300.\n" . "§aJAX " . "§7»» " . "§rShrinking to " . "§b200" . "§f in " . "§b5 minutes.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 300:
                foreach ($server->getOnlinePlayers() as $player) {
                    //$this->border->setSize(200);
                    $player->sendMessage("§aJAX " . "§7»» " . "§rThe border is now shrinking to " . "§b200.\n" . "§aJAX " . "§7»» " . "§rShrinking to " . "§b100" . "§f in " . "§b5 minutes.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                    $player->sendMessage("§aJAX " . "§7»» " . "§r§cDeathmatch starts in " . "§b10 minutes" . ".\n" . "§aJAX " . "§7»» " . "§r§cAll players would be teleported before the Deathmatch starts.");
                }
                break;
            case 0:
                foreach ($server->getOnlinePlayers() as $player) {
                    //$this->border->setSize(100);
                    $player->sendMessage("§aJAX " . "§7»» " . "§rThe border is now shrinking to " . "§b100.");
                    $player->getLevel()->addSound(new BlazeShootSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                    $player->sendMessage("§aJAX " . "§7»» " . "§r§cDeathmatch starts in " . "§b5 minutes" . ".\n" . "§aJAX " . "§7»» " . "§r§cAll players would be teleported before the Deathmatch starts.");
                }
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

        switch ($gameManager->getDeathmatchTimer()) {
            case 960:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage("§aJAX " . "§7»» " . "§r§cDeathmatch starts in " . "§b1 minute" . ".\n" . "§aJAX " . "§7»» " . "§r§cAll players would be teleported in 30 seconds.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 930:
                foreach ($server->getOnlinePlayers() as $player) {
                    $gameManager->randomizeCoordinates(-99, 99, 180, 200, -99, 99);
                    $player->setImmobile(true);
                    $player->sendMessage("§aJAX " . "§7»» " . "§r§cDeathmatch starts in 30 seconds" . ".\n" . "§aJAX " . "§7»» " . "§r§cAll players have been teleported.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 900:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->setImmobile(false);
                    $player->sendMessage("§aJAX " . "§7»» " . "§rDeathmatch has started, " . "§bGOOD LUCK!\n" . "§aJAX " . "§7»» " . "§rBorder is shrinking to " . "§b100" . "§f in " . "§b5 minutes.");
                    $player->getLevel()->addSound(new BlazeShootSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 700:
                foreach ($server->getOnlinePlayers() as $player) {
                    //$this->border->setSize(50);
                    $player->sendMessage("§aJAX " . "§7»» " . "§rThe border is now shrinking to " . "§b50.\n" . "§aJAX " . "§7»» " . "§rShrinking to " . "§b75" . "§f in " . "§b5 minutes.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 400:
                foreach ($server->getOnlinePlayers() as $player) {
                    //$this->border->setSize(10);
                    $player->sendMessage("§aJAX " . "§7»» " . "§rThe border is now shrinking to " . "§b10.\n" . "§aJAX " . "§7»» " . "§rShrinking to " . "§b50" . "§f in " . "§b5 minutes.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 300:
                foreach ($server->getOnlinePlayers() as $player) {
                    //$this->border->setSize(1);
                    $player->sendMessage("§aJAX " . "§7»» " . "§rThe border is now shrinking to " . "§b1.");
                    $player->sendMessage("§aJAX " . "§7»» " . "§r§cTHE GAME IS ENDING IN 5 MINS!!!");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 0:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage("§aJAX " . "§7»» " . "§r§cGAME OVER!");
                    $player->getLevel()->addSound(new BlazeShootSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
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
        
        switch ($gameManager->getWinnerTimer()) {
            case 60:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage("§aJAX " . "§7»» " . "§r§aCongratulations to the winner!");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                    $player->setFood($player->getMaxFood());
                    $player->setHealth($player->getMaxHealth());
                    $player->removeAllEffects();
                    $player->getInventory()->clearAll();
                    $player->getArmorInventory()->clearAll();
                    $player->getCursorInventory()->clearAll();
                    $player->getOffHandInventory()->clearAll(); /** @phpstan-ignore-line */
                    $player->setImmobile(false);
                    $this->handleScoreboard($player);

                    $session = $this->plugin->getSessionManager()->getSession($player);
                    $player->teleport($server->getLevelByName($this->plugin->map)->getSafeSpawn());
                    $player->setGamemode(Player::SURVIVAL);
                }
                $gameManager->setShrinking(false);
                break;
            case 45:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage("§aJAX " . "§7»» " . "§rAll players would be sent back to the Hub in " . "§b40 seconds as map resets!");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 30:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage("§aJAX " . "§7»» " . "§rAll players would be sent back to the Hub in " . "§b25 seconds as map resets!");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 10:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage("§aJAX " . "§7»» " . "§rAll players will be sent back to the Hub in " . "§b5 seconds!");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 7:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage("§aJAX " . "§7»» " . "§rThanks for playing on " . "§bMineUHC!");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 5:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage("§aJAX " . "§7»» " . "§r§cALL PLAYERS TELEPORTING!");
                    $player->getLevel()->addSound(new BlazeShootSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 4:
                foreach ($server->getOnlinePlayers() as $player) {
                    $this->plugin->getServer()->dispatchCommand($player, "transfer hub");
                }
                break;
            case 1:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->kick();
                }
                break;
            case 0:
                $gameManager->setPhase(PhaseChangeEvent::RESET);
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
        
        switch ($gameManager->getResetTimer()) {
            case 3:
                $server->getLogger()->info("Starting reset");

                foreach ($server->getOnlinePlayers() as $player) { // ik this is the 2nd time. its just for safety measures
                    $player->kick();
                }
            
                foreach ($server->getLevels() as $level) {
                    foreach ($level->getEntities() as $entity) {
                        if (!$entity instanceof Player) {
                            $entity->close(); 
                        }
                    }
                }
                $this->plugin->prepareLevels();
                $server->getLogger()->info("World reset completed");
                break;
            case 2:
                $gameManager->setGameTimer(0);
                $gameManager->setCountdownTimer(60);
                $gameManager->setGraceTimer(60 * 20);
                $gameManager->setPVPTimer(60 * 20);
                $gameManager->setDeathmatchTimer(60 * 20);
                $gameManager->setWinnerTimer(60);
                $gameManager->setShrinking(false);
                $this->border->setSize(500);
                $this->plugin->getTeamManager()->resetTeams();
            
                $server->getLogger()->info("Timers, Teams & Borders have been reset");
                break;
            case 0:
                $gameManager->setPhase(PhaseChangeEvent::WAITING);
                $server->getLogger()->info("Changed to waiting phase");
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

        ScoreFactory::setScore($player, "§7»» §f§eMineUHC UHC-" . $this->plugin->uhcServer . " §7««");
        if ($gameManager->hasStarted()) {
            ScoreFactory::setScoreLine($player, 1, "§7§l[-------------------]");
            ScoreFactory::setScoreLine($player, 2, " §fGame Time: §a" . gmdate("H:i:s", $gameManager->game));

            ScoreFactory::setScoreLine($player, 1, "§7§l[-------------------]");
            ScoreFactory::setScoreLine($player, 2, " §fGame Time: §a" . gmdate("H:i:s", $gameManager->game));
            switch ($gameManager->getPhase()) {
                case PhaseChangeEvent::DEATHMATCH:
                    if ($gameManager->getDeathmatchTimer() >= 900) {
                        ScoreFactory::setScoreLine($player, 3, " §fDeathmatch In: §c" . gmdate("i:s", $gameManager->getDeathmatchTimer() - 900));
                    }
                    break;
            }

            ScoreFactory::setScoreLine($player, 4, " ");
            ScoreFactory::setScoreLine($player, 5, " §fPlayers: §a" . count($this->plugin->getSessionManager()->getPlaying()) . "§f§7/50");
            ScoreFactory::setScoreLine($player, 6, "  ");
            ScoreFactory::setScoreLine($player, 7, $this->plugin->getSessionManager()->hasSession($player) !== true ? " §fKills: §a0" : " §fKills: §a" . $this->plugin->getSessionManager()->getSession($player)->getEliminations());
            ScoreFactory::setScoreLine($player, 8, " §fTPS: §a" . $server->getTicksPerSecond());
            ScoreFactory::setScoreLine($player, 9, "   ");
            ScoreFactory::setScoreLine($player, 10, " §fBorder: §a± " . $this->border->getSize());
            ScoreFactory::setScoreLine($player, 11, " §fCenter: §a0, 0");
            ScoreFactory::setScoreLine($player, 12, "    ");
            ScoreFactory::setScoreLine($player, 13, "§7§l[-------------------] ");
            ScoreFactory::setScoreLine($player, 14, " §eplay.mineuhc.xyz");
        } else {
            ScoreFactory::setScoreLine($player, 1, "§7§l[-------------------]");
            ScoreFactory::setScoreLine($player, 2, " §fPlayers §f");
            ScoreFactory::setScoreLine($player, 3, " §a" . count($server->getOnlinePlayers()) . "§f§7/50");
            ScoreFactory::setScoreLine($player, 4, " ");
            ScoreFactory::setScoreLine($player, 5, $gameManager->getPhase() === PhaseChangeEvent::WAITING ? "§7 Waiting for more players..." : "§7 Starting in:§f $gameManager->countdown");
            ScoreFactory::setScoreLine($player, 6, "  ");
            ScoreFactory::setScoreLine($player, 7, "§7§l[-------------------] ");
            ScoreFactory::setScoreLine($player, 8, " §eplay.mineuhc.xyz");
        }
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

        if ($this->bossBar !== null) {
            foreach ($server->getOnlinePlayers() as $player) {
                $this->bossBar->hideFrom($player);
            }
        }

        switch ($gameManager->getPhase()) {
            case PhaseChangeEvent::GRACE:
                $changedTime = $gameManager->getGraceTimer() - 601;
                if ($changedTime >= 0) {
                    $this->bossBar = $this->plugin->getBossBar("§fFinal Heal In: §a" . gmdate("i:s", $changedTime), floatval($changedTime / 599));
                } else {
                    $changedTime = $gameManager->getGraceTimer();
                    if ($gameManager->getGraceTimer() >= 300) {
                        $this->bossBar = $this->plugin->getBossBar("§fPVP Enables In: §a" . gmdate("i:s", $changedTime), floatval($changedTime / 599));
                    } else {
                        $this->bossBar = $this->plugin->getBossBar("§fPVP Enables In: §c" . gmdate("i:s", $changedTime), floatval($changedTime / 599));
                    }
                }
                break;
            case PhaseChangeEvent::PVP:
                if ($this->border->getSize() >= 499) {
                    $changedTime = $gameManager->getPVPTimer() - 900;
                    if ($gameManager->getPVPTimer() - 900 >= 61) {
                        $this->bossBar = $this->plugin->getBossBar("§fBorder Shrinks(400): §a" . gmdate("i:s", $changedTime), floatval($changedTime / 300));
                    } elseif ($gameManager->getPVPTimer() - 900 <= 60) {
                        $this->bossBar = $this->plugin->getBossBar("§fBorder Shrinks(400): §c" . gmdate("i:s", $changedTime), floatval($changedTime / 300));
                    }
                } elseif ($this->border->getSize() >= 399) {
                    $changedTime = $gameManager->getPVPTimer() - 600;
                    if ($gameManager->getPVPTimer() - 600 >= 61) {
                        $this->bossBar = $this->plugin->getBossBar("§fBorder Shrinks(300): §a" . gmdate("i:s", $changedTime), floatval($changedTime / 300));
                    } elseif ($gameManager->getPVPTimer() - 600 <= 60) {
                        $this->bossBar = $this->plugin->getBossBar("§fBorder Shrinks(300): §c" . gmdate("i:s", $changedTime), floatval($changedTime / 300));
                    }
                } elseif ($this->border->getSize() >= 299) {
                    $changedTime = $gameManager->getPVPTimer() - 300;
                    if ($gameManager->getPVPTimer() - 300 >= 61) {
                        $this->bossBar = $this->plugin->getBossBar("§fBorder Shrinks(200): §a" . gmdate("i:s", $changedTime), floatval($changedTime / 300));
                    } elseif ($gameManager->getPVPTimer() - 300 <= 60) {
                        $this->bossBar = $this->plugin->getBossBar("§fBorder Shrinks(200): §c" . gmdate("i:s", $changedTime), floatval($changedTime / 300));
                    }
                } elseif ($this->border->getSize() >= 199) {
                    $changedTime = $gameManager->getPVPTimer() - 0;
                    if ($gameManager->getPVPTimer() - 0 >= 61) { // reason why i leave it as - 0 is to note myself
                        $this->bossBar = $this->plugin->getBossBar("§fBorder Shrinks(100): §a" . gmdate("i:s", $changedTime), floatval($changedTime / 300));
                    } elseif ($gameManager->getPVPTimer() - 0 <= 60) {
                        $this->bossBar = $this->plugin->getBossBar("§fBorder Shrinks(100): §c" . gmdate("i:s", $changedTime), floatval($changedTime / 300));
                    }
                } elseif ($this->border->getSize() >= 99) {
                    $changedTime = $gameManager->getDeathmatchTimer() - 700;
                    if ($gameManager->getDeathmatchTimer() - 700 >= 61) {
                        $this->bossBar = $this->plugin->getBossBar("§fBorder Shrinks(50): §a" . gmdate("i:s", $changedTime), floatval($changedTime / 300));
                    } elseif ($gameManager->getDeathmatchTimer() - 700 <= 60) {
                        $this->bossBar = $this->plugin->getBossBar("§fBorder Shrinks(50): §c" . gmdate("i:s", $changedTime), floatval($changedTime / 300));
                    }
                } elseif ($this->border->getSize() >= 49) {
                    $changedTime = $gameManager->getDeathmatchTimer() - 400;
                    if ($gameManager->getDeathmatchTimer() - 400 >= 61) {
                        $this->bossBar = $this->plugin->getBossBar("§fBorder Shrinks(10): §a" . gmdate("i:s", $changedTime), floatval($changedTime / 300));
                    } elseif ($gameManager->getDeathmatchTimer() - 400 <= 60) {
                        $this->bossBar = $this->plugin->getBossBar("§fBorder Shrinks(10): §c" . gmdate("i:s", $changedTime), floatval($changedTime / 300));
                    }
                } elseif ($this->border->getSize() >= 9) {
                    $changedTime = $gameManager->getDeathmatchTimer() - 300;
                     if ($gameManager->getDeathmatchTimer() - 300 >= 61) {
                        $this->bossBar = $this->plugin->getBossBar("§fBorder Shrinks(1): §a" . gmdate("i:s", $changedTime), floatval($changedTime / 100));
                    } elseif ($gameManager->getDeathmatchTimer() - 300 <= 60) {
                        $this->bossBar = $this->plugin->getBossBar("§fBorder Shrinks(1): §c" . gmdate("i:s", $changedTime), floatval($changedTime / 100));
                    }
                }
                break;
        }

        if ($this->bossBar !== null) {
            foreach ($server->getOnlinePlayers() as $player) {
                $this->bossBar->showTo($player);
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
        foreach ($server->getOnlinePlayers() as $player) {
            $playerx = $player->getFloorX();
            $playery = $player->getFloorY();
            $playerz = $player->getFloorZ();

            if ($playerx >= $this->border->getSize() - 20 || -$playerx >= $this->border->getSize() - 20 || $playery >= $this->border->getSize() - 20 || $playerz >= $this->border->getSize() - 20 || -$playerz >= $this->border->getSize() - 20) {
                $player->sendPopup("§cBORDER IS CLOSE!");
            }
            
            if ($playerx >= $this->border->getSize() || -$playerx >= $this->border->getSize() || $playery >= $this->border->getSize() || $playerz >= $this->border->getSize() || -$playerz >= $this->border->getSize()) {
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
        }

        if ($gameManager->shrinking === true) {
            switch ($gameManager->getPhase()) {
                case PhaseChangeEvent::PVP:
                    if ($gameManager->getPVPTimer() >= 801 && $gameManager->getPVPTimer() <= 900) {
                        $this->border->setSize($this->border->getSize() - 1);
                    }
                    if ($gameManager->getPVPTimer() >= 501 && $gameManager->getPVPTimer() <= 600) {
                        $this->border->setSize($this->border->getSize() - 1);
                    }
                    if ($gameManager->getPVPTimer() >= 201 && $gameManager->getPVPTimer() <= 300) {
                        $this->border->setSize($this->border->getSize() - 1);
                    }
                    break;
                case PhaseChangeEvent::DEATHMATCH:
                    if ($gameManager->getDeathmatchTimer() >= 1101) {
                        $this->border->setSize($this->border->getSize() - 1);
                    }
                    if ($gameManager->getDeathmatchTimer() >= 651 && $gameManager->getDeathmatchTimer() <= 700) {
                        $this->border->setSize($this->border->getSize() - 1);
                    }
                    if ($gameManager->getDeathmatchTimer() >= 361 && $gameManager->getDeathmatchTimer() <= 400) {
                        $this->border->setSize($this->border->getSize() - 1);
                    }
                    if ($gameManager->getDeathmatchTimer() >= 291 && $gameManager->getDeathmatchTimer() <= 300) {
                        $this->border->setSize($this->border->getSize() - 1);
                    }
                    break;
            }
        }
    }
}
