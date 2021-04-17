<?php
declare(strict_types=1);

namespace AGTHARN\uhc\kits;

use pocketmine\item\Item;
use pocketmine\Player;

use AGTHARN\uhc\libs\JackMD\ScoreFactory\ScoreFactory;

class Kits
{    
    // Item::get(Item::item, 0, 1)
    
    /** @var array */
    private $stoneAge = [];

    /** @var array */
    private $stoneKid = [];
    
    /** @var int */
    private $kitsTotal = 2;
    
    /**
     * __construct
     *
     * @return void
     */
    public function __construct()
    {
        $this->stoneAge = [Item::get(Item::STONE_SWORD, 0, 1), Item::get(Item::CHAINMAIL_CHESTPLATE, 0, 1), Item::get(Item::STONE, 0, 32)];
        $this->stoneKid = [Item::get(Item::STONE_SWORD, 0, 1), Item::get(Item::CHAINMAIL_LEGGINGS, 0, 1), Item::get(Item::COBBLESTONE, 0, 32)];
    }

    /**
     * giveKit
     *
     * @param  Player $player
     * @return string
     */
    public function giveKit(Player $player): string
    {   
        // NOTE: no break cuz return is already the same as break
        switch (mt_rand(1, $this->kitsTotal)) {
            case 1:
                $this->giveStoneAge($player);
                return "Stone Age";
            case 2:
                $this->giveStoneKid($player);
                return "Stone Kid";
        }
    }
    
    /**
     * giveStoneAge
     *
     * @param  Player $player
     * @return void
     */
    public function giveStoneAge(Player $player): void
    {
        foreach ($this->stoneAge as $item) {
            $player->getInventory()->addItem($item);
        }
    }
    
    /**
     * giveStoneKid
     *
     * @param  Player $player
     * @return void
     */
    public function giveStoneKid(Player $player): void
    {
        foreach ($this->stoneKid as $item) {
            $player->getInventory()->addItem($item);
        }
    }
}