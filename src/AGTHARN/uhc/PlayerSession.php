<?php
declare(strict_types=1);

namespace AGTHARN\uhc;

use pocketmine\utils\UUID;
use pocketmine\Player;

class PlayerSession
{
    /** @var UUID */
    private $uuid;

    /** @var Player */
    private $player;
    
    /** @var int[] */
    private $eliminations = [];
    
    /**
     * __construct
     *
     * @param  Player $player
     * @return void
     */
    public function __construct(Player $player)
    {
        $this->player = $player;
        $this->uuid = $player->getUniqueId();
        $this->eliminations[$player->getName()] = 0;
    }
    
    /**
     * getUniqueId
     *
     * @return UUID
     */
    public function getUniqueId(): UUID
    {
        return $this->uuid;
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
     * setPlayer
     *
     * @param  Player $player
     * @return void
     */
    public function setPlayer(Player $player): void
    {
        $this->player = $player;
    }
    
    /**
     * addElimination
     *
     * @return void
     */
    public function addElimination(): void
    {
        $this->eliminations[$this->player->getName()] = $this->eliminations[$this->player->getName()] + 1;
    }
    
    /**
     * getEliminations
     *
     * @return int
     */
    public function getEliminations(): int
    {
        return $this->eliminations[$this->player->getName()];
    }
    
    /**
     * create
     *
     * @param  Player $player
     * @return self
     */
    public static function create(Player $player): self
    {
        return new self($player);
    }
}
