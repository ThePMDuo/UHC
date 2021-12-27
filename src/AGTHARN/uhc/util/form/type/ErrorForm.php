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

use jojoe77777\FormAPI\SimpleForm;

class ErrorForm
{        
    /**
     * sendErrorForm
     *
     * @param  Player $player
     * @param  string $reason
     * @return void
     */
    public function sendErrorForm(Player $player, string $reason): void
    {
        $form = new SimpleForm(function (Player $player, $data) {
            if ($data !== null) {
                switch ($data) {
                    case 0:
                        // exit button
                        break;
                }
            }
        }); 
        
        $form->setTitle('§l§7< §aFATAL ERROR! §7>');
        $form->setContent("You have encountered an error! Please send this to a staff member!\n\n" . $reason);
        $form->addButton('§l§cExit');
        $form->sendToPlayer($player);
    }
}
