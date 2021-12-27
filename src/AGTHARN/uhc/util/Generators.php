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

use pocketmine\world\generator\GeneratorManager;
use pocketmine\world\WorldCreationOptions;
use pocketmine\math\Vector3;

use AGTHARN\uhc\game\GameProperties;
use AGTHARN\uhc\Main;

class Generators
{
    /** @var Main */
    private Main $plugin;

    /** @var GameProperties */
    private GameProperties $gameProperties;

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
        $server = $this->plugin->getServer();
        $worldManager = $server->getWorldManager();

        $uhcName = $this->gameProperties->map;
        $uhcWorld = $worldManager->getWorldByName($uhcName);

        if ($worldManager->isWorldGenerated($uhcName)) {
            $worldWorld = $worldManager->getWorldByName('world');
            $worldManager->setDefaultWorld($worldWorld);

            if ($worldManager->isWorldLoaded($uhcName)) {  
                $worldManager->unloadWorld($uhcWorld);
            }
            $this->removeWorld($uhcName);
            $this->prepareWorld();
        } else {
            $this->gameProperties->normalSeed = $this->generateRandomSeed();

            if ($this->gameProperties->normalSeed === 0) {
                $this->gameProperties->normalSeed = $this->generateRandomSeed();
            }
            $worldManager->generateWorld($uhcName, WorldCreationOptions::create()->setSeed($this->gameProperties->normalSeed)->setGeneratorClass(GeneratorManager::getInstance()->getGenerator('custom')));  
            $worldManager->loadWorld($uhcName);

            $uhcWorld = $worldManager->getWorldByName($uhcName); // redefine so its not null
            $worldManager->setDefaultWorld($uhcWorld);
            $uhcWorld->setSpawnLocation(new Vector3($this->gameProperties->spawnPosX, $this->gameProperties->spawnPosY, $this->gameProperties->spawnPosZ));
            $uhcWorld->setAutoSave(false);
        }
    }

    /**
     * removeAllWorlds
     * 
     * currently unused
     *
     * @return void
     */
    public function removeAllWorlds(): void
    {   
        $server = $this->plugin->getServer();
        $worldManager = $server->getWorldManager();

        foreach ($worldManager->getWorlds() as $world) {
            $worldName = $world->getFolderName();
            
            if ($worldManager->isWorldGenerated($worldName)) {
                if ($world !== $worldManager->getDefaultWorld()) {
                    if ($worldManager->isWorldLoaded($worldName)) {  
                        $worldManager->unloadWorld($world);
                    }
                    $this->removeWorld($worldName);
                }
            }
        }
        
    }
    
    /**
     * removeWorld
     *
     * @param  string $name
     * @return int
     */
    public function removeWorld(string $name): int 
    {
        $server = $this->plugin->getServer();
        $worldManager = $server->getWorldManager();

        if ($worldManager->isWorldLoaded($name)) {
            $world = $worldManager->getWorldByName($name);

            if (count($world->getPlayers()) > 0) {
                foreach ($world->getPlayers() as $player) {
                    $player->teleport($worldManager->getDefaultWorld()->getSpawnLocation());
                }
            }
            $worldManager->unloadWorld($world);
        }
        return $this->plugin->getClass('Directory')->removeDir($server->getDataPath() . '/worlds/' . $name);
    }
    
    /**
     * prepareNether
     *
     * @return void
     */
    public function prepareNether(): void
    {
        $server = $this->plugin->getServer();
        $worldManager = $server->getWorldManager();
        
        $netherName = $this->gameProperties->nether;
        $netherWorld = $worldManager->getWorldByName($netherName);

        if ($worldManager->isWorldGenerated($netherName)) {
            if ($worldManager->isWorldLoaded($netherName)) {  
                $worldManager->unloadWorld($netherWorld);
            }
            $this->removeWorld($netherName);
            $this->prepareNether();
        } else {
            $this->gameProperties->netherSeed = $this->generateRandomSeed();

            if ($this->gameProperties->netherSeed === 0) {
                $this->gameProperties->netherSeed = $this->generateRandomSeed();
            }
            $worldManager->generateWorld($netherName, WorldCreationOptions::create()->setSeed($this->gameProperties->netherSeed)->setGeneratorClass(GeneratorManager::getInstance()->getGenerator('nether')));
            $worldManager->loadWorld($netherName);

            $netherWorld = $worldManager->getWorldByName($netherName); // redefine so its not null
            $netherWorld->setAutoSave(false);
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
