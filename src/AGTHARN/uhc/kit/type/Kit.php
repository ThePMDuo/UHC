<?php
declare(strict_types=1);

namespace AGTHARN\uhc\kit\type;

use pocketmine\item\Item;

class Kit
{    
    
    /**
     * getKitsList
     *
     * @return array
     */
    public function getKitsList(): array
    {
        $kits['stoneAge'] = [Item::get(Item::STONE_SWORD, 0, 1), Item::get(Item::CHAINMAIL_CHESTPLATE, 0, 1), Item::get(Item::STONE, 0, 32)];
        $kits['stoneKid'] = [Item::get(Item::STONE_SWORD, 0, 1), Item::get(Item::CHAINMAIL_LEGGINGS, 0, 1), Item::get(Item::COBBLESTONE, 0, 32)];
        $kits['stoneMiner'] = [Item::get(Item::STONE_PICKAXE, 0, 1), Item::get(Item::CHAINMAIL_HELMET, 0, 1), Item::get(Item::CHAINMAIL_BOOTS, 0, 1)];
        $kits['stoneLumberjack'] = [Item::get(Item::STONE_AXE, 0, 1), Item::get(Item::CHAINMAIL_CHESTPLATE, 0, 1), Item::get(Item::LOG, 0, 16)];
        $kits['oogaChaka'] = [Item::get(Item::WOODEN_SWORD, 0, 1), Item::get(Item::LEATHER_CHESTPLATE, 0, 1), Item::get(Item::LEATHER_LEGGINGS, 0, 1), Item::get(Item::LOG, 0, 32)];
        $kits['tank'] = [Item::get(Item::WOODEN_SWORD, 0, 1), Item::get(Item::CHAINMAIL_HELMET, 0, 1), Item::get(Item::LEATHER_CHESTPLATE, 0, 1), Item::get(Item::LEATHER_LEGGINGS, 0, 1)];
        $kits['lavaBoy'] = [Item::get(Item::BUCKET, 10, 2)];
        $kits['waterGirl'] = [Item::get(Item::BUCKET, 8, 2)];
        $kits['snowBaller'] = [Item::get(Item::SNOWBALL, 0, 16)];
        $kits['vegetarian'] = [Item::get(Item::CARROT, 0, 16), Item::get(Item::POTATO, 0, 16)];
        $kits['nonVegetarian'] = [Item::get(Item::STEAK, 0, 8), Item::get(Item::COOKED_MUTTON, 0, 8)];
        $kits['superFeet'] = [Item::get(Item::IRON_BOOTS, 0, 1)];
        $kits['miner'] = [Item::get(Item::IRON_PICKAXE, 0, 1)];
        $kits['warrior'] = [Item::get(Item::IRON_SWORD, 0, 1)];
        $kits['doctor'] = [Item::get(Item::APPLE, 0, 32)];
        $kits['daBaby'] = [Item::get(Item::GOLD_INGOT, 0, 10)->setCustomName('i will turn into a convertible')];
        $kits['miniTank'] = [Item::get(Item::LEATHER_HELMET, 0, 1), Item::get(Item::LEATHER_CHESTPLATE, 0, 1), Item::get(Item::LEATHER_LEGGINGS, 0, 1), Item::get(Item::LEATHER_BOOTS, 0, 1)];
        $kits['skeleton'] = [Item::get(Item::BOW, 0, 1), Item::get(Item::ARROW, 0, 32), Item::get(Item::BONE, 0, 2)];
        
        return $kits;
    }
    
    /**
     * countKits
     *
     * @return int
     */
    public function countKits(): int
    {
        return count($this->getKitsList());
    }
}