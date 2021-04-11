<?php

declare(strict_types=1);

namespace AGTHARN\uhc\game;

use pocketmine\level\Position;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Effect;
use pocketmine\scheduler\Task;
use pocketmine\Player;

use AGTHARN\uhc\event\PhaseChangeEvent;
use AGTHARN\uhc\game\type\GameTimer;
use AGTHARN\uhc\game\border\Border;
use AGTHARN\uhc\Main;

use AGTHARN\uhc\libs\JackMD\ScoreFactory\ScoreFactory;

class GameManager extends Task
{
    /** @var int */
    public $game = 0;

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
    public $shrinking = false;
    
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
     * onRun
     *
     * @param  int $currentTick
     * @return void
     */
    public function onRun(int $currentTick): void
    {
        $server = $this->plugin->getServer();
        $handler = $this->plugin->getHandler();
        $handler->handlePlayers();
        $handler->handleBossBar();
        $handler->handleBorder();
        
        switch ($this->getPhase()) {
            case PhaseChangeEvent::WAITING:
                $handler->handleWaiting();
                break;
            case PhaseChangeEvent::COUNTDOWN:
                $handler->handleCountdown();
                break;
            case PhaseChangeEvent::GRACE:
                $handler->handleGrace();
                break;
            case PhaseChangeEvent::PVP:
                $handler->handlePvP();
                break;
            case PhaseChangeEvent::DEATHMATCH:
                $handler->handleDeathmatch();
                break;
            case PhaseChangeEvent::WINNER:
                $handler->handleWinner();
                break;
            case PhaseChangeEvent::RESET:
                $handler->handleReset();
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
            if (!$player->hasEffect(16)) {
                $player->addEffect(new EffectInstance(Effect::getEffect(16), 1000000, 1, false));
            }
            
            if ($player->getLevel()->getName() !== $this->plugin->map) {
                $level = $server->getLevelByName($this->plugin->map);
                $player->teleport(new Position($this->plugin->spawnPosX, $this->plugin->spawnPosY, $this->plugin->spawnPosZ, $level));
            }
            
            if ($player->getGamemode() === Player::SPECTATOR) {
                $this->plugin->getUtilItems()->giveItems($player);
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
            
            $player->getPlayer()->teleport(new Position($x, $y, $z, $level));
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
            $event = new PhaseChangeEvent($playerSession->getPlayer(), $this->phase, $phase);
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
     * getGraceTimer
     *
     * @return int
     */
    public function getGraceTimer(): int
    {
        return (int)$this->grace;
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
     * getPVPTimer
     *
     * @return int
     */
    public function getPVPTimer(): int
    {
        return (int)$this->pvp;
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
     * getDeathmatchTimer
     *
     * @return int
     */
    public function getDeathmatchTimer(): int
    {
        return (int)$this->deathmatch;
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
     * getWinnerTimer
     *
     * @return int
     */
    public function getWinnerTimer(): int
    {
        return (int)$this->winner;
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
     * getResetTimer
     *
     * @return int
     */
    public function getResetTimer(): int
    {
        return (int)$this->reset;
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