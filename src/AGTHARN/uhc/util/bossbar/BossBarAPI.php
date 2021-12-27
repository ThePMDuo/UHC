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
namespace AGTHARN\uhc\util\bossbar;

use pocketmine\entity\Attribute;
use pocketmine\entity\AttributeMap;
use pocketmine\entity\AttributeFactory;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\player\Player;

class BossBarAPI
{
    /** @var int */
    private int $entityId;
    /** @var AttributeMap */
    private AttributeMap $attributeMap;

    /** @var array */
    protected array $players = [];
    
    /**
     * __construct
     *
     * @return void
     */
    public function __construct()
    {
        $this->entityId = Entity::nextRuntimeId();

        $this->attributeMap = new AttributeMap();
        $this->attributeMap->add(AttributeFactory::getInstance()->mustGet(Attribute::HEALTH)
            ->setMaxValue(100.0)
            ->setMinValue(0.0)
            ->setDefaultValue(100.0)
        );

        $this->propertyManager = new EntityMetadataCollection();
        $this->propertyManager->setLong(EntityMetadataProperties::FLAGS, 0
            ^ 1 << EntityMetadataFlags::SILENT
            ^ 1 << EntityMetadataFlags::INVISIBLE
            ^ 1 << EntityMetadataFlags::NO_AI
            ^ 1 << EntityMetadataFlags::FIRE_IMMUNE
        );
        $this->propertyManager->setShort(EntityMetadataProperties::MAX_AIR, 400);
        $this->propertyManager->setString(EntityMetadataProperties::NAMETAG, '');
        $this->propertyManager->setLong(EntityMetadataProperties::LEAD_HOLDER_EID, -1);
        $this->propertyManager->setFloat(EntityMetadataProperties::SCALE, 0);
        $this->propertyManager->setFloat(EntityMetadataProperties::BOUNDING_BOX_WIDTH, 0.0);
        $this->propertyManager->setFloat(EntityMetadataProperties::BOUNDING_BOX_HEIGHT, 0.0);
    }
    
    /**
     * getAttributeMap
     *
     * @return AttributeMap
     */
    public function getAttributeMap(): AttributeMap
    {
        return $this->attributeMap;
    }
    
    /**
     * sendSpawnPacket
     *
     * @param  Player $player
     * @return void
     */
    public function sendSpawnPacket(Player $player): void
    {
        $pk = new AddActorPacket();
        $pk->entityRuntimeId = $this->entityId;
        $pk->type = 'minecraft:slime';
        $pk->attributes = $this->attributeMap->getAll();
        $pk->metadata = $this->propertyManager->getAll();
        $pk->position = $player->getPosition()->subtract(0, 28, 0);
        $player->getNetworkSession()->sendDataPacket($pk);
    }
    
    /**
     * removeBossBar
     *
     * @param  Player $player
     * @return void
     */
    public function removeBossBar(Player $player): void
    {
        if (isset($this->players[strtolower($player->getName())])) {
            $pk = new BossEventPacket();
            $pk->bossEid = $this->entityId;
            $pk->eventType = BossEventPacket::TYPE_HIDE;
            $player->getNetworkSession()->sendDataPacket($pk);
        }
    }
    
    /**
     * setBossText
     *
     * @param  Player $player
     * @param  string $text
     * @return void
     */
    public function setBossText(Player $player, string $text): void
    {
        if (isset($this->players[strtolower($player->getName())])) {
            $pk = new BossEventPacket();
            $pk->bossEid = $this->entityId;
            $pk->bossEid = BossEventPacket::TYPE_TITLE;
            $pk->title = $text;
            $player->getNetworkSession()->sendDataPacket($pk);
        }
    }
    
    /**
     * setBossHealth
     *
     * @param  Player $player
     * @param  float $percentage
     * @return void
     */
    public function setBossHealth(Player $player, float $percentage): void
    {
        if (isset($this->players[strtolower($player->getName())])) {
            $pk = new BossEventPacket();
            $pk->bossEid = $this->entityId;
            $pk->eventType = BossEventPacket::TYPE_HEALTH_PERCENT;
            $pk->healthPercent = $percentage;
            $player->getNetworkSession()->sendDataPacket($pk);
        }
    }
    
    /**
     * sendBossBar
     *
     * @param  Player $player
     * @param  string $text
     * @param  float $percentage
     * @return void
     */
    public function sendBossBar(Player $player, string $text, float $percentage): void
    {
        $this->sendSpawnPacket($player);
        $this->removeBossBar($player);

        $pk = new BossEventPacket();
        $pk->bossEid = $this->entityId;
        $pk->eventType = BossEventPacket::TYPE_SHOW;
        $pk->title = $text;
        $pk->healthPercent = $percentage;
        $pk->color = 1;
        $pk->overlay = 1;
        $pk->unknownShort = 0;
        $player->getNetworkSession()->sendDataPacket($pk);
        $this->players[strtolower($player->getName())] = clone $pk;
    }
    
    /**
     * sendBossBarSessions
     *
     * @param  array $sessions
     * @param  string $text
     * @param  float $percentage
     * @return void
     */
    public function sendBossBarSessions(array $sessions, string $text, float $percentage): void
    {
        foreach ($sessions as $session) {
            $player = $session->getPlayer();

            $this->sendSpawnPacket($player);
            $this->removeBossBar($player);

            $pk = new BossEventPacket();
            $pk->bossEid = $this->entityId;
            $pk->eventType = BossEventPacket::TYPE_SHOW;
            $pk->title = $text;
            $pk->healthPercent = $percentage;
            $pk->color = 1;
            $pk->overlay = 1;
            $pk->unknownShort = 0;
            $player->getNetworkSession()->sendDataPacket($pk);
            $this->players[strtolower($player->getName())] = clone $pk;
        }
    }
}