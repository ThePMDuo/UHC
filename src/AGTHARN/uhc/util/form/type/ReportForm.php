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

use AGTHARN\uhc\game\GameProperties;
use AGTHARN\uhc\Main;

use jojoe77777\FormAPI\CustomForm;

class ReportForm
{    
    /** @var Main */
    private Main $plugin;

    /** @var GameProperties */
    private GameProperties $gameProperties;

    /**
     * __construct
     *
     * @param  Main $plugin
     * @param  GameProperties $gameProperties
     * @return void
     */
    public function __construct(Main $plugin, GameProperties $gameProperties)
    {
        $this->plugin = $plugin;

        $this->gameProperties = $gameProperties;
    }
    
    /**
     * sendReportForm
     *
     * @param  Player $player
     * @return void
     */
    public function sendReportForm(Player $player): void
    {
        $form = new CustomForm(function (Player $player, $data) {
            if ($data !== null) {
                $t1 = $data[0];
                $reported = $this->gameProperties->allPlayersForm[$player->getName()][$t1];
                $t2 = $data[1];
                $reportType = $this->gameProperties->reportTypes[$t2];

                $reporter = $player->getName();
                $reason = $data[2];

                $this->plugin->getClass('Discord')->sendReport($reporter, $reported, $reportType, $reason);
                $player->sendMessage('§gFABIO §7»» §aThanks for the report! You can ask any staff for a follow-up if needed!');
            }
        });

        $players = [];
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $onlinePlayer) {
            $players[] = $onlinePlayer->getName();
        }
        $this->gameProperties->allPlayersForm[$player->getName()] = $players;

        $form->setTitle('§l§7< §6MineUHC Reporting §7>');
        $form->addDropdown('Select Player:', $this->gameProperties->allPlayersForm[$player->getName()]);
        $form->addDropdown('Report Type:', $this->gameProperties->reportTypes);
        $form->addInput('Reason:', 'Reason for the report');
        $form->sendToPlayer($player);
    }
}
