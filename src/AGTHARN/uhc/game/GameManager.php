<?php

declare(strict_types=1);

namespace AGTHARN\uhc\game;

use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\level\sound\BlazeShootSound;
use pocketmine\level\sound\ClickSound;
use pocketmine\level\Position;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Effect;
use pocketmine\item\ItemIds;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat as TF;
use pocketmine\scheduler\Task;
use pocketmine\math\Vector3;
use pocketmine\Player;

use AGTHARN\uhc\event\PhaseChangeEvent;
use AGTHARN\uhc\game\type\GameTimer;
use AGTHARN\uhc\Main;

use AGTHARN\uhc\libs\JackMD\ScoreFactory\ScoreFactory;

class GameManager extends Task
{
    /** @var int */
    private $game = 0;
    /** @var int */
    public const MIN_PLAYERS = 2;

    /** @var int */
    public $phase = PhaseChangeEvent::WAITING;
    /** @var int */
    public $countdown = GameTimer::TIMER_COUNTDOWN;
    /** @var float|int */
    public $grace = GameTimer::TIMER_GRACE;
    /** @var float|int */
    public $pvp = GameTimer::TIMER_PVP;
    /** @var float|int */
    public $deathmatch = GameTimer::TIMER_DEATHMATCH;
    /** @var int */
    public $winner = GameTimer::TIMER_WINNER;
    /** @var int */
    public $reset = GameTimer::TIMER_RESET;
    
    /** @var Border */
    private $border;
    /** @var Main */
    private $plugin;

    /** @var int */
    private $playerTimer = 1;
        
    /** @var bool */
    private $shrinking = false;
    
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
    }
    
    /**
     * onRun
     *
     * @param  int $currentTick
     * @return void
     */
    public function onRun(int $currentTick): void
    {
        $server = $this->plugin->getServer();
        $this->handlePlayers();
        $this->handleBossBar();
        
        switch ($this->getPhase()) {
            case PhaseChangeEvent::WAITING:
                $this->handleWaiting();
                break;
            case PhaseChangeEvent::COUNTDOWN:
                $this->handleCountdown();
                break;
            case PhaseChangeEvent::GRACE:
                $this->handleGrace();
                break;
            case PhaseChangeEvent::PVP:
                $this->handlePvP();
                break;
            case PhaseChangeEvent::DEATHMATCH:
                $this->handleDeathmatch();
                break;
            case PhaseChangeEvent::WINNER:
                $this->handleWinner();
                break;
            case PhaseChangeEvent::RESET:
                $this->handleReset();
                break;
        }
        if ($this->hasStarted() && $this->phase !== PhaseChangeEvent::WINNER) $this->game++;
        
        $server->getLevelByName($this->plugin->map)->setTime(1000);

        if (!$this->hasStarted()) {
            $server->getNetwork()->setName("NOT STARTED");
        } else {
            $server->getNetwork()->setName("STARTED");
        }
        
        foreach ($server->getOnlinePlayers() as $player) {
            $playerx = $player->getFloorX();
            $playery = $player->getFloorY();
            $playerz = $player->getFloorZ();
            
            if (!$player->hasEffect(16)) {
                $player->addEffect(new EffectInstance(Effect::getEffect(16), 1000000, 1, false));
            }
            
            if ($playerx >= $this->border->getSize() || -$playerx >= $this->border->getSize() || $playery >= $this->border->getSize() || $playerz >= $this->border->getSize() || -$playerz >= $this->border->getSize()) {
                if ($this->phase === PhaseChangeEvent::WAITING || $this->phase === PhaseChangeEvent::COUNTDOWN) {
                    $level = $server->getLevelByName($this->plugin->map);
                    
                    $player->teleport(new Position($this->plugin->spawnPosX, $this->plugin->spawnPosY, $this->plugin->spawnPosZ, $level));
                } else {
                    $player->addEffect(new EffectInstance(Effect::getEffect(19), 60, 1, false));
                    if ($player->getHealth() <= 2) {
                        $player->addEffect(new EffectInstance(Effect::getEffect(7), 100, 1, false));
                    }
                }
            }
            if ($playerx >= $this->border->getSize() - 20 || -$playerx >= $this->border->getSize() - 20 || $playery >= $this->border->getSize() - 20 || $playerz >= $this->border->getSize() - 20 || -$playerz >= $this->border->getSize() - 20) {
                $player->sendPopup(TF::RED . "BORDER IS CLOSE!");
            }
            
            if ($player->getLevel()->getName() !== $server->getLevelByName($this->plugin->map)) {
                $level = $server->getLevelByName($this->plugin->map);
                $player->teleport(new Position($this->plugin->spawnPosX, $this->plugin->spawnPosY, $this->plugin->spawnPosZ, $level));
            }
            
            if ($player->getGamemode() === Player::SPECTATOR) {
                $inventory = $player->getInventory();
                $item = Item::get(Item::BED, 0, 1)->setCustomName("§aReturn To Hub");
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
                $player->getInventory()->setItem(8, $item);
                
                $item2 = Item::get(35, 14, 1)->setCustomName("§cReport");
                $item2->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
                $player->getInventory()->setItem(0, $item2);
            }
        }
        
        if (count($this->plugin->getSessionManager()->getPlaying()) <= 1) {
            switch ($this->getPhase()) {
                case PhaseChangeEvent::WAITING:
                case PhaseChangeEvent::COUNTDOWN:
                case PhaseChangeEvent::WINNER:
                case PhaseChangeEvent::RESET:
                    return;
                default:
                    $this->border->setSize(500);
                    $this->setPhase(PhaseChangeEvent::WINNER);
                    break;
            }       
        }
        
        if ($this->shrinking == true) {
            switch ($this->getPhase()) {
                case PhaseChangeEvent::PVP:
                    if ($this->pvp >= 801 && $this->pvp <= 900) {
                        $this->border->setSize($this->border->getSize() - 1);
                    }
                    if ($this->pvp >= 501 && $this->pvp <= 600) {
                        $this->border->setSize($this->border->getSize() - 1);
                    }
                    if ($this->pvp >= 201 && $this->pvp <= 300) {
                        $this->border->setSize($this->border->getSize() - 1);
                    }
                    break;
                case PhaseChangeEvent::DEATHMATCH:
                    if ($this->deathmatch >= 1101) {
                        $this->border->setSize($this->border->getSize() - 1);
                    }
                    if ($this->deathmatch >= 651 && $this->deathmatch <= 700) {
                        $this->border->setSize($this->border->getSize() - 1);
                    }
                    if ($this->deathmatch >= 361 && $this->deathmatch <= 400) {
                        $this->border->setSize($this->border->getSize() - 1);
                    }
                    if ($this->deathmatch >= 291 && $this->deathmatch <= 300) {
                        $this->border->setSize($this->border->getSize() - 1);
                    }
                    break;
            }
        }
        $server->getLevelByName("UHC")->setAutoSave(false);
    }
    
    /**
     * handlePlayers
     *
     * @return void
     */
    private function handlePlayers(): void
    {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            $session = $this->plugin->getSessionManager()->getSession($player);
            if ($player->isSurvival()) {
                $session->setPlaying(true);
            } else {
                $session->setPlaying(false);
            }
            $this->handleScoreboard($player);
        }

        foreach ($this->plugin->getSessionManager()->getPlaying() as $player) {
            $session = $this->plugin->getSessionManager()->getSession($player);
            if ($session !== null) {
                $name = (string)$session->getTeam()->getNumber() ?? "NO TEAM";
                $player->setNameTag(TF::GOLD . "[$name] " . $player->getDisplayName());
            }
            switch ($this->getPhase()) {
                case PhaseChangeEvent::COUNTDOWN:
                    $player->setFood($player->getMaxFood());
                    $player->setHealth($player->getMaxHealth());
                    if ($this->countdown === 29) {
                        $this->randomizeCoordinates(-250, 250, 180, 200, -250, 250);
                        $player->removeAllEffects();
                        $player->getInventory()->clearAll();
                        $player->getArmorInventory()->clearAll();
                        $player->getCursorInventory()->clearAll();
                        $player->setImmobile(true);
                    } elseif ($this->countdown === 0) {
                        $player->setImmobile(false);
                    }
                    break;
                case PhaseChangeEvent::GRACE:
                    if ($this->grace === 601) {
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
    private function handleWaiting(): void
    {
        $server = $this->plugin->getServer();
        $this->border->setSize(500);
        
        $playerstartcount = self::MIN_PLAYERS - count($server->getOnlinePlayers());
        
        if (count($server->getOnlinePlayers()) >= self::MIN_PLAYERS) {
                $this->setPhase(PhaseChangeEvent::COUNTDOWN);
        }
        
        foreach ($this->plugin->getSessionManager()->getSessions() as $player) {
            $player->setFood($player->getMaxFood());
            $player->setHealth($player->getMaxHealth());
            $player->setImmobile(false);
            $this->handleScoreboard($player);
        }

        foreach ($server->getOnlinePlayers() as $player) {
            $inventory = $player->getInventory();
            $session = $this->plugin->getSessionManager()->getSession($player);
                
            $session->setPlaying(false);
            $player->setGamemode(Player::SURVIVAL);
            if (count($server->getOnlinePlayers()) <= self::MIN_PLAYERS) {
                if ($this->getPhase() === PhaseChangeEvent::WAITING && $player->getInventory()->getItemInHand()->hasEnchantment(17)) {
                    switch ($player->getInventory()->getItemInHand()->getId()) {
                        case 355:
                            if ($player->getInventory()->getItemInHand()->hasEnchantment(17)) {
                                $player->sendPopup("§aReturn To Hub");
                                return;
                            }
                            break;
                        case 35:
                            $player->sendPopup("§cReport");
                            return;
                    }
                }
                $player->sendPopup(TF::RED . $playerstartcount . " more players required...");
            }
            $item = Item::get(Item::BED, 0, 1)->setCustomName("§aReturn To Hub");
            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
            $player->getInventory()->setItem(8, $item);
                
            $item2 = Item::get(35, 14, 1)->setCustomName("§aReport");
            $item2->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
            $player->getInventory()->setItem(0, $item2);
        }
    }
    
    /**
     * handleCountdown
     *
     * @return void
     */
    private function handleCountdown(): void
    {
        $server = $this->plugin->getServer();
        switch ($this->countdown) {
            case 60:
                $this->setResetTimer(3);
                $this->setGameTimer(0);
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

                    $player->getArmorInventory()->setChestplate(Item::get(ItemIds::ELYTRA));
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
                }
                $this->setPhase(PhaseChangeEvent::GRACE);
                break;
        }
        $this->countdown--;
    }
    
    /**
     * handleGrace
     *
     * @return void
     */
    private function handleGrace(): void
    {
        $server = $this->plugin->getServer();
        switch ($this->grace) {
            case 1190:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "Final heal in " . TF::AQUA . "10 minutes.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 1180:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->getArmorInventory()->clearAll();
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
                $this->setPhase(PhaseChangeEvent::PVP);
                break;
        }
        $this->grace--;
    }
    
    /**
     * handlePvP
     *
     * @return void
     */
    private function handlePvP(): void
    {
        $server = $this->plugin->getServer();
        $this->setShrinking(true);
        switch ($this->pvp) {
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
                $this->setPhase(PhaseChangeEvent::DEATHMATCH);
                break;
        }
        $this->pvp--;
    }
    
    /**
     * handleDeathmatch
     *
     * @return void
     */
    public function handleDeathmatch(): void
    {
        $server = $this->plugin->getServer();
        switch ($this->deathmatch) {
            case 960:
                foreach ($server->getOnlinePlayers() as $player) {
                    $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "Deathmatch starts in " . TF::AQUA . "1 minute" . ".\n" . TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "All players would be teleported in 30 seconds.");
                    $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                }
                break;
            case 930:
                foreach ($server->getOnlinePlayers() as $player) {
                    $this->randomizeCoordinates(-99, 99, 180, 200, -99, 99);
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
                $this->setPhase(PhaseChangeEvent::WINNER);
                break;
        }
        $this->deathmatch--;
    }
        
    /**
     * handleWinner
     *
     * @return void
     */
    private function handleWinner(): void
    {
        $server = $this->plugin->getServer();
        
        switch ($this->winner) {
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
                    $player->setImmobile(false);
                    $this->handleScoreboard($player);

                    $session = $this->plugin->getSessionManager()->getSession($player);
                    $session->setPlaying(false);
                    $player->teleport($server->getLevelByName($this->plugin->map)->getSafeSpawn());
                    $player->setGamemode(Player::SURVIVAL);
                }
                $this->setShrinking(false);
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
                $this->setPhase(PhaseChangeEvent::RESET);
                break;
        }
        $this->winner--;
    }
        
    /**
     * handleReset
     *
     * @return void
     */
    private function handleReset(): void
    {
        $server = $this->plugin->getServer();
        
        switch ($this->reset) {
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
                $this->setGameTimer(0);
                $this->setCountdownTimer(60);
                $this->setGraceTimer(60 * 20);
                $this->setPVPTimer(60 * 20);
                $this->setDeathmatchTimer(60 * 20);
                $this->setWinnerTimer(60);
                $this->setShrinking(false);
            
                $server->getLogger()->info("Timers have been reset");
                break;
            case 0:
                $this->setPhase(PhaseChangeEvent::WAITING);
                $server->getLogger()->info("Changed to waiting phase");
                break;
        }
        $this->reset--;
    }
        
    /**
     * handleScoreboard
     *
     * @param  Player $player
     * @return void
     */
    private function handleScoreboard(Player $player): void
    {
        ScoreFactory::setScore($player, "§7»» §f§eMineUHC UHC-" . $this->plugin->uhcServer . " §7««");
        if ($this->hasStarted()) {
            ScoreFactory::setScoreLine($player, 1, "§7§l[-------------------]");
            ScoreFactory::setScoreLine($player, 2, " §fGame Time: §a" . gmdate("H:i:s", $this->game));

            switch ($this->getPhase()) {
                case PhaseChangeEvent::GRACE:
                    if ($this->grace >= 601) {
                        ScoreFactory::setScoreLine($player, 3, " §fFinal Heal In: §a" . (int)gmdate("i:s", (int)$this->grace - 601));
                    }
                    break;
            }

            ScoreFactory::setScoreLine($player, 1, "§7§l[-------------------]");
            ScoreFactory::setScoreLine($player, 2, " §fGame Time: §a" . gmdate("H:i:s", $this->game));
            switch ($this->getPhase()) {
                case PhaseChangeEvent::GRACE:
                    if ($this->grace <= 300) {
                        ScoreFactory::setScoreLine($player, 3, " §fPVP Enables In: §c" . gmdate("i:s", (int)$this->grace));
                    } else {
                        ScoreFactory::setScoreLine($player, 3, " §fPVP Enables In: §a" . gmdate("i:s", (int)$this->grace));
                    }
                    break;
                case PhaseChangeEvent::DEATHMATCH:
                    if ($this->deathmatch >= 900) {
                        ScoreFactory::setScoreLine($player, 3, " §fDeathmatch In: §c" . gmdate("i:s", (int)$this->deathmatch - 900));
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
            ScoreFactory::setScoreLine($player, 5, $this->getPhase() === PhaseChangeEvent::WAITING ? "§7 Waiting for more players..." : "§7 Starting in:§f $this->countdown");
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
        $bossBar = $this->plugin->getBossBar();
        switch ($this->getPhase()) {
            case PhaseChangeEvent::GRACE:
                $changedTime = (int)$this->grace - 601;

                $bossBar->setTitle("§fFinal Heal In: §a" . gmdate("i:s", $changedTime)); 
                $bossBar->setHealthPercent($changedTime / 599);
                break;
            case PhaseChangeEvent::PVP:
                if ($this->border->getSize() >= 499) {
                    $changedTime = (int)$this->pvp - 900;
                    if ($this->pvp - 900 >= 61) {
                        $bossBar->setTitle("§fBorder Shrinks(400): §a" . gmdate("i:s", $changedTime));
                        $bossBar->setHealthPercent($changedTime / 300);
                    } elseif ($this->pvp - 900 <= 60) {
                        $bossBar->setTitle("§fBorder Shrinks(400): §a" . gmdate("i:s", $changedTime));
                        $bossBar->setHealthPercent($changedTime / 300);
                    }
                } elseif ($this->border->getSize() >= 399) {
                    $changedTime = (int)$this->pvp - 600;
                    if ($this->pvp - 600 >= 61) {
                        $bossBar->setTitle("§fBorder Shrinks(300): §a" . gmdate("i:s", $changedTime));
                        $bossBar->setHealthPercent($changedTime / 300);
                    } elseif ($this->pvp - 600 <= 60) {
                        $bossBar->setTitle("§fBorder Shrinks(300): §a" . gmdate("i:s", $changedTime));
                        $bossBar->setHealthPercent($changedTime / 300);
                    }
                } elseif ($this->border->getSize() >= 299) {
                    $changedTime = (int)$this->pvp - 300;
                    if ($this->pvp - 300 >= 61) {
                        $bossBar->setTitle("§fBorder Shrinks(200): §a" . gmdate("i:s", $changedTime));
                        $bossBar->setHealthPercent($changedTime / 300);
                    } elseif ($this->pvp - 300 <= 60) {
                        $bossBar->setTitle("§fBorder Shrinks(200): §a" . gmdate("i:s", $changedTime));
                        $bossBar->setHealthPercent($changedTime / 300);
                    }
                } elseif ($this->border->getSize() >= 199) {
                    $changedTime = (int)$this->pvp - 0;
                    if ($this->pvp - 0 >= 61) { // reason why i leave it as - 0 is to note myself
                        $bossBar->setTitle("§fBorder Shrinks(100): §a" . gmdate("i:s", $changedTime));
                        $bossBar->setHealthPercent($changedTime / 300);
                    } elseif ($this->pvp - 0 <= 60) {
                        $bossBar->setTitle("§fBorder Shrinks(100): §a" . gmdate("i:s", $changedTime));
                        $bossBar->setHealthPercent($changedTime / 300);
                    }
                } elseif ($this->border->getSize() >= 99) {
                    $changedTime = (int)$this->deathmatch - 700;
                    if ($this->deathmatch - 700 >= 61) {
                        $bossBar->setTitle("§fBorder Shrinks(50): §a" . gmdate("i:s", $changedTime));
                        $bossBar->setHealthPercent($changedTime / 300);
                    } elseif ($this->deathmatch - 700 <= 60) {
                        $bossBar->setTitle("§fBorder Shrinks(50): §a" . gmdate("i:s", $changedTime));
                        $bossBar->setHealthPercent($changedTime / 300);
                    }
                } elseif ($this->border->getSize() >= 49) {
                    $changedTime = (int)$this->deathmatch - 400;
                    if ($this->deathmatch - 400 >= 61) {
                        $bossBar->setTitle("§fBorder Shrinks(10): §a" . gmdate("i:s", $changedTime));
                        $bossBar->setHealthPercent($changedTime / 100);
                    } elseif ($this->deathmatch - 400 <= 60) {
                        $bossBar->setTitle("§fBorder Shrinks(10): §a" . gmdate("i:s", $changedTime));
                        $bossBar->setHealthPercent($changedTime / 100);
                    }
                } elseif ($this->border->getSize() >= 9) {
                    $changedTime = (int)$this->deathmatch - 300;
                     if ($this->deathmatch - 300 >= 61) {
                        $bossBar->setTitle("§fBorder Shrinks(10): §a" . gmdate("i:s", $changedTime));
                        $bossBar->setHealthPercent($changedTime / 300);
                    } elseif ($this->deathmatch - 300 <= 60) {
                        $bossBar->setTitle("§fBorder Shrinks(10): §a" . gmdate("i:s", $changedTime));
                        $bossBar->setHealthPercent($changedTime / 300);
                    }
                }
                break;
        }
    }
    
    /**
     * randomizeCoordinates
     *
     * @param  int $x1
     * @param  int $x2
     * @param  int $y1
     * @param  int $y2
     * @param  int $z1
     * @param  int $z2
     * @return void
     */
    public function randomizeCoordinates(int $x1, int $x2, int $y1, int $y2, int $z1, int $z2): void
    {
        $server = $this->plugin->getServer();
        foreach ($this->plugin->getSessionManager()->getPlaying() as $player) {
            $x = mt_rand($x1, $x2);
            $y = mt_rand($y1, $y2);
            $z = mt_rand($z1, $z2);
            $level = $server->getLevelByName($this->plugin->map);
            
            $player->teleport(new Position($x, $y, $z, $level));
        }
        $this->playerTimer += 5;
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
        foreach ($this->plugin->getSessionManager()->getPlaying() as $playerSession) {
            $event = new PhaseChangeEvent($playerSession, $this->phase, $phase);
            $event->call();
        }
        $this->phase = $phase;
    }
        
    /**
     * setGameTimer
     *
     * @param  int $time
     * @return void
     */
    public function setGameTimer(int $time)
    {
        $this->game = $time;
    }
        
    /**
     * setCountdownTimer
     *
     * @param  int $time
     * @return void
     */
    public function setCountdownTimer(int $time): void
    {
        $this->countdown = $time;
    }
        
    /**
     * setGraceTimer
     *
     * @param  int $time
     * @return void
     */
    public function setGraceTimer(int $time): void
    {
        $this->grace = $time;
    }
        
    /**
     * setPVPTimer
     *
     * @param  int $time
     * @return void
     */
    public function setPVPTimer(int $time): void
    {
        $this->pvp = $time;
    }
        
    /**
     * setDeathmatchTimer
     *
     * @param  int $time
     * @return void
     */
    public function setDeathmatchTimer(int $time): void
    {
        $this->deathmatch = $time;
    }
        
    /**
     * setWinnerTimer
     *
     * @param  int $time
     * @return void
     */
    public function setWinnerTimer(int $time): void
    {
        $this->winner = $time;
    }
        
    /**
     * setResetTimer
     *
     * @param  int $time
     * @return void
     */
    public function setResetTimer(int $time): void
    {
        $this->reset = $time;
    }
    
    /**
     * hasStarted
     *
     * @return bool
     */
    public function hasStarted(): bool
    {
        return $this->getPhase() >= PhaseChangeEvent::GRACE;
    }
        
    /**
     * setShrinking
     *
     * @param  bool $shrinking
     * @return void
     */
    public function setShrinking(bool $shrinking)
    {
        $this->shrinking = $shrinking;
    }
}