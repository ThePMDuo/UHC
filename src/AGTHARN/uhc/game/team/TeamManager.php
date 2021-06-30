<?php
declare(strict_types=1);

namespace AGTHARN\uhc\game\team;

use pocketmine\Player;

use AGTHARN\uhc\game\team\type\Team;

class TeamManager
{
    /** @var Team[] */
    private array $teams = [];
    /** @var int */
    private int $teamNumbers = 1;
    
    /**
     * getTeams
     *
     * @return array
     */
    public function getTeams(): array
    {
        return $this->teams;
    }
    
    /**
     * createTeam
     *
     * @param  Player $teamLeader
     * @return Team
     */
    public function createTeam(Player $teamLeader): Team
    {
        $team = new Team($this->teamNumbers, $teamLeader);
        $this->teams[$this->teamNumbers] = $team;
        $this->teamNumbers++;

        return $team;
    }
    
    /**
     * getTeam
     *
     * @param  int $teamNumber
     * @return Team
     */
    public function getTeam(int $teamNumber): ?Team
    {
        return $this->teamExists($teamNumber) ? $this->teams[$teamNumber] : null;
    }
    
    /**
     * teamExists
     *
     * @param  int $teamNumber
     * @return bool
     */
    public function teamExists(int $teamNumber): bool
    {
        return isset($this->teams[$teamNumber]);
    }
    
    /**
     * disbandTeam
     *
     * @param  int $teamNumber
     * @return void
     */
    public function disbandTeam(int $teamNumber): void
    {
        unset($this->teams[$teamNumber]);
    }
    
    /**
     * resetTeams
     *
     * @return void
     */
    public function resetTeams(): void
    {
        foreach ($this->teams as $team) {
            unset($team);
        }
        $this->teamNumbers = 1;
    }
}