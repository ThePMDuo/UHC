<?php

namespace CLADevs\VanillaX\enchantments\weapons;

use CLADevs\VanillaX\enchantments\utils\EnchantmentTrait;
use pocketmine\data\bedrock\EnchantmentIds;
use pocketmine\item\Axe;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\ItemFlags;
use pocketmine\item\enchantment\Rarity;
use pocketmine\item\Item;
use pocketmine\item\Sword;

class SmiteEnchantment extends Enchantment{
    use EnchantmentTrait;

    public function __construct(){
        parent::__construct(EnchantmentIds::SMITE, "%enchantment.damage.undead", Rarity::UNCOMMON, ItemFlags::SWORD, ItemFlags::AXE, 5);
    }

    public function getIncompatibles(): array{
        return [EnchantmentIds::BANE_OF_ARTHROPODS, EnchantmentIds::SHARPNESS];
    }

    public function isItemCompatible(Item $item): bool{
        return $item instanceof Sword || $item instanceof Axe;
    }
}