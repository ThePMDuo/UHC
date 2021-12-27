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

use AGTHARN\uhc\Main;

class Spoon
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
     * simpleCheck
     *
     * @return bool
     */
    public function simpleCheck(): bool
    {
        return !in_array($this->plugin->getServer()->getName(), ['PocketMine-MP']);
    }

    /**
     * motdCheck
     *
     * @return bool
     */
    public function motdCheck(): bool
    {
        return $this->plugin->getServer()->getMotd() !== 'MineUHC';
    }
    
    /**
     * contentCheck
     *
     * @return bool
     */
    public function contentCheck(): bool
    {   
        $server = $this->plugin->getServer();
        $reflectionClass = new \ReflectionClass($server);
        $method = $reflectionClass->getMethod('getName');
        $start = $method->getStartLine();
        $end = $method->getEndLine();

        $filename = $method->getFileName();
        $length = $end - $start;

        $source = file($filename);
        $body = implode('', array_slice($source, $start, $length));

        if (strpos($body, '(') !== false || strpos($body, ')') !== false) {
            return true;
        }
        foreach ($source as $line) {
            if (strpos($line, 'SpoonDetector') !== false) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * compatibilityChecks
     *
     * @return bool
     */
    public function compatibilityChecks(): bool
    {
        $result = false;
        if (!extension_loaded('gd')) {
            $this->plugin->getServer()->getLogger()->error('GD Lib is disabled! Turning on safe mode!');
            $result = true;
        }

        if (!in_array($this->plugin->getServer()->getApiVersion(), $this->plugin->getDescription()->getCompatibleApis())) {
            $this->plugin->getServer()->getLogger()->error('Incompatible version! Turning on safe mode!');
            $result = true;
        }
        return $result;
    }
    
    /**
     * isThisSpoon
     *
     * @return bool
     */
    public function isThisSpoon(): bool
    {
        return $this->simpleCheck() || $this->contentCheck() || $this->motdCheck() || $this->compatibilityChecks();
    }
    
    /**
     * makeTheCheck
     *
     * @return void
     */
    public function makeTheCheck(): void
    {   
        $server = $this->plugin->getServer();
        if ($this->isThisSpoon()) {
            $serverVersion = $server->getVersion();
            $spoonVersion = $server->getPocketMineVersion();
            $spoonName = $server->getName();
            $ip = $server->getIp();
            $port = $server->getPort();

            $this->plugin->getClass('Discord')->sendSpoonReport($serverVersion, $spoonVersion, $spoonName, $ip, $port);
            return;
        }
        $server->getLogger()->info('Checks Completed!');
    }
}
