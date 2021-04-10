<?php
declare(strict_types=1);

namespace AGTHARN\uhc;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as TF;

use AGTHARN\uhc\command\SpectatorCommand;
use AGTHARN\uhc\session\SessionManager;
use AGTHARN\uhc\game\scenario\ScenarioManager;
use AGTHARN\uhc\game\team\TeamManager;
use AGTHARN\uhc\game\GameManager;
use AGTHARN\uhc\util\Handler;
use AGTHARN\uhc\EventListener;

use AGTHARN\uhc\libs\xenialdan\apibossbar\BossBar;

class Main extends PluginBase
{   
    /** @var int */
    public $uhcServer = 1;
    /** @var int */
    public $buildNumber = 1;
    /** @var bool */
    public $operational = true;
    
    /** @var int */
    public $seed;
    /** @var string */
    public $map = "UHC";

    /** @var int */
    public $spawnPosX = 0;
    /** @var int */
    public $spawnPosY = 100;
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
        if (!is_dir($this->getDataFolder() . "scenarios")) {
            mkdir($this->getDataFolder() . "scenarios");
        }
        $this->prepareLevels();
        
        $this->gameManager = new GameManager($this);
        $this->teamManager = new TeamManager();
        $this->sessionManager = new SessionManager();
        $this->scenarioManager = new ScenarioManager($this);
        $this->utilHandler = new Handler($this);
        $this->getScheduler()->scheduleRepeatingTask($this->gameManager, 20);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);

        $this->getServer()->getCommandMap()->registerAll("uhc", [
            new SpectatorCommand($this)
        ]);
    }
    
    /**
     * prepareLevels
     *
     * @return void
     */
    public function prepareLevels(): void
    {
        $level = $this->getServer()->getLevelByName($this->map);
        $levelName = $this->map;
        $levelPath = $this->getServer()->getDataPath() . "worlds/" . $this->map;

        $worldAPI = $this->getServer()->getPluginManager()->getPlugin("MultiWorld")->getWorldManagementAPI();

        if ($worldAPI->isLevelGenerated($levelName)) {
            if($worldAPI->isLevelLoaded($levelName)) {  
                $worldAPI->unloadLevel($level);
            }
            $worldAPI->removeLevel($levelName);
            $this->prepareLevels();
        } else {  
            $this->seed = $this->generateRandomSeed();

            if ((int)$this->seed === 0) {
                $this->seed = $this->generateRandomSeed();
            }
            $worldAPI->generateLevel($levelName, $this->seed, 1);  
            $worldAPI->loadLevel($levelName);
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
     * @return BossBar
     */
    public function getBossBar(): BossBar
    {
        return new BossBar();
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
    public function setOperational(bool $operational): void {
        if ($this->getOperational()) {
            $this->operational = $operational;
        }
    }
    
    /**
     * getOperational
     *
     * @return bool
     */
    public function getOperational(): bool {
        return $this->operational;
    }
    
    /**
     * getOperationalMessage
     *
     * @return string
     */
    public function getOperationalMessage(): string {
        if ($this->getOperational()) {
            return TF::GREEN . "SERVER OPERATIONAL";
        }
        return TF::RED . "SERVER UNOPERATIONAL";
    }
}
