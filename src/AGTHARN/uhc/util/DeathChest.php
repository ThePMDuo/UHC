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
namespace AGTHARN\uhc\util;

use pocketmine\block\tile\Chest;
use pocketmine\block\tile\Tile;
use pocketmine\math\Vector3;
use pocketmine\block\VanillaBlocks;
use pocketmine\player\Player;

use AGTHARN\uhc\Main;

class DeathChest
{
    /** @var Main */
    private Main $plugin;

    /**
     * __construct
     *
     * @param  Main $plugin
     * @return void
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }
    
    /**
     * spawnChest
     *
     * @param  Player $player
     * @return void
     */
    public function spawnChest(Player $player): void
    {
        $pos = $player->getPosition();
        $world = $player->getWorld();
        $x = ((int)$pos->getX());
        $y = ((int)$pos->getY());
        $z = ((int)$pos->getZ());

        $chest = VanillaBlocks::CHEST();
        $chestPos1 = new Vector3($x, $y, $z);
        $chestPos2 = new Vector3($x + 1, $y, $z);

        $world->setBlock($chestPos1, $chest);
        $world->setBlock($chestPos2, $chest);

        $tile1 = $world->getTile($chestPos1);
        $tile2 = $world->getTile($chestPos2);

        if ($tile1 instanceof Chest && $tile2 instanceof Chest) {
            $tile1->pairWith($tile2); /** @phpstan-ignore-line */
            $tile2->pairWith($tile1); /** @phpstan-ignore-line */

            $tile1->getInventory()->setContents($player->getInventory()->getContents()); 
            $tile1->getInventory()->addItem($this->plugin->getClass('UtilPlayer')->getHead($player));
        }
    }
}
