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

use AGTHARN\uhc\Main;
use pocketmine\player\Player;
use pocketmine\event\Listener;
use pocketmine\world\Position;
use AGTHARN\uhc\game\GameManager;
use AGTHARN\uhc\game\GameProperties;

use AGTHARN\uhc\session\SessionManager;
use AGTHARN\uhc\event\phase\PhaseChangeEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class EntityListener implements Listener
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
     * handleDamage
     * 
     * Handles damage inflicted on an entity.
     *
     * @param  EntityDamageEvent $event
     * @return void
     */
    public function handleDamage(EntityDamageEvent $event): void
    {
        $cause = $event->getEntity()->getLastDamageCause();
        $entity = $event->getEntity();
        
        if ($event->getCause() !== EntityDamageEvent::CAUSE_MAGIC) {
            if (!$this->gameManager->hasStarted()) {
                $event->cancel();
                return;
            }
            switch ($this->gameManager->getPhase()) {
                case PhaseChangeEvent::GRACE:
                    if ($entity instanceof Player) {
                        if ($event->getCause() === EntityDamageEvent::CAUSE_FALL) {
                            if ($this->gameManager->getGraceTimer() >= 1180) { // for elytra
                                $event->cancel();
                            }
                            return;
                        }
                        $event->cancel();
                    }
                    break;
                case PhaseChangeEvent::DEATHMATCH:
                    if ($this->gameManager->getDeathmatchTimer() >= 890) {
                        $event->cancel();
                    }
                    break;
                default:
                    if ($event instanceof EntityDamageByEntityEvent) {
                        $damager = $event->getDamager();
                        $victim = $event->getEntity();
        
                        if ($damager instanceof Player && $victim instanceof Player) {
                            $damagerSession = $this->sessionManager->getSession($damager);
                            $victimSession = $this->sessionManager->getSession($victim);
                            if ($damagerSession->isInTeam() && $victimSession->isInTeam() && $damagerSession->getTeam()->memberExists($victim)) {
                                $event->cancel();
                            }
                        }
                    }
                    break;
            }
        }
    }
    
    /**
     * handlePlayerWorldChange
     * 
     * Handles world change through EntityTeleportEvent.
     *
     * @param  EntityTeleportEvent $event
     * @return void
     */
    public function handlePlayerWorldChange(EntityTeleportEvent $event): void
    {
        if ($event->getFrom()->getWorld() !== $event->getTo()->getWorld()) {
            $entity = $event->getEntity();
            if ($entity instanceof Player) {
                $world = $this->plugin->getServer()->getWorldManager()->getWorldByName($this->gameProperties->map);
                if ($event->getTo()->getWorld() !== $world) {
                    $entity->teleport(new Position($this->gameProperties->spawnPosX, $this->gameProperties->spawnPosY, $this->gameProperties->spawnPosZ, $world));
                }
            }
        }
    }
}
