<?php
declare(strict_types=1); 

/* 
 * ____    ____ .______   .__   __. .______   .______        ______   .___________. _______   ______ .___________.
 * \   \  /   / |   _  \  |  \ |  | |   _  \  |   _  \      /  __  \  |           ||   ____| /      ||           |
 *  \   \/   /  |  |_)  | |   \|  | |  |_)  | |  |_)  |    |  |  |  | `---|  |----`|  |__   |  ,----'`---|  |----`
 *   \      /   |   ___/  |  . `  | |   ___/  |      /     |  |  |  |     |  |     |   __|  |  |         |  |     
 *    \    /    |  |      |  |\   | |  |      |  |\  \----.|  `--'  |     |  |     |  |____ |  `----.    |  |     
 *     \__/     | _|      |__| \__| | _|      | _| `._____| \______/      |__|     |_______| \______|    |__|     
 *                                                                                                             
 * VPNProtect, is an advanced AntiVPN plugin for PMMP.
 * Copyright (C) 2021 AGTHARN
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace AGTHARN\uhc\task;

use pocketmine\scheduler\AsyncTask;
use pocketmine\utils\TextFormat as C;
use pocketmine\Server;

use AGTHARN\uhc\util\AntiVPN;

class VPNAsyncCheck extends AsyncTask
{   
    /** @var AntiVPN */
    private $api;

    /** @var string */
    private $playerIP;
    /** @var string */
    private $playerName;

    /** @var array|string */
    private $configs;
        
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
    public function onRun()
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
     * @param  Server $server
     * @return void
     */
    public function onCompletion(Server $server)
    {   
        $result = $this->getResult();
        
        $name = $this->playerName ?? 'null';
        $player = $server->getPlayerExact($name) ?? null;

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
