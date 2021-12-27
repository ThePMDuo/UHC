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
namespace AGTHARN\uhc\util\form\type;

use pocketmine\player\Player;

use AGTHARN\uhc\Main;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;

class CapeForm
{    
    /** @var Main */
    private Main $plugin;

    /** @var array */
    public array $capesButtons = [];

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
     * sendCapesMenuForm
     *
     * @param  Player $player
     * @return void
     */
    public function sendCapesMenuForm(Player $player): void
    {
        $form = new SimpleForm(function (Player $player, $data) {
            if ($data !== null) {
                switch ($data) {
                    case 0:
                        $this->sendSearchCapesForm($player, $this->plugin->getClass('GameProperties')->allCapes);
                        break;
                    case 1:
                        $this->sendOfficialCapesForm($player);
                        break;
                    case 2:
                        $this->sendAllCapesForm($player, $this->plugin->getClass('GameProperties')->allCapes);
                        break;
                    case 3:
                        // exit button
                        break;
                }
            }
        }); 

        $form->setTitle('§l§7< §6MineUHC Capes §7>');
        $form->setContent("Choose a cape of your own choice! All capes are unlocked for free!\n\nCapes are cosmetic and do not include gameplay features.");
        $form->addButton('Search Capes');
        $form->addButton('Official Capes');
        $form->addButton('All Capes');
        $form->addButton('§l§cExit');
        $form->sendToPlayer($player);
    }

    /**
     * sendSearchCapesForm
     *
     * @param  Player $player
     * @param  array $allCapes
     * @return void
     */
    public function sendSearchCapesForm(Player $player, array $allCapes): void
    {   
        $form = new CustomForm(function (Player $player, $data) use ($allCapes) {
            if ($data !== null) {
                $result = $data[0];
                $capesFound = [];

                if ($result === '') {
                    $this->sendAllCapesForm($player, $allCapes, $result);
                    return;
                }
                foreach ($allCapes as $capeDir) {
                    if (strpos($capeDir, $result) !== false) {
                        $capesFound[] = basename($capeDir);
                    }
                }
                $this->sendAllCapesForm($player, $capesFound, $result);
            }
        });
        
        $form->setTitle('§l§7< §6MineUHC Capes §7>');
        $form->addInput('Search Cape:', '');
        $form->sendToPlayer($player);
    }

    /**
     * sendOfficialCapesForm
     *
     * @param  Player $player
     * @return void
     */
    public function sendOfficialCapesForm(Player $player): void
    {
        $form = new SimpleForm(function (Player $player, $data) {
            if ($data !== null) {
                switch ($data) {
                    case 0:
                        // golden apple cape
                        $this->plugin->getClass('Capes')->giveCape($player, 'normal_cape.png');
                        break;
                    case 1:
                        // potion cape
                        $this->plugin->getClass('Capes')->giveCape($player, 'potion_cape.png');
                        break;
                    case 2:
                        // exit button
                        break;
                }
            }
        }); 

        $form->setTitle('§l§7< §6MineUHC Capes §7>');
        $form->setContent("Choose a cape of your own choice! All capes are unlocked for free!\n\nCapes are cosmetic and do not include gameplay features.");
        $form->addButton('§l§aGolden Apple Cape');
        $form->addButton('§l§aPotion Cape');
        $form->addButton('§l§cExit');
        $form->sendToPlayer($player);
    }
    
    /**
     * sendAllCapesForm
     *
     * @param  Player $player
     * @param  array $currentCapes
     * @param  string $searched
     * @param  int $page
     * @return void
     */
    public function sendAllCapesForm(Player $player, array $currentCapes, string $searched = '', int $page = 0): void
    {
        // sort array
        $currentCapes = array_slice(call_user_func(function(array $a){asort($a);return $a;}, $currentCapes), $page * 5);

        $form = new SimpleForm(function (Player $player, $data) use ($currentCapes, $page) {
            if ($data !== null) {
                switch ($data) {
                    case 0:
                    case 1:
                    case 2:
                    case 3:
                    case 4:
                        // sendCape
                        $chosenCapeFormatted = $this->capesButtons[$player->getName()][$data] ?? null;

                        if ($chosenCapeFormatted === null) return;
                        $this->plugin->getClass('Capes')->giveCape($player, $chosenCapeFormatted);
                        break;
                    case 5:
                        // next page button
                        $this->sendAllCapesForm($player, $currentCapes, '', $page + 1);
                        break;
                    case 6:
                        // exit button
                        break;
                }
            }
        }); 

        $form->setTitle('§l§7< §6MineUHC Capes §7>');
        if ($searched === '') {
            $form->setContent("Choose a cape of your own choice! All capes are unlocked for free!\n\nCapes are cosmetic and do not include gameplay features.");
        } else {
            $form->setContent("Choose a cape of your own choice! All capes are unlocked for free!\n\nCapes are cosmetic and do not include gameplay features.\n\nCape Searched: " . $searched);
        }
        
        $capesDir = $this->plugin->getServer()->getDataPath() . 'plugins/UHC/resources/capes';
        $this->capesButtons[$player->getName()] = [];
        $i = 0;
        foreach ($currentCapes as $capeDir) {
            if ($i >= 5) {
                $form->addButton('Next Page (' . ($page + 1) . ')');
                break;
            }
            if ($i <= 5) {
                $capeName = basename($capeDir, '.png');
                $capeNameFormatted = basename($capeDir);

                $form->addButton($capeName);
                $this->capesButtons[$player->getName()][$i] = $capeNameFormatted;
                $i++;
            }
        }

        $form->addButton('§l§cExit');
        $form->sendToPlayer($player);
    }
}
