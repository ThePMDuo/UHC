<?php

declare(strict_types=1);

namespace AGTHARN\uhc\event\phase;

use pocketmine\event\Event;
use pocketmine\Player;

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
    private $player;
    /** @var int */
    private $oldPhase;
    /** @var int */
    private $newPhase;
    
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