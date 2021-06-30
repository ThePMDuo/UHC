<?php
declare(strict_types=1);

namespace AGTHARN\uhc\kits;

use pocketmine\Player;

use AGTHARN\uhc\kits\type\Kits;

class KitsManager
{   
    /**
     * giveKit
     *
     * @param  Player $player
     * @return string
     */
    public function giveKit(Player $player): string
    {   
        $array = $this->getKits()->getKitsList();
        $kit = $array[array_rand(array_flip($array))];

        $this->deliverKit($player, $kit);
    }

    /**
     * deliverKit
     *
     * @param  Player $player
     * @param  array  $kit
     * @return void
     */
    public function deliverKit(Player $player, array $kit): void
    {
        foreach ($kit as $item) {
            $player->getInventory()->addItem($item);
        }
    }
    
    /**
     * getKits
     *
     * @return Kits
     */
    public function getKits(): Kits
    {
        return new Kits();
    }
}