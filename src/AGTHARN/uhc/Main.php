<?php
declare(strict_types=1);

namespace AGTHARN\uhc;

use pocketmine\entity\utils\Bossbar;
use pocketmine\plugin\PluginBase;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\Player;

use AGTHARN\uhc\game\scenario\ScenarioManager;
use AGTHARN\uhc\game\border\Border;
use AGTHARN\uhc\game\team\TeamManager;
use AGTHARN\uhc\game\GameManager;
use AGTHARN\uhc\command\SpectatorCommand;
use AGTHARN\uhc\session\SessionManager;
use AGTHARN\uhc\util\UtilPlayer;
use AGTHARN\uhc\util\Generators;
use AGTHARN\uhc\util\DeathChest;
use AGTHARN\uhc\util\ChestSort;
use AGTHARN\uhc\util\Handler;
use AGTHARN\uhc\util\Items;
use AGTHARN\uhc\util\Capes;
use AGTHARN\uhc\util\Forms;
use AGTHARN\uhc\kits\Kits;
use AGTHARN\uhc\EventListener;

class Main extends PluginBase
{   
    /** @var string */
    public $uhcServer = 'GAME-1';
    /** @var string */
    public $node = 'NYC-01';
    /** @var string */
    public $buildNumber = 'BETA-1';
    /** @var bool */
    public $operational = true;
    
    /** @var int */
    public $normalSeed;
    /** @var string */
    public $map = 'UHC';
    /** @var int */
    public $netherSeed;
    /** @var string */
    public $nether = 'nether';

    /** @var int */
    public $spawnPosX = 0;
    /** @var int */
    public $spawnPosY = 125;
    /** @var int */
    public $spawnPosZ = 0;

    /** @var GameManager */
    private $gameManager;
    /** @var TeamManager */
    private $teamManager;
    /** @var SessionManager */
    private $sessionManager;
    /** @var ScenarioManager */
    private $scenarioManager;
    /** @var Handler */
    private $utilHandler;
    /** @var Border */
    private $border;
    /** @var Items */
    private $items;
    /** @var ChestSort */
    private $chestSort;
    /** @var DeathChest */
    private $deathChest;
    /** @var Kits */
    private $kits;
    /** @var Capes */
    private $capes;
    /** @var Generators */
    private $generators;
    /** @var UtilPlayer */
    private $utilplayer;
    /** @var Forms */
    private $forms;

    /** @var bool */
    private $globalMuteEnabled = false;
    
    /**
     * onEnable
     *
     * @return void
     */
    public function onEnable(): void
    {   
        if (!extension_loaded('gd')) {
            $this->getServer()->getLogger()->error('GD Lib is disabled! Turning on safe mode!');
            $this->setOperational(false);
        }
        @mkdir($this->getDataFolder() . 'scenarios');
        $this->saveResource('normal_cape.png');

        $this->getGenerators()->prepareWorld();
        //$this->getGenerators()->prepareNether();

        $this->gameManager = new GameManager($this, $this->getBorder());
        $this->scenarioManager = new ScenarioManager($this);
        $this->sessionManager = new SessionManager();
        $this->teamManager = new TeamManager();
        $this->utilHandler = new Handler($this, $this->getBorder());
        $this->border = new Border($this->getServer()->getLevelByName($this->map));
        $this->items = new Items($this);
        $this->chestSort = new ChestSort($this);
        $this->deathChest = new DeathChest($this);
        $this->kits = new Kits();
        $this->capes = new Capes($this);
        $this->generators = new Generators($this);
        $this->utilplayer = new UtilPlayer($this);
        $this->forms = new Forms();

        $this->getScheduler()->scheduleRepeatingTask($this->gameManager, 20);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this, $this->getBorder()), $this);
        
        $this->getServer()->getCommandMap()->registerAll('uhc', [
            new SpectatorCommand($this)
        ]);
    }
    
    /**
     * veinMine
     *
     * @param  Block $block
     * @param  Item $item
     * @param  Player $player
     * @return void
     */
    public function veinMine(Block $block, Item $item, Player $player): void
    {
        if ($block->isValid()) {
            foreach ($block->getAllSides() as $side) {
                if (($side->getId() === $block->getId())) {
                    $this->veinMine($side, $item, $player);
                }
            }
            $block->getLevel()->useBreakOn($block, $item, $player, true);
        }
    }
    
    /**
     * getManager
     *
     * @return GameManager
     */
    public function getManager(): GameManager
    {
        return $this->gameManager ?? new GameManager($this, $this->getBorder());
    }

    /**
     * getScenarioManager
     *
     * @return ScenarioManager
     */
    public function getScenarioManager(): ScenarioManager
    {
        return $this->scenarioManager ?? new ScenarioManager($this);
    }
    
    /**
     * getSessionManager
     *
     * @return SessionManager
     */
    public function getSessionManager(): SessionManager
    {   
        return $this->sessionManager ?? new SessionManager();
    }
    

    /**
     * getTeamManager
     *
     * @return TeamManager
     */
    public function getTeamManager(): TeamManager
    {
        return $this->teamManager ?? new TeamManager();
    }
    
    /**
     * getHandler
     *
     * @return Handler
     */
    public function getHandler(): Handler {
        return $this->utilHandler ?? new Handler($this, $this->getBorder());
    }

    /**
     * getBossBar
     *
     * @return mixed
     */
    public function getBossBar(string $text, float $float)
    {
        return new Bossbar($text, $float); /** @phpstan-ignore-line */
    }
    
    /**
     * getBorder
     *
     * @return Border
     */
    public function getBorder(): Border
    {
        return $this->border ?? new Border($this->getServer()->getLevelByName($this->map));
    }
    
    /**
     * getUtilItems
     *
     * @return Items
     */
    public function getUtilItems(): Items
    {
        return $this->items ?? new Items($this);
    }

    /**
     * getChestSort
     *
     * @return ChestSort
     */
    public function getChestSort(): ChestSort
    {
        return $this->chestSort ?? new ChestSort($this);
    }
    
    /**
     * getDeathChest
     *
     * @return DeathChest
     */
    public function getDeathChest(): DeathChest
    {
        return $this->deathChest ?? new DeathChest($this);
    }
    
    /**
     * getKits
     *
     * @return Kits
     */
    public function getKits(): Kits
    {
        return $this->kits ?? new Kits();
    }

    /**
     * getCapes
     *
     * @return Capes
     */
    public function getCapes(): Capes
    {
        return $this->capes ?? new Capes($this);
    }
    
    /**
     * getGenerators
     *
     * @return Generators
     */
    public function getGenerators(): Generators
    {
        return $this->generators ?? new Generators($this);
    }

    /**
     * getUtilPlayer
     *
     * @return UtilPlayer
     */
    public function getUtilPlayer(): UtilPlayer
    {
        return $this->utilplayer ?? new UtilPlayer($this);
    }
    
    /**
     * getForms
     *
     * @return Forms
     */
    public function getForms(): Forms
    {
        return $this->forms ?? new Forms();
    }
    
    /**
     * setGlobalMute
     *
     * @param  bool $enabled
     * @return void
     */
    public function setGlobalMute(bool $enabled): void
    {
        $this->globalMuteEnabled = $enabled;
    }
    
    /**
     * isGlobalMuteEnabled
     *
     * @return bool
     */
    public function isGlobalMuteEnabled(): bool
    {
        return $this->globalMuteEnabled;
    }
    
    /**
     * setOperational
     *
     * @param  bool $operational
     * @return void
     */
    public function setOperational(bool $operational): void
    {
        if ($this->getOperational()) {
            $this->operational = $operational;
        }
    }
    
    /**
     * getOperational
     *
     * @return bool
     */
    public function getOperational(): bool
    {
        return $this->operational;
    }
    
    /**
     * getOperationalColoredMessage
     *
     * @return string
     */
    public function getOperationalColoredMessage(): string
    {
        if ($this->getOperational()) {
            return '§aSERVER OPERATIONAL';
        }
        return '§cSERVER UNOPERATIONAL: POSSIBLY RESETTING';
    }

    /**
     * getOperationalMessage
     *
     * @return string
     */
    public function getOperationalMessage(): string
    {
        if ($this->getOperational()) {
            return 'SERVER OPERATIONAL';
        }
        return 'SERVER UNOPERATIONAL: POSSIBLY RESETTING';
    }
}
