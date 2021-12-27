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
namespace AGTHARN\uhc\game;

use pocketmine\scheduler\Task;
use pocketmine\world\Position;

use AGTHARN\uhc\event\phase\PhaseChangeEvent;
use AGTHARN\uhc\session\SessionManager;
use AGTHARN\uhc\game\border\Border;
use AGTHARN\uhc\game\GameProperties;
use AGTHARN\uhc\Main;

class GameManager extends Task
{
    /** @var Main */
    private Main $plugin;

    /** @var GameProperties */
    private GameProperties $gameProperties;
    /** @var SessionManager */
    private SessionManager $sessionManager;
    /** @var Border */
    private Border $border;

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
        $this->sessionManager = $plugin->getClass('SessionManager');
        $this->border = $plugin->getClass('Border');
    }
    
    /**
     * onRun
     * 
     * Runs every 1 second.
     *
     * @return void
     */
    public function onRun(): void
    {
        $server = $this->plugin->getServer();
        $server->getWorldManager()->getWorldByName($this->gameProperties->map)->setTime(1000);

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

        //$gameRuleUHC = $server->getWorldManager()->getWorldByName($this->gameProperties->map)->getGameRules();
        //$gameRuleUHC->setRuleWithMatching('showcoordinates', 'true');
        //$gameRuleUHC->setRuleWithMatching('doimmediaterespawn', 'true');
        
        if (count($this->sessionManager->getPlaying()) <= 1) {
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
        foreach ($this->sessionManager->getPlaying() as $player) {
            $x = mt_rand($x1, $x2);
            $y = mt_rand($y1, $y2);
            $z = mt_rand($z1, $z2);
            $world = $server->getWorldManager()->getWorldByName($this->gameProperties->map);
            
            $player->getPlayer()->teleport(new Position($x, $y, $z, $world));
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
        foreach ($this->sessionManager->getPlaying() as $playerSession) {
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
     * @return bool
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