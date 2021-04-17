<?php
declare(strict_types=1);

namespace AGTHARN\uhc\kits;

use pocketmine\item\Item;
use pocketmine\Player;

use AGTHARN\uhc\libs\JackMD\ScoreFactory\ScoreFactory;

class Kits
{    
    // Item::get(Item::item, 0, 1)
    
    /** @var array */
    private $stoneAge = [];
    /** @var array */
    private $stoneKid = [];
    /** @var array */
    private $stoneMiner = [];
    /** @var array */
    private $stoneLumberjack = [];
    /** @var array */
    private $oogaChaka = [];
    /** @var array */
    private $tank = [];
    /** @var array */
    private $lavaBoy = [];
    /** @var array */
    private $waterGirl = [];
    /** @var array */
    private $snowBaller = [];
    /** @var array */
    private $vegetarian = [];
    /** @var array */
    private $nonVegetarian = [];
    /** @var array */
    private $superFeet = [];
    /** @var array */
    private $miner = [];
    /** @var array */
    private $warrior = [];
    /** @var array */
    private $doctor = [];
    /** @var array */
    private $daBaby = [];
    /** @var array */
    private $miniTank = [];
    /** @var array */
    private $skeleton = [];
    
    /** @var int */
    private $kitsTotal = 18;
    
    /**
     * __construct
     *
     * @return void
     */
    public function __construct()
    {
        $this->stoneAge = [Item::get(Item::STONE_SWORD, 0, 1), Item::get(Item::CHAINMAIL_CHESTPLATE, 0, 1), Item::get(Item::STONE, 0, 32)];
        $this->stoneKid = [Item::get(Item::STONE_SWORD, 0, 1), Item::get(Item::CHAINMAIL_LEGGINGS, 0, 1), Item::get(Item::COBBLESTONE, 0, 32)];
        $this->stoneMiner = [Item::get(Item::STONE_PICKAXE, 0, 1), Item::get(Item::CHAINMAIL_HELMET, 0, 1), Item::get(Item::CHAINMAIL_BOOTS, 0, 1)];
        $this->stoneLumberjack = [Item::get(Item::STONE_AXE, 0, 1), Item::get(Item::CHAINMAIL_CHESTPLATE, 0, 1), Item::get(Item::LOG, 0, 16)];
        $this->oogaChaka = [Item::get(Item::WOODEN_SWORD, 0, 1), Item::get(Item::LEATHER_CHESTPLATE, 0, 1), Item::get(Item::LEATHER_LEGGINGS, 0, 1), Item::get(Item::LOG, 0, 32)];
        $this->tank = [Item::get(Item::WOODEN_SWORD, 0, 1), Item::get(Item::CHAINMAIL_HELMET, 0, 1), Item::get(Item::LEATHER_CHESTPLATE, 0, 1), Item::get(Item::LEATHER_LEGGINGS, 0, 1)];
        $this->lavaBoy = [Item::get(Item::BUCKET, 10, 2)];
        $this->waterGirl = [Item::get(Item::BUCKET, 8, 2)];
        $this->snowBaller = [Item::get(Item::SNOWBALL, 0, 16)];
        $this->vegetarian = [Item::get(Item::CARROT, 0, 16), Item::get(Item::POTATO, 0, 16)];
        $this->nonVegetarian = [Item::get(Item::STEAK, 0, 8), Item::get(Item::COOKED_MUTTON, 0, 8)];
        $this->superFeet = [Item::get(Item::IRON_BOOTS, 0, 1)];
        $this->miner = [Item::get(Item::IRON_PICKAXE, 0, 1)];
        $this->warrior = [Item::get(Item::IRON_SWORD, 0, 1)];
        $this->doctor = [Item::get(Item::APPLE, 0, 32)];
        $this->daBaby = [Item::get(Item::GOLD_INGOT, 0, 10)->setCustomName("i will turn into a convertible")];
        $this->miniTank = [Item::get(Item::LEATHER_HELMET, 0, 1), Item::get(Item::LEATHER_CHESTPLATE, 0, 1), Item::get(Item::LEATHER_LEGGINGS, 0, 1), Item::get(Item::LEATHER_BOOTS, 0, 1)];
        $this->skeleton = [Item::get(Item::BOW, 0, 1), Item::get(Item::ARROW, 0, 32), Item::get(Item::BONE, 0, 2)];
    }

    /**
     * giveKit
     *
     * @param  Player $player
     * @return string
     */
    public function giveKit(Player $player): string
    {   
        // NOTE: no break cuz return is already the same as break
        switch (mt_rand(1, $this->kitsTotal)) {
            case 1:
                $this->giveStoneAge($player);
                return "Stone Age";
            case 2:
                $this->giveStoneKid($player);
                return "Stone Kid";
            case 3:
                $this->giveStoneMiner($player);
                return "Stone Miner";
            case 4:
                $this->giveLumberjack($player);
                return "Lumberjack";
            case 5:
                $this->giveOogaChaka($player);
                return "ooogachaka";
            case 6:
                $this->giveTank($player);
                return "Tank";
            case 7:
                $this->giveLavaBoy($player);
                return "Lava Boy";
            case 8:
                $this->giveWaterGirl($player);
                return "Water Girl";
            case 9:
                $this->giveSnowBaller($player);
                return "Snowballer";
            case 10:
                $this->giveVegetarian($player);
                return "Vegetarian";
            case 11:
                $this->giveNonVegetarian($player);
                return "Non-Vegetarian";
            case 12:
                $this->giveSuperFeet($player);
                return "Super Feet";
            case 13:
                $this->giveMiner($player);
                return "Miner";
            case 14:
                $this->giveWarrior($player);
                return "Warrior";
            case 15:
                $this->giveDoctor($player);
                return "Doctor";
            case 16:
                $this->giveDaBaby($player);
                return "dababy lets gooo";
            case 17:
                $this->giveMiniTank($player);
                return "Mini Tank";
            case 18:
                $this->giveSkeleton($player);
                return "Skeleton";
        }
        return "ERROR - PLEASE REPORT";
    }
    
    /**
     * giveStoneAge
     *
     * @param  Player $player
     * @return void
     */
    public function giveStoneAge(Player $player): void
    {
        foreach ($this->stoneAge as $item) {
            $player->getInventory()->addItem($item);
        }
    }
    
    /**
     * giveStoneKid
     *
     * @param  Player $player
     * @return void
     */
    public function giveStoneKid(Player $player): void
    {
        foreach ($this->stoneKid as $item) {
            $player->getInventory()->addItem($item);
        }
    }

    /**
     * giveStoneMiner
     *
     * @param  Player $player
     * @return void
     */
    public function giveStoneMiner(Player $player): void
    {
        foreach ($this->stoneMiner as $item) {
            $player->getInventory()->addItem($item);
        }
    }

    /**
     * giveLumberjack
     *
     * @param  Player $player
     * @return void
     */
    public function giveLumberjack(Player $player): void
    {
        foreach ($this->stoneLumberjack as $item) {
            $player->getInventory()->addItem($item);
        }
    }

    /**
     * giveOogaChaka
     *
     * @param  Player $player
     * @return void
     */
    public function giveOogaChaka(Player $player): void
    {
        foreach ($this->oogaChaka as $item) {
            $player->getInventory()->addItem($item);
        }
    }

    /**
     * giveTank
     *
     * @param  Player $player
     * @return void
     */
    public function giveTank(Player $player): void
    {
        foreach ($this->tank as $item) {
            $player->getInventory()->addItem($item);
        }
    }

    /**
     * giveLavaBoy
     *
     * @param  Player $player
     * @return void
     */
    public function giveLavaBoy(Player $player): void
    {
        foreach ($this->lavaBoy as $item) {
            $player->getInventory()->addItem($item);
        }
    }

    /**
     * giveWaterGirl
     *
     * @param  Player $player
     * @return void
     */
    public function giveWaterGirl(Player $player): void
    {
        foreach ($this->waterGirl as $item) {
            $player->getInventory()->addItem($item);
        }
    }

    /**
     * giveSnowBaller
     *
     * @param  Player $player
     * @return void
     */
    public function giveSnowBaller(Player $player): void
    {
        foreach ($this->snowBaller as $item) {
            $player->getInventory()->addItem($item);
        }
    }

    /**
     * giveVegetarian
     *
     * @param  Player $player
     * @return void
     */
    public function giveVegetarian(Player $player): void
    {
        foreach ($this->vegetarian as $item) {
            $player->getInventory()->addItem($item);
        }
    }

    /**
     * giveNonVegetarian
     *
     * @param  Player $player
     * @return void
     */
    public function giveNonVegetarian(Player $player): void
    {
        foreach ($this->nonVegetarian as $item) {
            $player->getInventory()->addItem($item);
        }
    }

    /**
     * giveSuperFeet
     *
     * @param  Player $player
     * @return void
     */
    public function giveSuperFeet(Player $player): void
    {
        foreach ($this->superFeet as $item) {
            $player->getInventory()->addItem($item);
        }
    }

    /**
     * giveMiner
     *
     * @param  Player $player
     * @return void
     */
    public function giveMiner(Player $player): void
    {
        foreach ($this->miner as $item) {
            $player->getInventory()->addItem($item);
        }
    }

    /**
     * giveWarrior
     *
     * @param  Player $player
     * @return void
     */
    public function giveWarrior(Player $player): void
    {
        foreach ($this->warrior as $item) {
            $player->getInventory()->addItem($item);
        }
    }

    /**
     * giveDoctor
     *
     * @param  Player $player
     * @return void
     */
    public function giveDoctor(Player $player): void
    {
        foreach ($this->doctor as $item) {
            $player->getInventory()->addItem($item);
        }
    }

    /**
     * giveDaBaby
     *
     * @param  Player $player
     * @return void
     */
    public function giveDaBaby(Player $player): void
    {
        foreach ($this->daBaby as $item) {
            $player->getInventory()->addItem($item);
        }
    }

    /**
     * giveMiniTank
     *
     * @param  Player $player
     * @return void
     */
    public function giveMiniTank(Player $player): void
    {
        foreach ($this->miniTank as $item) {
            $player->getInventory()->addItem($item);
        }
    }

    /**
     * giveSkeleton
     *
     * @param  Player $player
     * @return void
     */
    public function giveSkeleton(Player $player): void
    {
        foreach ($this->skeleton as $item) {
            $player->getInventory()->addItem($item);
        }
    }
}