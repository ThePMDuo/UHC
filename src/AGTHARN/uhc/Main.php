<?php
declare(strict_types=1);

namespace AGTHARN\uhc;

use pocketmine\level\generator\GeneratorManager;
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
use AGTHARN\uhc\util\ChestSort;
use AGTHARN\uhc\util\Handler;
use AGTHARN\uhc\util\Items;
use AGTHARN\uhc\kits\Kits;
use AGTHARN\uhc\EventListener;

class Main extends PluginBase
{   
    /** @var string */
    public $uhcServer = "GAME-1";
    /** @var string */
    public $node = "NYC-01";
    /** @var string */
    public $buildNumber = "BETA-1";
    /** @var bool */
    public $operational = true;
    
    /** @var int */
    public $normalSeed;
    /** @var string */
    public $map = "UHC";
    /** @var int */
    public $netherSeed;
    /** @var string */
    public $nether = "nether";

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

    /** @var bool */
    private $globalMuteEnabled = false;
    
    /**
     * onEnable
     *
     * @return void
     */
    public function onEnable(): void
    {   
        @mkdir($this->getDataFolder() . "scenarios");

        $this->prepareWorld();
        //$this->prepareNether();
        
        $this->gameManager = new GameManager($this, $this->getBorder());
        $this->teamManager = new TeamManager();
        $this->sessionManager = new SessionManager();
        $this->scenarioManager = new ScenarioManager($this);
        $this->utilHandler = new Handler($this, $this->getBorder());
        $this->getScheduler()->scheduleRepeatingTask($this->gameManager, 20);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this, $this->getBorder()), $this);
        
        $this->getServer()->getCommandMap()->registerAll("uhc", [
            new SpectatorCommand($this)
        ]);
    }
    
    /**
     * prepareWorld
     *
     * @return void
     */
    public function prepareWorld(): void
    {   
        $uhcName = $this->map;
        $uhcLevel = $this->getServer()->getLevelByName($uhcName);

        $worldAPI = $this->getServer()->getPluginManager()->getPlugin("MultiWorld")->getWorldManagementAPI(); /** @phpstan-ignore-line */

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

            $uhcLevel = $this->getServer()->getLevelByName($this->map); // redefine so its not null
            $uhcLevel->getGameRules()->setRuleWithMatching("domobspawning", "true"); /** @phpstan-ignore-line */
            $uhcLevel->getGameRules()->setRuleWithMatching("showcoordinates", "true"); /** @phpstan-ignore-line */
        }
    }
    
    /**
     * prepareNether
     *
     * @return void
     */
    public function prepareNether(): void
    {
        $netherName = $this->nether;
        $netherLevel = $this->getServer()->getLevelByName($netherName);

        $worldAPI = $this->getServer()->getPluginManager()->getPlugin("MultiWorld")->getWorldManagementAPI(); /** @phpstan-ignore-line */

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
            $this->getServer()->generateLevel($netherName, $this->netherSeed, GeneratorManager::getGenerator("nether"));
            $worldAPI->loadLevel($netherName);

            $netherLevel = $this->getServer()->getLevelByName($this->nether); // redefine so its not null
            $netherLevel->getGameRules()->setRuleWithMatching("showcoordinates", "true"); /** @phpstan-ignore-line */
            $this->getServer()->setNetherLevel($netherLevel); /** @phpstan-ignore-line */
        }
    }
    
    /**
     * generateRandomSeed
     *
     * @return int
     */
    public function generateRandomSeed(): int
    {
        return intval(rand(0, intval(time() / memory_get_usage(true) * (int) str_shuffle("127469453645108") / (int) str_shuffle("12746945364"))));
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
        return $this->gameManager;
    }

    /**
     * getScenarioManager
     *
     * @return ScenarioManager
     */
    public function getScenarioManager(): ScenarioManager
    {
        return $this->scenarioManager;
    }
    
    /**
     * getSessionManager
     *
     * @return SessionManager
     */
    public function getSessionManager(): SessionManager
    {
        return $this->sessionManager;
    }
    

    /**
     * getTeamManager
     *
     * @return TeamManager
     */
    public function getTeamManager(): TeamManager
    {
        return $this->teamManager;
    }
    
    /**
     * getHandler
     *
     * @return Handler
     */
    public function getHandler(): Handler {
        return $this->utilHandler;
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
        return new Border($this->getServer()->getLevelByName($this->map));
    }
    
    /**
     * getUtilItems
     *
     * @return Items
     */
    public function getUtilItems(): Items
    {
        return new Items($this);
    }

    /**
     * getChestSort
     *
     * @return ChestSort
     */
    public function getChestSort(): ChestSort
    {
        return new ChestSort($this);
    }
    
    /**
     * getKits
     *
     * @return Kits
     */
    public function getKits(): Kits
    {
        return new Kits();
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
            return "§aSERVER OPERATIONAL";
        }
        return "§cSERVER UNOPERATIONAL: POSSIBLY RESETTING";
    }

    /**
     * getOperationalMessage
     *
     * @return string
     */
    public function getOperationalMessage(): string
    {
        if ($this->getOperational()) {
            return "SERVER OPERATIONAL";
        }
        return "SERVER UNOPERATIONAL: POSSIBLY RESETTING";
    }
}
