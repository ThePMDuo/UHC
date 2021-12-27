<?php
declare(strict_types=1);

/**
 * ███╗░░░███╗██╗███╗░░██╗███████╗██╗░░░██╗██╗░░██╗░█████╗░
 * ████╗░████║██║████╗░██║██╔════╝██║░░░██║██║░░██║██╔══██╗
 * ██╔████╔██║██║██╔██╗██║█████╗░░██║░░░██║███████║██║░░╚═╝
 * ██║╚██╔╝██║██║██║╚████║██╔══╝░░██║░░░██║██╔══██║██║░░██╗
 * ██║░╚═╝░██║██║██║░╚███║███████╗╚██████╔╝██║░░██║╚█████╔╝
 * ╚═╝░░░░░╚═╝╚═╝╚═╝░░╚══╝╚══════╝░╚═════╝░╚═╝░░╚═╝░╚════╝░
 * 
 * Copyright (C) 2020-2021 AGTHARN
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace AGTHARN\uhc\util\kit;

use pocketmine\player\Player;

use AGTHARN\uhc\util\kit\type\Kit;

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