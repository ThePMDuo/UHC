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
namespace AGTHARN\uhc\task;

use pocketmine\utils\TextFormat as C;
use pocketmine\scheduler\Task;

use AGTHARN\uhc\session\SessionManager;
use AGTHARN\uhc\game\GameProperties;
use AGTHARN\uhc\Main;

class UpdateCheck extends Task
{   
    /** @var Main */
    private Main $plugin;

    /** @var GameProperties */
    private GameProperties $gameProperties;
    /** @var SessionManager */
    private SessionManager $sessionManager;
        
    /**
     * __construct
     *
     * @param  Main $plugin
     * @return void
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;

        $this->sessionManager = $plugin->getClass('SessionManager');
        $this->gameProperties = $plugin->getClass('GameProperties');
    }
    
    /**
     * onRun
     * 
     * Runs every 300 seconds.
     *
     * @return void
     */
    public function onRun(): void
    {   
        if ($this->plugin->updateCheck(true)) {
            foreach ($this->sessionManager->getPlaying() as $playerSession) {
                $playerSession->getPlayer()->sendMessage(GameProperties::PREFIX_COSMIC . 'Detected a new update! Server will reboot for an update from (version) to (version) after this round!');
            }
            $this->gameProperties->hasUpdate = true;
        }
    }
}
