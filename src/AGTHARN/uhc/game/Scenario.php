<?php
declare(strict_types=1);

namespace AGTHARN\uhc\game;

use pocketmine\event\HandlerList;
use pocketmine\event\Listener;

use AGTHARN\uhc\Loader;

class Scenario implements Listener
{
    /** @var string */
    private $name;

    /** @var Loader */
    protected $plugin;
    
    /** @var bool */
    private $activeScenario = false;
    
    /**
     * __construct
     *
     * @param  Loader $plugin
     * @param  string $name
     * @return void
     */
    public function __construct(Loader $plugin, string $name)
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
    public function setActive(bool $active): void
    {
        $this->activeScenario = $active;
        if ($active) {
            $this->plugin->getServer()->getPluginManager()->registerEvents($this, $this->plugin);
        } else {
            HandlerList::unregisterAll($this);
        }
    }
    
    /**
     * isActive
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->activeScenario;
    }
}
