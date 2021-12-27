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
namespace AGTHARN\uhc\util\kit\type;

use pocketmine\item\VanillaItems;

class Kit
{    
    
    /**
     * getKitsList
     *
     * @return array
     */
    public function getKitsList(): array
    {
        $kits['stoneAge'] = [VanillaItems::STONE_SWORD(), VanillaItems::CHAINMAIL_CHESTPLATE(), VanillaItems::STONE()->setCount(32)];
        $kits['stoneKid'] = [VanillaItems::STONE_SWORD(), VanillaItems::CHAINMAIL_LEGGINGS(), VanillaItems::COBBLESTONE()->setCount(32)];
        $kits['stoneMiner'] = [VanillaItems::STONE_PICKAXE(), VanillaItems::CHAINMAIL_HELMET(), VanillaItems::CHAINMAIL_BOOTS()];
        $kits['stoneLumberjack'] = [VanillaItems::STONE_AXE(), VanillaItems::CHAINMAIL_CHESTPLATE(), VanillaItems::LOG()->setCount(16)];
        $kits['oogaChaka'] = [VanillaItems::WOODEN_SWORD(), VanillaItems::LEATHER_CHESTPLATE(), VanillaItems::LEATHER_LEGGINGS(), VanillaItems::LOG()->setCount(32)];
        $kits['tank'] = [VanillaItems::WOODEN_SWORD(), VanillaItems::CHAINMAIL_HELMET(), VanillaItems::LEATHER_CHESTPLATE(), VanillaItems::LEATHER_LEGGINGS()];
        $kits['lavaBoy'] = [VanillaItems::LAVA_BUCKET()->setCount(2)];
        $kits['waterGirl'] = [VanillaItems::WATER_BUCKET()->setCount(2)];
        $kits['snowBaller'] = [VanillaItems::SNOWBALL()->setCount(16)];
        $kits['vegetarian'] = [VanillaItems::CARROT()->setCount(16), VanillaItems::POTATO()->setCount(16)];
        $kits['nonVegetarian'] = [VanillaItems::STEAK()->setCount(8), VanillaItems::COOKED_MUTTON()->setCount(8)];
        $kits['superFeet'] = [VanillaItems::IRON_BOOTS()];
        $kits['miner'] = [VanillaItems::IRON_PICKAXE()];
        $kits['warrior'] = [VanillaItems::IRON_SWORD()];
        $kits['doctor'] = [VanillaItems::APPLE()->setCount(16)];
        $kits['daBaby'] = [VanillaItems::GOLD_INGOT()->setCount(10)->setCustomName('i will turn into a convertible')];
        $kits['miniTank'] = [VanillaItems::LEATHER_HELMET(), VanillaItems::LEATHER_CHESTPLATE(), VanillaItems::LEATHER_LEGGINGS(), VanillaItems::LEATHER_BOOTS()];
        $kits['skeleton'] = [VanillaItems::BOW(), VanillaItems::ARROW()->setCount(32), VanillaItems::BONE()->setCount(2)->setCustomName('i had to kill a skeleton to get this uk')];
        
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