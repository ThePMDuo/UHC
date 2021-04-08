<?php
declare(strict_types=1);

namespace AGTHARN\uhc;

use pocketmine\level\generator\GeneratorManager;
use pocketmine\entity\utils\Bossbar;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as TF;

use AGTHARN\uhc\command\SpectatorCommand;
use AGTHARN\uhc\session\SessionManager;
use AGTHARN\uhc\game\scenario\ScenarioManager;
use AGTHARN\uhc\game\team\TeamManager;
use AGTHARN\uhc\game\GameManager;
use AGTHARN\uhc\EventListener;

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

    /** @var GameManager */
    private $gameManager;
    /** @var TeamManager */
    private $teamManager;
    /** @var SessionManager */
    private $sessionManager;
    /** @var ScenarioManager */
    private $scenarioManager;

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
        $level = $this->getServer()->getLevelByName("UHC");
        $levelName = "UHC";
        $levelPath = $this->getServer()->getDataPath() . "worlds/UHC";

        if (is_dir($levelPath)) {
            if ($level !== null) {
                $this->getServer()->unloadLevel($level);
            }
            $this->rrmdir($levelPath);
            $this->prepareLevels();
        } else {  
            $this->seed = $this->generateRandomSeed();

            $generator = GeneratorManager::getGenerator("betternormal");
            $generatorName = "betternormal";

            if ((int)$this->seed === 0) {
                $this->seed = $this->generateRandomSeed();
            }
            $this->getServer()->generateLevel($levelName, $this->seed, $generator, []);
            $this->getServer()->loadLevel("UHC");
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
     * rrmdir
     *
     * @param  mixed $dir
     * @return void
     */
    public function rrmdir($dir): void
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) { /* @phpstan-ignore-line */
                if ($object != "." && $object != "..") {
                    if (filetype($dir."/".$object) == "dir") {
                        $this->rrmdir($dir."/".$object); 
                    } else {
                        unlink($dir."/".$object);
                    }
                }
            }
            reset($objects); /* @phpstan-ignore-line */
            rmdir($dir);
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
     * getBossBar
     *
     * @return Bossbar
     */
    public function getBossBar(): Bossbar /** @phpstan-ignore-line */
    {
        return new Bossbar(); /** @phpstan-ignore-line */
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
