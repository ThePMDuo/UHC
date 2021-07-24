<?php
declare(strict_types=1);

namespace AGTHARN\uhc\util;

use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;
use pocketmine\level\Position;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\Player;

use AGTHARN\uhc\game\GameProperties;
use AGTHARN\uhc\Main;

class UtilPlayer
{   
    /** @var Main */
    private $plugin;
    
    /** @var SessionManager */
    private $sessionManager;
    /** @var GameProperties */
    private $gameProperties;

    /** @var array */
    private const FLOORS = [Block::DIRT, Block::GRASS];
    /** @var array */
    private const WOODS = [Block::WOOD, Block::WOOD2];
    /** @var array */
    private const LEAVES = [Block::LEAVES, Block::LEAVES2];

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
     * @param  Player $player
     * @return void
     */
    public function resetPlayer(Player $player, bool $fullReset = false): void
    {   
        $player->setFood($player->getMaxFood() ?? 10);
        $player->setHealth($player->getMaxHealth() ?? 10);
        $player->removeAllEffects();
        $player->setXpLevel(0);
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->getCursorInventory()->clearAll();
        //$player->getOffHandInventory()->clearAll();

        $pk = new GameRulesChangedPacket();
        $pk->gameRules = ["showCoordinates" => [1, true, true], "immediateRespawn" => [1, true, true]];
        $player->dataPacket($pk);

        if ($fullReset) {
            $player->setGamemode(Player::SURVIVAL);
            $player->teleport(new Position($this->gameProperties->spawnPosX, $this->gameProperties->spawnPosY, $this->gameProperties->spawnPosZ, $this->plugin->getServer()->getLevelByName($this->gameProperties->map)));
        }
    }

    /**
     * playerJoinReset
     *
     * @param  Player $player
     * @return void
     */
    public function playerJoinReset(Player $player): void
    {   
        $player->setFood($player->getMaxFood() ?? 10);
        $player->setHealth($player->getMaxHealth() ?? 10);
        $player->removeAllEffects();
        $player->setXpLevel(0);
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->getCursorInventory()->clearAll();
        //$player->getOffHandInventory()->clearAll();
        $player->setGamemode(Player::SURVIVAL);
        $player->teleport(new Position($this->gameProperties->spawnPosX, $this->gameProperties->spawnPosY, $this->gameProperties->spawnPosZ, $this->plugin->getServer()->getLevelByName($this->gameProperties->map)));
        
        $pk = new GameRulesChangedPacket();
        $pk->gameRules = ["showCoordinates" => [1, true, true], "immediateRespawn" => [1, true, true]];
        $player->dataPacket($pk);

        $this->giveSpecItems($player);
    }

    /**
     * giveSpecItems
     *
     * @param  Player $player
     * @return void
     */
    public function giveSpecItems(Player $player): void
    {
        $hub = Item::get(Item::COMPASS)->setCustomName('§aReturn To Hub');;
        $hub->setNamedTagEntry(new StringTag('Hub'));
        $capes = Item::get(Block::WOOL)->setCustomName('§eCapes');
        $capes->setNamedTagEntry(new StringTag('Capes'));
        $report = Item::get(Item::BED)->setCustomName('§cReport');
        $report->setNamedTagEntry(new StringTag('Report'));

        $player->getInventory()->setItem(0 , $hub);
        $player->getInventory()->setItem(4 , $capes);
        $player->getInventory()->setItem(8 , $report);
    }
    
    /**
     * giveRoundStart
     *
     * @param  Player $player
     * @return void
     */
    public function giveRoundStart(Player $player): void
    {
        $i = 6;
        $items = [
            Item::get(Item::BAKED_POTATO, 0, 16),
            Item::get(Item::SAPLING, 0, 1),
            Item::get(Block::ENCHANTING_TABLE, 0, 1)
        ];

        foreach ($items as $item) {
            if ($inventory->canAddItem($item)) {
                $player->getInventory()->setItem($i, $item);
                $i++;
            }
        }
    }
    
    /**
     * getGoldenHead
     *
     * @return Item
     */
    public function getGoldenHead(): Item
    {   
        $item = Item::get(Item::GOLDEN_APPLE, 0, 1)->setCustomName('§6Golden Head');
        $item->setNamedTagEntry(new StringTag('golden_head_1'));
        return $item;
    }
    
    /**
     * getHead
     *
     * @param  Player $player
     * @return Item
     */
    public function getHead(Player $player): Item
    {   
        $item = Item::get(Item::MOB_HEAD, 3, 1)->setCustomName('§6' . $player->getName() . 's Head');
        $item->setNamedTagEntry(new StringTag('player_head_1'));
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

        $level = $stump->getLevel();
        for ($y = 0; $y < $treetop; $y++) {
            $block = $level->getBlock($stump->add(0, $y, 0));
            $block->onBreak($item);
            foreach ($block->getDrops($item) as $drop) {
                $level->dropItem($stump->add(0.4, 0.4, 0.4), Item::get(Item::PLANKS, 0, 4));
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
        $level = $stump->getLevel();

        $floor = $level->getBlock($stump->getSide(0));
        if (!in_array($floor->getId(), self::FLOORS)) {
            return -1;
        }

        $found = null;
        $maxHeight = 128 - $stump->getY();

        for ($height = 0; $height < $maxHeight; $height++) {
            $block = $level->getBlock($stump->add(0, $height, 0));
            if (in_array($block->getId(), self::WOODS)) {
                if ($found === null) {
                    $found = [$block->getId(), $block->getDamage()];
                } elseif ($found[0] !== $block->getId() || $found[1] !== $block->getDamage()) {
                    return -1;
                }
            } elseif ($found !== null && in_array($block->getId(), self::LEAVES)) {
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
                
        if ($rPlayerPos === $this->gameProperties->allPlayers[$player->getName()]['pos']) {
            if ($this->gameProperties->allPlayers[$player->getName()]['afk_time'] > 600) {
                $player->kick('omg?!?!? why u go afk midgame wut happenened!!!!', false);
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
                        $player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
                    }
                }
                break;
            case 2:
                foreach ($this->sessionManager->getSessions() as $session) {
                    $player = $session->getPlayer();
                    if ($player->isOnline()) {
                        $player->getLevel()->addSound(new BlazeShootSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
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
        return [round($player->x), round($player->y), round($player->z), $player->getLevel()];
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
