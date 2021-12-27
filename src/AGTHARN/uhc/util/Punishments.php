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

use pocketmine\console\ConsoleCommandSender;
use pocketmine\player\Player;

use AGTHARN\uhc\Main;

class Punishments
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

    public function createPunishment(Player $player, string $selectedName, string $reason, string $duration, string $type): void
    {
        $server = $this->plugin->getServer();
        $playerName = $player->getName();
        switch ($type) {
            case 'CREATE_BAN':
                if ($server->dispatchCommand(new ConsoleCommandSender($server, $server->getLanguage()), 'ban "' . $selectedName . '" ' . $duration . ' ' . $reason . '[' . $playerName . ']')) {
                    $player->sendMessage('§aBan request successfully completed!');
                    return;
                }
                $player->sendMessage('§cBan request unsuccessful!');
                break;
            case 'CREATE_KICK':
                if ($server->getPlayerExact($selectedName)->kick($reason . '[' . $playerName . ']')) {
                    $player->sendMessage('§aKick request successfully completed!');
                    return;
                }
                $player->sendMessage('§cKick request unsuccessful!');
                break;
            case 'CREATE_MUTE':
                if ($server->dispatchCommand(new ConsoleCommandSender($server, $server->getLanguage()), 'mute "' . $selectedName . '" ' . $duration . ' ' . $reason . '[' . $playerName . ']')) {
                    $player->sendMessage('§aMute request successfully completed!');
                    return;
                }
                $player->sendMessage('§aMute request unsuccessful!');
                break;
            case 'CREATE_WARN':
                if ($server->dispatchCommand(new ConsoleCommandSender($server, $server->getLanguage()), 'warn "' . $selectedName . '" ' . $duration . ' ' . $reason . '[' . $playerName . ']')) {
                    $player->sendMessage('§aWarn request successfully completed!');
                    return;
                }
                $player->sendMessage('§cWarn request unsuccessful!');
                break;
            case 'CHECK_WARNS':
                 // to do
                break;
            case 'CHECK_HISTORY':
                 // to do
                break;
        }
    }
}
