<?php

declare(strict_types=1);

namespace AGTHARN\uhc\game;

use pocketmine\scheduler\Task;
use pocketmine\level\Position;

use AGTHARN\uhc\event\phase\PhaseChangeEvent;
use AGTHARN\uhc\game\border\Border;
use AGTHARN\uhc\game\GameProperties;
use AGTHARN\uhc\Main;

class GameManager extends Task
{
    /** @var Main */
    private $plugin;

    /** @var GameProperties */
    private $gameProperties;
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

        $this->gameProperties = $plugin->getClass('GameProperties');
        $this->border = $plugin->getClass('Border');
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
        $server->getLevelByName($this->gameProperties->map)->setTime(1000);

        $gameHandler = $this->plugin->getClass('GameHandler');
        $gameHandler->handlePlayers();
        $gameHandler->handleBossBar();
        $gameHandler->handleBorder();

        switch ($this->getPhase()) {
            case PhaseChangeEvent::WAITING:
                $gameHandler->handleWaiting();
                break;
            case PhaseChangeEvent::COUNTDOWN:
                $gameHandler->handleCountdown();
                break;
            case PhaseChangeEvent::GRACE:
                $gameHandler->handleGrace();
                break;
            case PhaseChangeEvent::PVP:
                $gameHandler->handlePvP();
                break;
            case PhaseChangeEvent::DEATHMATCH:
                $gameHandler->handleDeathmatch();
                break;
            case PhaseChangeEvent::WINNER:
                $gameHandler->handleWinner();
                break;
            case PhaseChangeEvent::RESET:
                $gameHandler->handleReset();
                break;
        }
        if ($this->hasStarted()) {
            $this->gameProperties->game++;
            $server->getNetwork()->setName('STARTED');
        } else {
            $server->getNetwork()->setName('NOT STARTED');
        }

        if (!$this->plugin->getOperational()) {
            $server->getNetwork()->setName($this->plugin->getOperationalMessage());
        }

        //$gameRuleUHC = $server->getLevelByName($this->gameProperties->map)->getGameRules();
        //$gameRuleUHC->setRuleWithMatching('showcoordinates', 'true');
        //$gameRuleUHC->setRuleWithMatching('doimmediaterespawn', 'true');
        
        if (count($this->plugin->getClass('SessionManager')->getPlaying()) <= 1) {
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
        foreach ($this->plugin->getClass('SessionManager')->getPlaying() as $player) {
            $x = mt_rand($x1, $x2);
            $y = mt_rand($y1, $y2);
            $z = mt_rand($z1, $z2);
            $level = $server->getLevelByName($this->gameProperties->map);
            
            $player->getPlayer()->teleport(new Position($x, $y, $z, $level));
        }
    }

    /**
     * getPhase
     *
     * @return int
     */
    public function getPhase(): int
    {
        return $this->gameProperties->phase;
    }
    
    /**
     * setPhase
     *
     * @param  int $phase
     * @return void
     */
    public function setPhase(int $phase): void
    {
        foreach ($this->plugin->getClass('SessionManager')->getPlaying() as $playerSession) {
            $event = new PhaseChangeEvent($playerSession->getPlayer(), $this->gameProperties->phase, $phase);
            $event->call();
        }
        $this->gameProperties->phase = $phase;
    }
        
    /**
     * setGameTimer
     *
     * @param  int $time
     * @return void
     */
    public function setGameTimer(int $time)
    {
        $this->gameProperties->game = $time;
    }
        
    /**
     * setCountdownTimer
     *
     * @param  int $time
     * @return void
     */
    public function setCountdownTimer(int $time): void
    {
        $this->gameProperties->countdown = $time;
    }

    /**
     * getCountdownTimer
     *
     * @return int
     */
    public function getCountdownTimer(): int
    {
        return (int)$this->gameProperties->countdown;
    }
        
    /**
     * setGraceTimer
     *
     * @param  int $time
     * @return void
     */
    public function setGraceTimer(int $time): void
    {
        $this->gameProperties->grace = $time;
    }
    
    /**
     * getGraceTimer
     *
     * @return int
     */
    public function getGraceTimer(): int
    {
        return (int)$this->gameProperties->grace;
    }
        
    /**
     * setPVPTimer
     *
     * @param  int $time
     * @return void
     */
    public function setPVPTimer(int $time): void
    {
        $this->gameProperties->pvp = $time;
    }

    /**
     * getPVPTimer
     *
     * @return int
     */
    public function getPVPTimer(): int
    {
        return (int)$this->gameProperties->pvp;
    }
        
    /**
     * setDeathmatchTimer
     *
     * @param  int $time
     * @return void
     */
    public function setDeathmatchTimer(int $time): void
    {
        $this->gameProperties->deathmatch = $time;
    }

    /**
     * getDeathmatchTimer
     *
     * @return int
     */
    public function getDeathmatchTimer(): int
    {
        return (int)$this->gameProperties->deathmatch;
    }
        
    /**
     * setWinnerTimer
     *
     * @param  int $time
     * @return void
     */
    public function setWinnerTimer(int $time): void
    {
        $this->gameProperties->winner = $time;
    }

    /**
     * getWinnerTimer
     *
     * @return int
     */
    public function getWinnerTimer(): int
    {
        return (int)$this->gameProperties->winner;
    }
        
    /**
     * setResetTimer
     *
     * @param  int $time
     * @return void
     */
    public function setResetTimer(int $time): void
    {
        $this->gameProperties->reset = $time;
    }

    /**
     * getResetTimer
     *
     * @return int
     */
    public function getResetTimer(): int
    {
        return (int)$this->gameProperties->reset;
    }
    
    /**
     * hasStarted
     *
     * @return bool
     */
    public function hasStarted(): bool
    {
        return $this->getPhase() >= PhaseChangeEvent::GRACE && $this->getPhase() <= PhaseChangeEvent::DEATHMATCH;
    }
        
    /**
     * setShrinking
     *
     * @param  bool $shrinking
     * @return void
     */
    public function setShrinking(bool $shrinking)
    {
        $this->gameProperties->shrinking = $shrinking;
    }

    /**
     * getShrinking
     *
     * @return int
     */
    public function getShrinking(): bool
    {
        return $this->gameProperties->shrinking;
    }

    /**
     * setGlobalMute
     *
     * @param  bool $enabled
     * @return void
     */
    public function setGlobalMute(bool $enabled): void
    {
        $this->gameProperties->globalMuteEnabled = $enabled;
    }

    /**
     * isGlobalMuteEnabled
     *
     * @return bool
     */
    public function isGlobalMuteEnabled(): bool
    {
        return $this->gameProperties->globalMuteEnabled;
    }
}