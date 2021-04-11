<?php
declare(strict_types=1);

namespace AGTHARN\uhc\util;

use AGTHARN\uhc\Main;

class ConfigUpdater
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
     * updateConfigs
     *
     * @return void
     */
    public function updateConfigs(): void
    {   
        $this->updateMocking();
    }
    
    /**
     * updateMocking
     *
     * @return void
     */
    public function updateMocking(): void
    {
        $mockingBird = getcwd() . "\n" . "/configs/Mockingbird";
        $mockingConfig = $mockingBird . "/config.yml";
        $mockingActual = $this->plugin->getServer()->getDataPath() . "plugin_data/Mockingbird";

        if (is_dir($mockingBird)) {
            if (is_file($mockingBird . "/config.yml")) {
                @unlink($mockingActual . "/config.yml");
            }
            @mkdir($mockingActual);
            @copy($mockingConfig, $mockingActual . "/config.yml");
        }
    }
}
