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
namespace AGTHARN\uhc\listener;

use AGTHARN\uhc\session\SessionManager;
use AGTHARN\uhc\game\GameProperties;
use AGTHARN\uhc\game\GameManager;
use AGTHARN\uhc\Main;

class ListenerManager
{
    /** @var Main */
    private Main $plugin;
    
    /** @var GameManager */
    private GameManager $gameManager;
    /** @var SessionManager */
    private SessionManager $sessionManager;
    /** @var GameProperties */
    private GameProperties $gameProperties;
        
    /**
     * __construct
     *
     * @param  Main $plugin
     * @return void
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;

        $this->gameManager = $plugin->getClass('GameManager');
        $this->sessionManager = $plugin->getClass('SessionManager');
        $this->gameProperties = $plugin->getClass('GameProperties');
    }

    /**
     * registerListeners
     *
     * (Code extracted from VanillaX)
     * 
     * @return void
     */
    public function registerListeners(): void
    {
        $this->plugin->getClass('Directory')->callDirectory('listener/type', true, function (string $namespace, string $directory): void {
            $class = new $namespace($this->plugin);

            $this->plugin->getServer()->getPluginManager()->registerEvents($class, $this->plugin);
        });
    }
}