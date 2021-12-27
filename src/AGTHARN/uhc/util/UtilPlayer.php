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

use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\sound\BlazeShootSound;
use pocketmine\world\sound\ClickSound;
use pocketmine\world\Position;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\Block;
use pocketmine\item\VanillaItems;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Process;
use pocketmine\math\Vector3;
use pocketmine\player\GameMode;
use pocketmine\player\Player;

use AGTHARN\uhc\session\SessionManager;
use AGTHARN\uhc\game\GameProperties;
use AGTHARN\uhc\Main;

class UtilPlayer
{   
    /** @var Main */
    private Main $plugin;
    
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

        $this->sessionManager = $plugin->getClass('SessionManager');
        $this->gameProperties = $plugin->getClass('GameProperties');
    }
    
    /**
     * resetPlayer
     * 
     * Resets player's inventory, health, hunger, XP.
     * (OPTIONAL: Teleports to spawn, give spectator items.)
     *
     * @param  Player $player
     * @param  bool $fullReset
     * @param  bool $giveSpecItems
     * @return void
     */
    public function resetPlayer(Player $player, bool $fullReset = false, bool $giveSpecItems = false): void
    {   
        $player->getHungerManager()->setFood($player->getHungerManager()->getMaxFood() ?? 10);
        $player->setHealth($player->getMaxHealth() ?? 10);
        $player->getEffects()->clear();
        $player->getXpManager()->setXpLevel(0);
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->getCursorInventory()->clearAll();
        $player->getOffHandInventory()->clearAll();
        $player->getEnderInventory()->clearAll();

        switch (true) {
            case $fullReset:
                $player->setGamemode(GameMode::SURVIVAL());
                $player->teleport(new Position($this->gameProperties->spawnPosX, $this->gameProperties->spawnPosY, $this->gameProperties->spawnPosZ, $this->plugin->getServer()->getWorldManager()->getWorldByName($this->gameProperties->map)));
                $player->sendMessage('§7woooosh');
                break;
            case $giveSpecItems:
                $this->giveSpecItems($player);
                break;
        }
    }
    
    /**
     * summonLightning
     * 
     * Summons a lightning at the location of player.
     *
     * @param  Player $player
     * @param  bool $playSound
     * @return void
     */
    public function summonLightning(Player $player, bool $playSound = true): void
    {
		$pos = $player->getPosition();

        $lightningBolt = new AddActorPacket();
		$lightningBolt->type = "minecraft:lightning_bolt";
		$lightningBolt->entityRuntimeId = 1;
		$lightningBolt->metadata = [];
		$lightningBolt->motion = null;
		$lightningBolt->yaw = $player->getLocation()->getYaw();
		$lightningBolt->pitch = $player->getLocation()->getPitch();
		$lightningBolt->position = new Vector3($pos->getX(), $pos->getY(), $pos->getZ());

        $packets[] = $lightningBolt;
        if ($playSound) {
            $sound = new PlaySoundPacket();
		    $sound->soundName = "ambient.weather.thunder";
            $sound->x = $pos->getX();
            $sound->y = $pos->getY();
		    $sound->z = $pos->getZ();
		    $sound->volume = 1;
		    $sound->pitch = 1;

            $packets[] = $sound;
        }
        
		$blockUnder = $player->getWorld()->getBlock($player->getPosition()->floor()->down());
		$blockParticle = new BlockBreakParticle($blockUnder);

		$player->getWorld()->addParticle($pos->asVector3(), $blockParticle, $player->getWorld()->getPlayers());

		$this->plugin->getServer()->broadcastPackets($player->getWorld()->getPlayers(), $packets);
	}
    
    /**
     * sendDebugInfo
     * 
     * Sends server debug information to player.
     *
     * @param  Player $player
     * @return void
     */
    public function sendDebugInfo(Player $player): void
    {
        $player->sendMessage('Welcome to UHC! Build ' . $this->gameProperties->buildNumber . ' © 2021 MineUHC');
        $player->sendMessage('UHC-' . $this->gameProperties->uhcServer . ': ' . $this->plugin->getOperationalColoredMessage());
        $player->sendMessage('THREADS: ' . Process::getThreadCount() . ' | RAM: ' . number_format(round((Process::getAdvancedMemoryUsage()[2] / 1024) / 1024, 2), 2) . ' MB.');
        $player->sendMessage('NODE: ' . $this->gameProperties->node);
        $player->sendMessage('API: ' . $this->plugin->getServer()->getApiVersion());
        $player->sendMessage('§7(information above for inviting, reporting etc.)');
    }

    /**
     * giveSpecItems
     * 
     * Sets spectator items at specific player slots.
     * 
     * ⧅◻◻◻⧅◻◻◻⧅
     * ⧅ = Item
     * ◻ = Empty
     *
     * @param  Player $player
     * @return void
     */
    public function giveSpecItems(Player $player): void
    {
        $hub = VanillaItems::COMPASS()->setCustomName('§aReturn To Hub');
        $hub->setNamedTag((new CompoundTag())->setTag('Hub', new StringTag('')));
        $capes = VanillaBlocks::WOOL()->asItem()->setCustomName('§eCapes');
        $capes->setNamedTag((new CompoundTag())->setTag('Capes', new StringTag('')));
        $report = VanillaItems::BED()->setCustomName('§cReport');
        $report->setNamedTag((new CompoundTag())->setTag('Report', new StringTag('')));

        $player->getInventory()->setItem(0 , $hub);
        $player->getInventory()->setItem(4 , $capes);
        $player->getInventory()->setItem(8 , $report);
    }
    
    /**
     * giveRoundStart
     * 
     * Sets round start items from slot 6 onwards.
     * 
     * ◻◻◻◻◻◻⧅⧅⧅
     * ⧅ = Item
     * ◻ = Empty
     *
     * @param  Player $player
     * @return void
     */
    public function giveRoundStart(Player $player): void
    {
        $i = 6;
        $items = [
            VanillaItems::BAKED_POTATO()->setCount(16),
            VanillaItems::SAPLING(),
            VanillaBlocks::ENCHANTING_TABLE()
        ];

        $inventory = $player->getInventory();
        foreach ($items as $item) {
            if ($inventory->canAddItem($item)) {
                $inventory->setItem($i, $item);
                $i++;
            }
        }
    }
    
    /**
     * getGoldenHead
     * 
     * Returns golden head item.
     *
     * @return Item
     */
    public function getGoldenHead(): Item
    {   
        $item = VanillaItems::GOLDEN_APPLE()->setCustomName('§6Golden Head');
        $item->setNamedTag((new CompoundTag())->setTag('golden_head_1', new StringTag('')));
        return $item;
    }
    
    /**
     * getHead
     * 
     * Returns head of a player.
     *
     * @param  Player $player
     * @return Item
     */
    public function getHead(Player $player): Item
    {   
        $item = VanillaItems::MOB_HEAD()->setCustomName('§6' . $player->getName() . 's Head');
        $item->setNamedTag((new CompoundTag())->setTag('player_head_1', new StringTag('')));
        return $item;
    }
    
    /**
     * playerTreeChop
     *
     * @param  Player $player
     * @param  Block $stump
     * @param  Item $item
     * @return bool
     */
    public function playerTreeChop(Player $player, Block $stump, Item $item): bool
    {
        $treetop = $this->getTreeTop($stump);
        if ($treetop < 0) {
            return false;
        }

        $stumpPos = $stump->getPosition();
        $world = $stumpPos->getWorld();
        for ($y = 0; $y < $treetop; $y++) {
            $block = $world->getBlock($stumpPos->add(0, $y, 0));
            $block->onBreak($item);
            foreach ($block->getDrops($item) as $drop) {
                $world->dropItem($stumpPos->add(0.4, 0.4, 0.4), VanillaItems::PLANKS()->setCount(4));
            }
        }
        return true;
    }
    
    /**
     * getTreeTop
     *
     * @param  Block $stump
     * @return int
     */
    public function getTreeTop(Block $stump): int
    {
        $stumpPos = $stump->getPosition();
        $world = $stumpPos->getWorld();

        $floor = $world->getBlock($stumpPos->getSide(0));
        if (!in_array($floor->getId(), [VanillaBlocks::DIRT(), VanillaBlocks::GRASS()])) {
            return -1;
        }

        $found = null;
        $maxHeight = $world->getMaxY() - $stumpPos->getY();

        for ($height = 0; $height < $maxHeight; $height++) {
            $block = $world->getBlock($stumpPos->add(0, $height, 0));
            if (in_array($block->getId(), [VanillaBlocks::WOOD(), VanillaBlocks::WOOD2()])) {
                if ($found === null) {
                    $found = [$block->getId(), $block->getMeta()];
                } elseif ($found[0] !== $block->getId() || $found[1] !== $block->getMeta()) {
                    return -1;
                }
            } elseif ($found !== null && in_array($block->getId(), [VanillaBlocks::LEAVES(), VanillaBlocks::LEAVES2()])) {
                return $height;
            }
        }
        return -1;
    }
    
    /**
     * checkAFK
     *
     * @param  Player $player
     * @return void
     */
    public function checkAFK(Player $player): void
    {
        $rPlayerPos = $this->rGetPos($player);
                
        if ($this->gameProperties->allPlayers[$player->getName()]['pos'] === $rPlayerPos) {
            if ($this->gameProperties->allPlayers[$player->getName()]['afk_time'] > 600) {
                $player->kick('omg?!?!? why u go afk midgame wut happenened!!!!');
            }
            $this->gameProperties->allPlayers[$player->getName()]['afk_time']++;
        } else {
            $this->gameProperties->allPlayers[$player->getName()]['pos'] = $rPlayerPos;
            $this->gameProperties->allPlayers[$player->getName()]['afk_time'] = 0;
        }
    }

    /**
     * sendSound
     *
     * @param  int $soundType
     * @return void
     */
    public function sendSound(int $soundType = 1): void
    {      
        switch ($soundType) {
            case 1:
                foreach ($this->sessionManager->getSessions() as $session) {
                    $player = $session->getPlayer();
                    if ($player->isOnline()) {
                        $player->getWorld()->addSound(new ClickSound(0, new Vector3($player->getX(), $player->getY(), $player->getZ())));
                    }
                }
                break;
            case 2:
                foreach ($this->sessionManager->getSessions() as $session) {
                    $player = $session->getPlayer();
                    if ($player->isOnline()) {
                        $player->getWorld()->addSound(new BlazeShootSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                    }
                }
                break;
        }
    }
    
    /**
     * rGetPos
     *
     * @param  Player $player
     * @return array
     */
    public function rGetPos(Player $player): array
    {
        $pos = $player->getPosition();
        return [round($pos->x), round($pos->y), round($pos->z), $player->getWorld()];
    }
    
    /**
     * toThin
     *
     * @param  string $str
     * @return string
     */
    public function toThin(string $str): string
    {
        return preg_replace('/%*(([a-z0-9_]+\.)+[a-z0-9_]+)/i', '%$1', $str) . TextFormat::ESCAPE . '　';
    }
}
