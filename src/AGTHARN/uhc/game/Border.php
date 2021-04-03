<?php
declare(strict_types=1);

namespace AGTHARN\uhc\game;

use pocketmine\level\Level;

class Border
{
    /** @var int */
    private $size = 500;

    /** @var Level */
    private $level;
    
    /**
     * __construct
     *
     * @param  Level $level
     * @return void
     */
    public function __construct(Level $level)
    {
        $this->level = $level;
        $this->safeX = $level->getSafeSpawn()->getFloorX();
        $this->safeZ = $level->getSafeSpawn()->getFloorZ();
    }
    
    /**
     * setSize
     *
     * @param  int $size
     * @return void
     */
    public function setSize(int $size): void
    {
        $this->size = $size;
    }
    
    /**
     * getSize
     *
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }   
}
