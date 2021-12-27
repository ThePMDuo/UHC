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
namespace AGTHARN\uhc\event\phase;

use pocketmine\event\Event;
use pocketmine\player\Player;

class PhaseChangeEvent extends Event
{
    /** @var int */
    public const WAITING = -1;
    /** @var int */
    public const COUNTDOWN = 0;
    /** @var int */
    public const GRACE = 1;
    /** @var int */
    public const PVP = 2;
    /** @var int */
    public const DEATHMATCH = 3;
    /** @var int */
    public const WINNER = 4;
    /** @var int */
    public const RESET = 5;

    /** @var Player */
    private Player $player;
    /** @var int */
    private int $oldPhase;
    /** @var int */
    private int $newPhase;
    
    /**
     * __construct
     *
     * @param  Player $player
     * @param  int $oldPhase
     * @param  int $newPhase
     * @return void
     */
    public function __construct(Player $player, int $oldPhase, int $newPhase)
    {
        $this->player = $player;
        $this->oldPhase = $oldPhase;
        $this->newPhase = $newPhase;
    }
    
    /**
     * getPlayer
     *
     * @return Player
     */
    public function getPlayer(): Player
    {
        return $this->player;
    }
    
    /**
     * getOldPhase
     *
     * @return int
     */
    public function getOldPhase(): int
    {
        return $this->oldPhase;
    }
    
    /**
     * getNewPhase
     *
     * @return int
     */
    public function getNewPhase(): int
    {
        return $this->newPhase;
    }
}