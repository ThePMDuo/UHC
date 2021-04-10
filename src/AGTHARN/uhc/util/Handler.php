<?php
declare(strict_types=1);

namespace AGTHARN\uhc\util;

use pocketmine\level\sound\BlazeShootSound;
use pocketmine\level\sound\ClickSound;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Effect;
use pocketmine\item\ItemIds;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat as TF;
use pocketmine\math\Vector3;
use pocketmine\Player;

use AGTHARN\uhc\event\PhaseChangeEvent;
use AGTHARN\uhc\game\Border;
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
    private $bossBar;

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
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            $session = $this->plugin->getSessionManager()->getSession($player);
            $gameManager = $this->plugin->getManager();

            $this->handleScoreboard($player);
            if ($player->isSurvival()) {
                $session->setPlaying(true);
            } else {
                $session->setPlaying(false);
            }

            if ($session !== null) {
                $name = (string)$session->getTeam()->getNumber() ?? "NO TEAM";
                $player->setNameTag(TF::GOLD . "[$name] " . $player->getDisplayName());
            }
            switch ($gameManager->getPhase()) {
                case PhaseChangeEvent::COUNTDOWN:
                    $player->setFood($player->getMaxFood());
                    $player->setHealth($player->getMaxHealth());
                    if ($gameManager->countdown === 29) {
                        $gameManager->randomizeCoordinates(-250, 250, 180, 200, -250, 250);
                        $player->removeAllEffects();
                        $player->getInventory()->clearAll();
                        $player->getArmorInventory()->clearAll();
                        $player->getCursorInventory()->clearAll();
                        $player->getOffHandInventory()->clearAll(); /** @phpstan-ignore-line */
                        $player->setImmobile(true);
                    } elseif ($gameManager->countdown === 0) {
                        $player->setImmobile(false);
                    }
                    break;
                case PhaseChangeEvent::GRACE:
                    if ($gameManager->getGraceTimer() === 601) {
                        $player->setHealth($player->getMaxHealth());
                    }
                    break;
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
            $session->setPlaying(false);

            $player->setFood($player->getMaxFood());
            $player->setHealth($player->getMaxHealth());
            $player->removeAllEffects();
            $player->getInventory()->clearAll();
            $player->getArmorInventory()->clearAll();
            $player->getCursorInventory()->clearAll();
            $player->getOffHandInventory()->clearAll(); /** @phpstan-ignore-line */
            if (count($server->getOnlinePlayers()) <= self::MIN_PLAYERS) {
                $player->sendPopup(TF::RED . $playerstartcount . " more players required...");
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
                    $player->removeAllEffects();
                    $player->getInventory()->clearAll();
                    $player->getArmorInventory()->clearAll();
                    $player->getCursorInventory()->clearAll();
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "Game starting in " . TF::AQUA . "60 seconds!");
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "All players will be teleported in " . TF::AQUA . "30 seconds!");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 45:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "All players will be teleported in " . TF::AQUA . "15 seconds!");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 30:
                $this->border->setSize(500);
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "The game will begin in " . TF::AQUA . "30 seconds.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 10:
            foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "The game will begin in " . TF::AQUA . "10 seconds.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 3:
            foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "The game will begin in " . TF::AQUA . "3 seconds.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 2:
            foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "The game will begin in " . TF::AQUA . "2 seconds.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 1:
            foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "The game will begin in " . TF::AQUA . "1 second.");
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
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . TF::BOLD . "The match has begun!");
                    $player->getLevel()->addSound(new BlazeShootSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                    $player->getArmorInventory()->setChestplate(Item::get(ItemIds::ELYTRA));
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
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "Final heal in " . TF::AQUA . "10 minutes.");
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
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "Final heal has " . TF::AQUA . "occurred!");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 600:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "PVP will enable in 10 minutes.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 300:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "PVP will enable in 5 minutes.");
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "The border will start shrinking to " . TF::AQUA . "400" . TF::WHITE . " in " . TF::AQUA . "10 minutes.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 60:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "PVP will enable in 1 minute.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 30:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "PVP will enable in 30 seconds.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 10:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "PVP will enable in 10 seconds.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 3:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "PvP will be enabled in 3 seconds.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 2:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "PvP will be enabled in 2 seconds.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 1:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "PvP will be enabled in 1 second.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 0:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "PvP has been enabled!");
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
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "The border will start shrinking to " . TF::AQUA . "400" . TF::WHITE . " in " . TF::AQUA . "5 minutes");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 900:
                foreach ($server->getOnlinePlayers() as $player) {
                    //$this->border->setSize(400);
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "The border is now shrinking to " . TF::AQUA . "400.\n" . TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "Shrinking to " . TF::AQUA . "300" . TF::WHITE . " in " . TF::AQUA . "5 minutes.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 600:
                foreach ($server->getOnlinePlayers() as $player) {
                    //$this->border->setSize(300);
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "The border is now shrinking to " . TF::AQUA . "300.\n" . TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "Shrinking to " . TF::AQUA . "200" . TF::WHITE . " in " . TF::AQUA . "5 minutes.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 300:
                foreach ($server->getOnlinePlayers() as $player) {
                    //$this->border->setSize(200);
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "The border is now shrinking to " . TF::AQUA . "200.\n" . TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "Shrinking to " . TF::AQUA . "100" . TF::WHITE . " in " . TF::AQUA . "5 minutes.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "Deathmatch starts in " . TF::AQUA . "10 minutes" . ".\n" . TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "All players would be teleported before the Deathmatch starts.");
                }
                break;
            case 0:
                foreach ($server->getOnlinePlayers() as $player) {
                    //$this->border->setSize(100);
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "The border is now shrinking to " . TF::AQUA . "100.");
                    $player->getLevel()->addSound(new BlazeShootSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "Deathmatch starts in " . TF::AQUA . "5 minutes" . ".\n" . TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "All players would be teleported before the Deathmatch starts.");
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
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "Deathmatch starts in " . TF::AQUA . "1 minute" . ".\n" . TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "All players would be teleported in 30 seconds.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 930:
                foreach ($server->getOnlinePlayers() as $player) {
                    $gameManager->randomizeCoordinates(-99, 99, 180, 200, -99, 99);
                    $player->setImmobile(true);
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "Deathmatch starts in 30 seconds" . ".\n" . TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "All players have been teleported.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 900:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->setImmobile(false);
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "Deathmatch has started, " . TF::AQUA . "GOOD LUCK!\n" . TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "Border is shrinking to " . TF::AQUA . "100" . TF::WHITE . " in " . TF::AQUA . "5 minutes.");
                    $player->getLevel()->addSound(new BlazeShootSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 700:
                foreach ($server->getOnlinePlayers() as $player) {
                    //$this->border->setSize(50);
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "The border is now shrinking to " . TF::AQUA . "50.\n" . TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "Shrinking to " . TF::AQUA . "75" . TF::WHITE . " in " . TF::AQUA . "5 minutes.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 400:
                foreach ($server->getOnlinePlayers() as $player) {
                    //$this->border->setSize(10);
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "The border is now shrinking to " . TF::AQUA . "10.\n" . TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "Shrinking to " . TF::AQUA . "50" . TF::WHITE . " in " . TF::AQUA . "5 minutes.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 300:
                foreach ($server->getOnlinePlayers() as $player) {
                    //$this->border->setSize(1);
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "The border is now shrinking to " . TF::AQUA . "1.");
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "THE GAME IS ENDING IN 5 MINS!!!");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 0:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "GAME OVER!");
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
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::GREEN . "Congratulations to the winner!");
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
                    $session->setPlaying(false);
                    $player->teleport($server->getLevelByName($this->plugin->map)->getSafeSpawn());
                    $player->setGamemode(Player::SURVIVAL);
                }
                $gameManager->setShrinking(false);
                break;
            case 45:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "All players would be sent back to the games hub in " . TF::AQUA . "40 seconds as map resets!");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 30:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "All players would be sent back to the games hub in " . TF::AQUA . "25 seconds as map resets!");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 10:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "All players will be sent back to the games hub in " . TF::AQUA . "5 seconds!");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 7:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "Thanks for playing on " . TF::AQUA . "MineWarrior UHC!");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 5:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "ALL PLAYERS TELEPORTING!");
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
                        if ($entity->getSaveId() === "Slapper") return;
                        if (!$entity instanceof Player) $entity->close(); 
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
                $this->plugin->getTeamManager()->resetTeams();
            
                $server->getLogger()->info("Timers have been reset");
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
            ScoreFactory::setScoreLine($player, 8, " §fTPS: §a" . $this->plugin->getServer()->getTicksPerSecond());
            ScoreFactory::setScoreLine($player, 9, "   ");
            ScoreFactory::setScoreLine($player, 10, " §fBorder: §a± " . $this->border->getSize());
            ScoreFactory::setScoreLine($player, 11, " §fCenter: §a0, 0");
            ScoreFactory::setScoreLine($player, 12, "    ");
            ScoreFactory::setScoreLine($player, 13, "§7§l[-------------------] ");
            ScoreFactory::setScoreLine($player, 14, " §eplay.minewarrior.xyz");
        } else {
            ScoreFactory::setScoreLine($player, 1, "§7§l[-------------------]");
            ScoreFactory::setScoreLine($player, 2, " §fPlayers §f");
            ScoreFactory::setScoreLine($player, 3, " §a" . count($this->plugin->getSessionManager()->getPlaying()) . "§f§7/50");
            ScoreFactory::setScoreLine($player, 4, " ");
            ScoreFactory::setScoreLine($player, 5, $gameManager->getPhase() === PhaseChangeEvent::WAITING ? "§7 Waiting for more players..." : "§7 Starting in:§f $gameManager->countdown");
            ScoreFactory::setScoreLine($player, 6, "  ");
            ScoreFactory::setScoreLine($player, 7, "§7§l[-------------------] ");
            ScoreFactory::setScoreLine($player, 8, " §eplay.minewarrior.xyz");
        }
    }
    
    /**
     * handleBossBar
     *
     * @return void
     */
    public function handleBossBar(): void
    {   
        $gameManager = $this->plugin->getManager();

        if ($this->bossBar !== null) {
            foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
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
            foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
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
        $gameManager = $this->plugin->getManager();
        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            $playerx = $player->getFloorX();
            $playery = $player->getFloorY();
            $playerz = $player->getFloorZ();

            if ($playerx >= $this->border->getSize() - 20 || -$playerx >= $this->border->getSize() - 20 || $playery >= $this->border->getSize() - 20 || $playerz >= $this->border->getSize() - 20 || -$playerz >= $this->border->getSize() - 20) {
                $player->sendPopup(TF::RED . "BORDER IS CLOSE!");
            }
            
            if ($playerx >= $this->border->getSize() || -$playerx >= $this->border->getSize() || $playery >= $this->border->getSize() || $playerz >= $this->border->getSize() || -$playerz >= $this->border->getSize()) {
                switch ($this->getPhase()) {
                    case PhaseChangeEvent::WAITING:
                    case PhaseChangeEvent::COUNTDOWN:
                        $level = $this->getServer()->getLevelByName($this->plugin->map);
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
    }
}
