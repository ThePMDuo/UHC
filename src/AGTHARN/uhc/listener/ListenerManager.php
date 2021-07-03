<?php
declare(strict_types=1);

namespace AGTHARN\uhc\listener;

use AGTHARN\uhc\Main;

class ListenerManager
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
     * registerListeners
     *
     * @return void
     */
    public function registerListeners(): void
    {
        $this->plugin->getClass('Directory')->callDirectory("listener" . DIRECTORY_SEPARATOR . "type", function (string $namespace): void {
            if (strpos($namespace, "EventListener") !== false) {
                $class = new $namespace($this->plugin);
            } else {
                $class = new $namespace();
            }
            $this->plugin->getServer()->getPluginManager()->registerEvents($class, $this->plugin);
        });
    }
}