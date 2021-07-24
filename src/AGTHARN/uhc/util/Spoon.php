<?php
declare(strict_types=1);

namespace AGTHARN\uhc\util;

use AGTHARN\uhc\Main;

class Spoon
{    
    /** @var Main */
    private $plugin;

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
     * @return void
     */
    public function compatibilityChecks()
    {
        if (!extension_loaded('gd')) {
            $this->getServer()->getLogger()->error('GD Lib is disabled! Turning on safe mode!');
            $result = true;
        }

        if (!in_array($this->getServer()->getApiVersion(), $this->getDescription()->getCompatibleApis())) {
            $this->getServer()->getLogger()->error('Incompatible version! Turning on safe mode!');
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
