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
namespace AGTHARN\uhc\listener\type;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;

use AGTHARN\uhc\session\SessionManager;
use AGTHARN\uhc\game\GameProperties;
use AGTHARN\uhc\game\GameManager;
use AGTHARN\uhc\Main;

class BlockListener implements Listener
{
    /** @var Main */
    private Main $plugin;
    
    /** @var GameManager */
    private GameManager $gameManager;
    /** @var SessionManager */
    private SessionManager $sessionManager;
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

        $this->gameManager = $plugin->getClass('GameManager');
        $this->sessionManager = $plugin->getClass('SessionManager');
        $this->gameProperties = $plugin->getClass('GameProperties');
    }
    
    /**
     * handleBreak
     *
     * @param  BlockBreakEvent $event
     * @return void
     */
    public function handleBreak(BlockBreakEvent $event): void
    {
        if (!$this->gameManager->hasStarted()) {
            $event->cancel();
            return;
        }
        switch ($event->getBlock()) { // drops
            case VanillaBlocks::LEAVES():
            case VanillaBlocks::LEAVES2():
                $rand = mt_rand(0, 100);
                if ($event->getItem()->equals(VanillaItems::SHEARS(), false, false)) {
                    if ($rand <= 6) {
                        $event->setDrops([VanillaItems::APPLE()]);
                    }
                } elseif ($rand <= 3) {
                    $event->setDrops([VanillaItems::APPLE()]);
                }
                break;
            case VanillaBlocks::LOG():
            case VanillaBlocks::LOG2():
                $drops[] = VanillaItems::PLANKS()->setCount(4);
                $event->setDrops($drops);
                break;
            case VanillaBlocks::IRON_ORE():
                $drops[] = VanillaItems::IRON_INGOT()->setCount(mt_rand(1, 2));
                $event->setDrops($drops);
                break;
            case VanillaBlocks::GOLD_ORE():
                $drops[] = VanillaItems::GOLD_INGOT()->setCount(mt_rand(2, 4));
                $event->setDrops($drops);
                break;
            case VanillaBlocks::DIAMOND_ORE():
                $drops[] = VanillaItems::DIAMOND()->setCount(mt_rand(1, 2));
                $event->setDrops($drops);
                break;
        }
        if ($this->plugin->getClass('UtilPlayer')->playerTreeChop($event->getPlayer(), $event->getBlock(), $event->getItem())) {
            $event->cancel();
        }
    }
    
    /**
     * handlePlace
     *
     * @param  BlockPlaceEvent $event
     * @return void
     */
    public function handlePlace(BlockPlaceEvent $event): void
    {   
        if (!$this->gameManager->hasStarted()) {
            $event->cancel();
        }
    }
}
