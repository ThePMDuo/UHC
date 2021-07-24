<?php
declare(strict_types=1);

namespace AGTHARN\uhc\listener;

use AGTHARN\uhc\session\SessionManager;
use AGTHARN\uhc\game\GameProperties;
use AGTHARN\uhc\game\GameManager;
use AGTHARN\uhc\Main;

class ListenerManager
{
    /** @var Main */
    private $plugin;
    
    /** @var GameManager */
    private $gameManager;
    /** @var SessionManager */
    private $sessionManager;
    /** @var GameProperties */
    private $gameProperties;
        
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
     * @return void
     */
    public function registerListeners(): void
    {
        $this->plugin->getClass('Directory')->callDirectory("listener/type", function (string $namespace): void {
            $class = new $namespace($this->plugin);

            $this->plugin->getServer()->getPluginManager()->registerEvents($class, $this->plugin);
        });
    }
}