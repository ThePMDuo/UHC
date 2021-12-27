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

use pocketmine\entity\Skin;
use pocketmine\player\Player;

use AGTHARN\uhc\Main;

class Capes
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
     * giveCape
     *
     * @param  Player $player
     * @param  string $cape
     * @return void
     */
    public function giveCape(Player $player, string $cape = 'normal_cape.png'): void
    {
        if (preg_match('/\.png$/', $cape)) {
            $img = imagecreatefrompng($this->plugin->getDataFolder() . 'capes/' . $cape);
        } else {
            $img = imagecreatefrompng($this->plugin->getDataFolder() . 'capes/' . $cape . '.png');
        }
        $rgba = $this->getRGBA($img);

        $this->setCape($player, $rgba);
        $this->plugin->getClass('Database')->changeCape($player, basename($cape, '.png'));
    }
    
    /**
     * setCape
     *
     * @param  Player $player
     * @param  string $cape
     * @return void
     */
    public function setCape(Player $player, string $cape): void
    {
        $oldSkin = $player->getSkin();
        $newSkin = new Skin($oldSkin->getSkinId(), $oldSkin->getSkinData(), $cape, $oldSkin->getGeometryName(), $oldSkin->getGeometryData());
        
        $player->setSkin($newSkin);
        $player->sendSkin();
    }
    
    /**
     * getRGBA
     *
     * @param  mixed $img
     * @return string
     */
    public function getRGBA($img): string
    {
        $rgba = '';

        for ($y = 0; $y < imagesy($img); $y++) {
            for ($x = 0; $x < imagesx($img); $x++) {
                $argb = imagecolorat($img, $x, $y);
                $rgba .= chr(($argb >> 16) & 0xff) . chr(($argb >> 8) & 0xff) . chr($argb & 0xff) . chr(((~((int)($argb >> 24))) << 1) & 0xff);
            }
        }
        return $rgba;
    }
}
