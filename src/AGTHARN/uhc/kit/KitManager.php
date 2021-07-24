<?php
declare(strict_types=1);

namespace AGTHARN\uhc\kit;

use pocketmine\Player;

use AGTHARN\uhc\kit\type\Kit;

class KitManager
{   
    /**
     * giveKit
     *
     * @param  Player $player
     * @return string
     */
    public function giveKit(Player $player): string
    {   
        $array = $this->getKit()->getKitsList();
        $rand = array_rand($array);
        $kit = $array[$rand];

        $this->deliverKit($player, $kit);
        return (string)$rand;
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
     * getKit
     *
     * @return Kit
     */
    public function getKit(): Kit
    {
        return new Kit();
    }
}