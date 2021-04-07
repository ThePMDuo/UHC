<?php
declare(strict_types=1);

namespace AGTHARN\uhc;

use pocketmine\Player;

use AGTHARN\uhc\game\team\Team;

class PlayerSession
{

    /** @var Player */
    private $player;

    /** @var Team|null */
    private $team = null;
    
    /** @var int */
    private $eliminations = 0;
    
    /**
     * __construct
     *
     * @param  Player $player
     * @return void
     */
    public function __construct(Player $player)
    {
        $this->player = $player;
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
     * addEliminations
     *
     * @param  int $amount
     * @return void
     */
    public function addEliminations(int $amount = 1): void
    {
        $this->eliminations += $amount;
    }
    
    /**
     * getEliminations
     *
     * @return int
     */
    public function getEliminations(): int
    {
        return $this->eliminations;
    }
    
    /**
     * updatePlayer
     *
     * @param  Player $player
     * @return void
     */
    public function updatePlayer(Player $player)
    {
        $this->player = $player;
    }
    
    /**
     * getTeam
     *
     * @return Team
     */
    public function getTeam(): Team
    {
        return $this->team;
    }
    
    /**
     * isInTeam
     *
     * @return bool
     */
    public function isInTeam(): bool
    {
        return $this->team !== null;
    }
    
    /**
     * addToTeam
     *
     * @param  Team $team
     * @return bool
     */
    public function addToTeam(Team $team): bool
    {
        if ($team->isLeader($this->player) || $team->addMember($this->getPlayer())) {
            $this->team = $team;
            return true;
        }
        return false;
    }
    
    /**
     * removeFromTeam
     *
     * @return bool
     */
    public function removeFromTeam(): bool
    {
        if ($this->team->removeMember($this->getPlayer())) {
            $this->team = null;
            return true;
        } elseif($this->isTeamLeader()){
            $this->team = null;
            return true;
        }
        return false;
    }
    
    /**
     * isTeamLeader
     *
     * @return bool
     */
    public function isTeamLeader(): bool
    {
        return $this->isInTeam() && $this->team->isLeader($this->getPlayer());
    }
}
