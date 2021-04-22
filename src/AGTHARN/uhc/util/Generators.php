<?php
declare(strict_types=1);

namespace AGTHARN\uhc\util;

use pocketmine\level\generator\GeneratorManager;

use AGTHARN\uhc\Main;

class Generators
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
     * prepareWorld
     *
     * @return void
     */
    public function prepareWorld(): void
    {   
        $uhcName = $this->plugin->map;
        $uhcLevel = $this->plugin->getServer()->getLevelByName($uhcName);

        $worldAPI = $this->plugin->getServer()->getPluginManager()->getPlugin('MultiWorld')->getWorldManagementAPI(); /** @phpstan-ignore-line */

        if ($worldAPI->isLevelGenerated($uhcName)) {
            if ($worldAPI->isLevelLoaded($uhcName)) {  
                $worldAPI->unloadLevel($uhcLevel);
            }
            $worldAPI->removeLevel($uhcName);
            $this->prepareWorld();
        } else {
            $this->normalSeed = $this->generateRandomSeed();

            if ($this->normalSeed === 0) {
                $this->normalSeed = $this->generateRandomSeed();
            }
            $worldAPI->generateLevel($uhcName, $this->normalSeed, 1);  
            $worldAPI->loadLevel($uhcName);

            $uhcLevel = $this->plugin->getServer()->getLevelByName($uhcName); // redefine so its not null
            $uhcLevel->getGameRules()->setRuleWithMatching('domobspawning', 'true'); /** @phpstan-ignore-line */
            $uhcLevel->getGameRules()->setRuleWithMatching('showcoordinates', 'true'); /** @phpstan-ignore-line */
            $uhcLevel->getGameRules()->setRuleWithMatching('doimmediaterespawn', 'true'); /** @phpstan-ignore-line */
        }
    }
    
    /**
     * prepareNether
     *
     * @return void
     */
    public function prepareNether(): void
    {
        $netherName = $this->plugin->nether;
        $netherLevel = $this->plugin->getServer()->getLevelByName($netherName);

        $worldAPI = $this->plugin->getServer()->getPluginManager()->getPlugin('MultiWorld')->getWorldManagementAPI(); /** @phpstan-ignore-line */

        if ($worldAPI->isLevelGenerated($netherName)) {
            if ($worldAPI->isLevelLoaded($netherName)) {  
                $worldAPI->unloadLevel($netherLevel);
            }
            $worldAPI->removeLevel($netherName);
            $this->prepareNether();
        } else {
            $this->netherSeed = $this->generateRandomSeed();

            if ($this->netherSeed === 0) {
                $this->netherSeed = $this->generateRandomSeed();
            }
            $this->plugin->getServer()->generateLevel($netherName, $this->netherSeed, GeneratorManager::getGenerator('nether'));
            $worldAPI->loadLevel($netherName);

            $netherLevel = $this->plugin->getServer()->getLevelByName($netherName); // redefine so its not null
            $netherLevel->getGameRules()->setRuleWithMatching('showcoordinates', 'true'); /** @phpstan-ignore-line */
            $this->plugin->getServer()->setNetherLevel($netherLevel); /** @phpstan-ignore-line */
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
