<?php
declare(strict_types=1);

namespace AGTHARN\uhc\listener\type;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\Listener;
use pocketmine\level\Position;
use pocketmine\Player;

use AGTHARN\uhc\event\phase\PhaseChangeEvent;
use AGTHARN\uhc\session\SessionManager;
use AGTHARN\uhc\game\GameProperties;
use AGTHARN\uhc\game\GameManager;
use AGTHARN\uhc\Main;

class EntityListener implements Listener
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
     * handleDamage
     *
     * @param  EntityDamageEvent $event
     * @return void
     */
    public function handleDamage(EntityDamageEvent $event): void
    {
        $cause = $event->getEntity()->getLastDamageCause();
        $entity = $event->getEntity();
        
        if ($event->getCause() === EntityDamageEvent::CAUSE_MAGIC) return;
        if (!$this->gameManager->hasStarted()) {
            $event->setCancelled();
            return;
        }
        switch ($this->gameManager->getPhase()) {
            case PhaseChangeEvent::GRACE:
                if ($entity instanceof Player) {
                    if ($event->getCause() === EntityDamageEvent::CAUSE_FALL) {
                        if ($this->gameManager->getGraceTimer() >= 1180) { // for elytra
                            $event->setCancelled();
                        }
                    } else {
                        $event->setCancelled();
                    }
                }
                break;
            case PhaseChangeEvent::DEATHMATCH:
                if ($this->gameManager->getDeathmatchTimer() >= 890) {
                    $event->setCancelled();
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
                            $event->setCancelled();
                        }
                    }
                }
                break;
        }
    }
    
    /**public function handleEntityRegain(EntityRegainHealthEvent $event): void
    {
        switch ($event->getRegainReason()) {
            case EntityRegainHealthEvent::CAUSE_SATURATION:
            case EntityRegainHealthEvent::CAUSE_EATING:
                $entity = $event->getEntity();
                if ($entity instanceof Player) {
                    if (!in_array($entity->getName(), $this->plugin->entityRegainNote)) {
                        $entity->sendMessage(GameProperties::PREFIX_COSMIC . '§bNOTE: §rYou may be healing from Saturation but this is a client-side bug and the regenerated health is fake.');
                        $this->plugin->entityRegainNote[] = $entity->getName();
                    }
                    $event->setCancelled();
                }
                break;
        }
    }*/
    
    /**
     * handlePlayerLevelChange
     *
     * @param  EntityLevelChangeEvent $event
     * @return void
     */
    public function handlePlayerLevelChange(EntityLevelChangeEvent $event): void
    {
        $entity = $event->getEntity();
        if ($entity instanceof Player) {
            $level = $this->plugin->getServer()->getLevelByName($this->gameProperties->map);
            if ($event->getTarget() !== $level) {
                $entity->teleport(new Position($this->gameProperties->spawnPosX, $this->gameProperties->spawnPosY, $this->gameProperties->spawnPosZ, $level));
            }
        }
    }
}
