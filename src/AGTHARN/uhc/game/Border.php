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

    /** @var int */
    private $safeX;
    /** @var int */
    private $safeZ;

    public function __construct(Level $level)
    {
        $this->level = $level;
        $this->safeX = $level->getSafeSpawn()->getFloorX();
        $this->safeZ = $level->getSafeSpawn()->getFloorZ();
    }

    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getX(bool $isNegative = false): int
    {
        return $isNegative ? ($this->safeX - $this->size) : ($this->safeX + $this->size);
    }

    public function getZ(bool $isNegative = false): int
    {
        return $isNegative ? ($this->safeZ - $this->size) : ($this->safeZ + $this->size);
    }
	
}
