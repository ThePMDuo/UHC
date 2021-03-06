<?php

declare(strict_types=1);

namespace AGTHARN\uhc\game\scenario\type;

use pocketmine\event\HandlerListManager;
use pocketmine\event\Listener;

use AGTHARN\uhc\Main;

class Scenario implements Listener
{
    /** @var Main */
    protected $plugin;

    /** @var string */
    private $name;
    /** @var bool */
    private $activeScenario = false;
    
    /**
     * __construct
     *
     * @param  Main $plugin
     * @param  string $name
     * @return void
     */
    public function __construct(Main $plugin, string $name)
    {
        $this->plugin = $plugin;
        $this->name = $name;
    }
    
    /**
     * getName
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * setActive
     *
     * @param  bool $active
     * @return void
     */
    public final function setActive(bool $active): void
    {
        $this->activeScenario = $active;
        if ($active) {
            $this->plugin->getServer()->getPluginManager()->registerEvents($this, $this->plugin);
        } else {
            HandlerListManager::global()->unregisterAll($this);
        }
    }
    
    /**
     * isActive
     *
     * @return bool
     */
    public final function isActive(): bool
    {
        return $this->activeScenario;
    }
    
    /**
     * getPlugin
     *
     * @return Main
     */
    public final function getPlugin(): Main
    {
        return $this->plugin;
    }
}