<?php
declare(strict_types=1);

namespace AGTHARN\uhc;

use pocketmine\plugin\PluginLoadOrder;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as TF;
use pocketmine\entity\utils\Bossbar;

use AGTHARN\uhc\command\SpectatorCommand;
use AGTHARN\uhc\session\SessionManager;
use AGTHARN\uhc\game\scenario\ScenarioManager;
use AGTHARN\uhc\game\team\TeamManager;
use AGTHARN\uhc\game\GameManager;
use AGTHARN\uhc\game\border\Border;
use AGTHARN\uhc\util\Handler;
use AGTHARN\uhc\util\Items;
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
        $plugins = getcwd() . "\n" . DIRECTORY_SEPARATOR . "plugins";

        @mkdir($this->getDataFolder() . "scenarios");
        @mkdir($plugins);

        $this->prepareLevels();
        
        $this->gameManager = new GameManager($this, $this->getBorder());
        $this->teamManager = new TeamManager();
        $this->sessionManager = new SessionManager();
        $this->scenarioManager = new ScenarioManager($this);
        $this->utilHandler = new Handler($this, $this->getBorder());
        $this->getScheduler()->scheduleRepeatingTask($this->gameManager, 20);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this, $this->getBorder()), $this);

        $this->getServer()->getPluginManager()->registerInterface(new FolderPluginLoader($this->getServer()->getLoader()));
        $this->getServer()->getPluginManager()->loadPlugins($plugins, [FolderPluginLoader::class]);
        $this->getServer()->enablePlugins(PluginLoadOrder::STARTUP);
        
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

        $worldAPI = $this->getServer()->getPluginManager()->getPlugin("MultiWorld")->getWorldManagementAPI(); /** @phpstan-ignore-line */

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

            $level = $this->getServer()->getLevelByName($this->map); // redefine so its not null
            $level->getGameRules()->setRuleWithMatching($this->matchRuleName($level->getGameRules()->getRules(), "domobspawning"), "true"); /** @phpstan-ignore-line */
            $level->getGameRules()->setRuleWithMatching($this->matchRuleName($level->getGameRules()->getRules(), "showcoordinates"), "true"); /** @phpstan-ignore-line */
        }
    }

    /**
     * getDirContents
     *
     * @param  mixed $dir
     * @param  string $filter
     * @param  array $results
     * @return array
     */
    public function getDirContents($dir, string $filter = '', array &$results = array()): array
    {
        $files = preg_grep('/^([^.])/', (array)scandir($dir));

        foreach ($files as $key => $value) {
            $path = (string)realpath($dir.DIRECTORY_SEPARATOR.$value); 

            if (!is_dir($path)) {
                if(empty($filter) || preg_match($filter, $path)) $results[] = $path;
            } elseif($value != "." && $value != "..") {
                $this->getDirContents($path, $filter, $results);
            }
        }
        return $results;
    } 
    
    /**
     * matchRuleName
     *
     * @param  array $rules
     * @param  string $input
     * @return string
     */
    public function matchRuleName(array $rules, string $input): string
    {
		foreach ($rules as $name => $d) {
			if (strtolower($name) === $input) {
				return $name;
			}
		}
		return $input;
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
