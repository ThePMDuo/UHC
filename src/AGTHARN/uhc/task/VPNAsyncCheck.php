<?php
declare(strict_types=1);

/**
 * ███╗░░░███╗██╗███╗░░██╗███████╗██╗░░░██╗██╗░░██╗░█████╗░
 * ████╗░████║██║████╗░██║██╔════╝██║░░░██║██║░░██║██╔══██╗
 * ██╔████╔██║██║██╔██╗██║█████╗░░██║░░░██║███████║██║░░╚═╝    ▀▄▀
 * ██║╚██╔╝██║██║██║╚████║██╔══╝░░██║░░░██║██╔══██║██║░░██╗    █░█
 * ██║░╚═╝░██║██║██║░╚███║███████╗╚██████╔╝██║░░██║╚█████╔╝
 * ╚═╝░░░░░╚═╝╚═╝╚═╝░░╚══╝╚══════╝░╚═════╝░╚═╝░░╚═╝░╚════╝░
 * 
 *  ____    ____ .______   .__   __. .______   .______        ______   .___________. _______   ______ .___________.
 * \   \  /   / |   _  \  |  \ |  | |   _  \  |   _  \      /  __  \  |           ||   ____| /      ||           |
 *  \   \/   /  |  |_)  | |   \|  | |  |_)  | |  |_)  |    |  |  |  | `---|  |----`|  |__   |  ,----'`---|  |----`
 *   \      /   |   ___/  |  . `  | |   ___/  |      /     |  |  |  |     |  |     |   __|  |  |         |  |     
 *    \    /    |  |      |  |\   | |  |      |  |\  \----.|  `--'  |     |  |     |  |____ |  `----.    |  |     
 *     \__/     | _|      |__| \__| | _|      | _| `._____| \______/      |__|     |_______| \______|    |__|     
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

use pocketmine\scheduler\AsyncTask;
use pocketmine\utils\TextFormat as C;
use pocketmine\Server;

use AGTHARN\uhc\util\AntiVPN;

class VPNAsyncCheck extends AsyncTask
{   
    /** @var AntiVPN */
    private AntiVPN $api;

    /** @var string */
    private string $playerIP;
    /** @var string */
    private string $playerName;

    /** @var mixed */
    private mixed $configs;
        
    /**
     * __construct
     *
     * @param  AntiVPN $api
     * @param  string $playerIP
     * @param  string $playerName
     * @param  array $configs
     * @return void
     */
    public function __construct(AntiVPN $api, string $playerIP, string $playerName, array $configs)
    {   
        $this->api = $api;

        $this->playerIP = $playerIP;
        $this->playerName = $playerName;

        $this->configs = serialize($configs);
    }
    
    /**
     * onRun
     *
     * @return void
     */
    public function onRun(): void
    {   
        if ($this->playerIP === 'error') {
            $this->setResult('error');
            return;
        }
        $result = $this->api->checkAll($this->playerIP, unserialize($this->configs));
        $this->setResult($result);
    }

    
    /**
     * onCompletion
     *
     * @return void
     */
    public function onCompletion(): void
    {   
        $result = $this->getResult();
        
        $name = $this->playerName ?? 'null';
        $player = Server::getInstance()->getPlayerExact($name) ?? null;

        $failedChecks = 0;
        $vpnResult = false;

        if ($result === 'error') {
            return;
        }
        
        foreach ($result as $key => $value) {
            if ($value === true) {
                $vpnResult = true;
                $failedChecks++;
            }
        }

        if ($player !== null && $vpnResult === true && $failedChecks >= 2) {
            $player->kick(C::colorize('&cPlease disconnect your VPN, Proxy or Mobile Data!'));
        }
    }
}
