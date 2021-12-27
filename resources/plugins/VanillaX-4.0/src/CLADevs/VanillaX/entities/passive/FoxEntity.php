<?php

namespace CLADevs\VanillaX\entities\passive;

use CLADevs\VanillaX\entities\VanillaEntity;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class FoxEntity extends VanillaEntity{

    const NETWORK_ID = EntityIds::FOX;

    public float $width = 0.6;
    public float $height = 0.7;

    protected function initEntity(CompoundTag $nbt): void{
        parent::initEntity($nbt);
        $this->setMaxHealth(20);
    }

    public function getName(): string{
        return "Fox";
    }

    public function getXpDropAmount(): int{
        return $this->getLastHitByPlayer() ? mt_rand(1, 3) : 0;
    }
}