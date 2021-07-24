<?php
declare(strict_types=1);

namespace AGTHARN\uhc\util;

use pocketmine\level\generator\GeneratorManager;

use AGTHARN\uhc\game\GameProperties;
use AGTHARN\uhc\Main;

class Generators
{
    /** @var Main */
    private $plugin;

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

        $this->gameProperties = $plugin->getClass('GameProperties');
    }

    /**
     * prepareWorld
     *
     * @return void
     */
    public function prepareWorld(): void
    {   
        $uhcName = $this->gameProperties->map;
        $uhcLevel = $this->plugin->getServer()->getLevelByName($uhcName);

        $worldAPI = $this->plugin->getServer()->getPluginManager()->getPlugin('UHCGenerator')->getWorldManagementAPI(); /** @phpstan-ignore-line */

        if ($worldAPI->isLevelGenerated($uhcName)) {
            $worldLevel = $this->plugin->getServer()->getLevelByName('world');
            $this->plugin->getServer()->setDefaultLevel($worldLevel);

            if ($worldAPI->isLevelLoaded($uhcName)) {  
                $worldAPI->unloadLevel($uhcLevel);
            }
            $worldAPI->removeLevel($uhcName);
            $this->prepareWorld();
        } else {
            $this->plugin->normalSeed = $this->generateRandomSeed();

            if ($this->plugin->normalSeed === 0) {
                $this->plugin->normalSeed = $this->generateRandomSeed();
            }
            $worldAPI->generateLevel($uhcName, $this->plugin->normalSeed, 1);  
            $worldAPI->loadLevel($uhcName);

            $uhcLevel = $this->plugin->getServer()->getLevelByName($uhcName); // redefine so its not null
            $uhcLevel->setAutoSave(false);
            $this->plugin->getServer()->setDefaultLevel($uhcLevel);
        }
    }

    /**
     * removeWorld
     * 
     * currently unused
     *
     * @return void
     */
    public function removeAllWorlds(): void
    {   
        $worldAPI = $this->plugin->getServer()->getPluginManager()->getPlugin('UHCGenerator')->getWorldManagementAPI(); /** @phpstan-ignore-line */

        foreach ($worldAPI->getAllLevels() as $levelName) {
            $level = $this->plugin->getServer()->getLevelByName($levelName);
            
            if ($worldAPI->isLevelGenerated($levelName)) {
                if ($level !== $this->plugin->getServer()->getDefaultLevel()) {
                    if ($worldAPI->isLevelLoaded($levelName)) {  
                        $worldAPI->unloadLevel($level);
                    }
                    $worldAPI->removeLevel($levelName);
                }
            }
        }
        
    }
    
    /**
     * prepareNether
     *
     * @return void
     */
    public function prepareNether(): void
    {
        $netherName = $this->gameProperties->nether;
        $netherLevel = $this->plugin->getServer()->getLevelByName($netherName);

        $worldAPI = $this->plugin->getServer()->getPluginManager()->getPlugin('UHCGenerator')->getWorldManagementAPI(); /** @phpstan-ignore-line */

        if ($worldAPI->isLevelGenerated($netherName)) {
            if ($worldAPI->isLevelLoaded($netherName)) {  
                $worldAPI->unloadLevel($netherLevel);
            }
            $worldAPI->removeLevel($netherName);
            $this->prepareNether();
        } else {
            $this->plugin->netherSeed = $this->generateRandomSeed();

            if ($this->plugin->netherSeed === 0) {
                $this->plugin->netherSeed = $this->generateRandomSeed();
            }
            $this->plugin->getServer()->generateLevel($netherName, $this->plugin->netherSeed, GeneratorManager::getGenerator('nether'));
            $worldAPI->loadLevel($netherName);

            $netherLevel = $this->plugin->getServer()->getLevelByName($netherName); // redefine so its not null
            $netherLevel->setAutoSave(false);
        }
    }
    
    /**
     * generateRandomSeed
     *
     * @return int
     */
    public function generateRandomSeed(): int
    {
        return intval(rand(0, intval(time() / memory_get_usage(true) * (int) str_shuffle('127469453645108') / (int) str_shuffle('12746945364'))));
    }
}
