<?php
declare(strict_types=1);

namespace AGTHARN\uhc\game\team\type;

use pocketmine\Player;

class Team
{
    /** @var int */
    private int $teamNumber;
    /** @var Player */
    private $teamLeader;
    /** @var Player[] */
    private $members = [];
    /** @var int */
    public const TEAM_LIMIT = 1;

    public function __construct(int $teamNumber, Player $teamLeader)
    {
        $this->teamNumber = $teamNumber;
        $this->teamLeader = $teamLeader;

        $this->members[$teamLeader->getUniqueId()->toString()] = $teamLeader;
    }
    
    /**
     * getMembers
     *
     * @return array
     */
    public function getMembers(): array
    {
        return $this->members;
    }
    
    /**
     * memberExists
     *
     * @param  Player $player
     * @return bool
     */
    public function memberExists(Player $player): bool
	{
		return isset($this->members[$player->getUniqueId()->toString()]);
	}

    /**
     * getMemberNames
     *
     * @return array
     */
    public function getMemberNames(): array
    {   
        $memberNames = [];
        foreach ($this->members as $member) {
            $memberNames[] = $member->getName();
        }
        return $memberNames;
    }
    
    /**
     * addMember
     *
     * @param  Player $player
     * @return bool
     */
    public function addMember(Player $player): bool
    {
        if ($this->isFull() || $player->getUniqueId() === $this->teamLeader->getUniqueId()) {
            return false;
        }
        $this->members[$player->getUniqueId()->toString()] = $player;
        return true;
    }
    
    /**
     * removeMember
     *
     * @param  Player $player
     * @return bool
     */
    public function removeMember(Player $player): bool
    {
        if (!isset($this->members[$player->getUniqueId()->toString()]) || $player->getUniqueId() === $this->teamLeader->getUniqueId()) {
            return false;
        }
        unset($this->members[$player->getUniqueId()->toString()]);
        return true;
    }
    
    /**
     * getName
     *
     * @return int
     */
    public function getNumber(): int
    {
        return $this->teamNumber;
    }
    
    /**
     * getLeader
     *
     * @return Player
     */
    public function getLeader(): Player
    {
        return $this->teamLeader;
    }
    
    /**
     * isLeader
     *
     * @param  Player $player
     * @return bool
     */
    public function isLeader(Player $player): bool
    {
        return $this->teamLeader->getUniqueId()->toString() === $player->getUniqueId()->toString();
    }
    
    /**
     * isFull
     *
     * @return bool
     */
    public function isFull(): bool
    {
        return count($this->members) === self::TEAM_LIMIT;
    }
}