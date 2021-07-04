<?php
declare(strict_types=1);

namespace AGTHARN\uhc;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

use AGTHARN\uhc\game\team\TeamManager;
use AGTHARN\uhc\command\SpectatorCommand;
use AGTHARN\uhc\command\ReportCommand;
use AGTHARN\uhc\command\PingCommand;
use AGTHARN\uhc\command\ModCommand;
use AGTHARN\uhc\session\SessionManager;
use AGTHARN\uhc\listener\ListenerManager;
use AGTHARN\uhc\util\Database;

use AGTHARN\uhc\libs\poggit\libasynql\DataConnector;
use AGTHARN\uhc\libs\poggit\libasynql\libasynql;

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
    public $startingPlayers = 0;
    /** @var int */
    public $startingTeams = 0;

    /** @var array */
    public $entityRegainNote = [];

    /** @var Config */
    public $secrets;
    /** @var string */
    public $reportWebhook = '';
    /** @var string */
    public $serverReportsWebhook = '';
    /** @var string */
    public $serverPowerWebhook = '';

    /** @var int */
    public $spawnPosX = 0;
    /** @var int */
    public $spawnPosY = 125;
    /** @var int */
    public $spawnPosZ = 0;

    /** @var ListenerManager */
    private $listenerManager;
    /** @var DataConnector */
    private $data;

    /** @var string|array */
    private $contents = '';
    
    /**
     * onEnable
     *
     * @return void
     */
    public function onEnable(): void
    {   
        $this->contents = $this->getDirContents(str_replace('/', DIRECTORY_SEPARATOR, $this->getServer()->getDataPath() . 'plugins/UHC/src/') . 'AGTHARN/uhc');
        $this->secrets = new Config($this->getDataFolder() . 'secrets.yml', Config::YAML);

        $this->reportWebhook = $this->secrets->get('reportWebhook');
        $this->serverReportsWebhook = $this->secrets->get('serverReportsWebhook');
        $this->serverPowerWebhook = $this->secrets->get('serverPowerWebhook');

        $this->listenerManager = $this->getClass('ListenerManager');
        // idot shit
        $this->sessionManager = new SessionManager();

        @mkdir($this->getDataFolder() . 'scenarios');
        $this->saveResource('capes/normal_cape.png');
        $this->saveResource('capes/potion_cape.png');
        $this->saveResource('swearwords.yml');
        $this->saveResource('secrets.yml');
        
        $this->getClass('Recipes')->registerGoldenHead();
        $this->getClass('Spoon')->makeTheCheck();

        $this->getClass('Generators')->prepareWorld();
        //$this->getClass('Generators')->prepareNether();

        $this->getScheduler()->scheduleRepeatingTask($this->getClass('GameManager'), 20);
        $this->getClass('ListenerManager')->registerListeners();
        $this->runCompatibilityChecks();
        $this->registerCommands();
    
        $this->getClass('Discord')->sendStartReport($this->getServer()->getVersion(), $this->buildNumber, $this->node, $this->uhcServer);

        $this->data = $this->getClass('Database')->initDataDatabase();
        $this->data->executeGeneric('uhc.data.init');
    }
    
    /**
     * onDisable
     *
     * @return void
     */
    public function onDisable(): void
    {   
        $this->getClass('Generators')->removeAllWorlds();

        if (isset($this->data)) $this->data->close();
    }
    
    /**
     * runCompatibilityChecks
     *
     * @return void
     */
    public function runCompatibilityChecks()
    {
        if (!extension_loaded('gd')) {
            $this->getServer()->getLogger()->error('GD Lib is disabled! Turning on safe mode!');
            $this->setOperational(false);
        }
        //if (!in_array($this->getServer()->getApiVersion(), $this->getDescription()->getCompatibleApis())) {
            //$this->getServer()->getLogger()->error('Incompatible version! Turning on safe mode!');
            //$this->setOperational(false);
        //}
    }
    
    /**
     * registerCommands
     *
     * @return void
     */
    public function registerCommands(): void
    {
        $this->getServer()->getCommandMap()->register('spectate', new SpectatorCommand($this, 'spectate', 'Spectates a player!', [
            'spectator'
        ]));
        $this->getServer()->getCommandMap()->register('report', new ReportCommand($this, 'report', 'Report a player for breaking a rule!', [
            'reports', 
            'reporter', 
            'reporting', 
            'callmod', 
            'modcall', 
            'admincall', 
            'calladmin',
            'saveme',
            'hacker',
            'cheater'
        ]));
        $this->getServer()->getCommandMap()->register('ping', new PingCommand($this, 'ping', 'Provides a report on your ping!', [
            'myping',
            'getping'
        ]));
        $this->getServer()->getCommandMap()->register('moderate', new ModCommand($this, 'moderate', 'Secret stuff!', [
            'mod',
            'punish'
        ]));
    }
    
    /**
     * getClass
     *
     * @param  string $search
     * @return mixed
     */
    public function getClass(string $search): mixed
    {   
        $pluginMainPath = str_replace('/', DIRECTORY_SEPARATOR, $this->getServer()->getDataPath() . 'plugins/UHC/src/');
        $namespace = '';

        foreach ($this->contents as $object) {
            if (strpos((string)$object, $search) !== false) {
                $namespace = preg_replace("/(.+)\.php$/", "$1", str_replace($pluginMainPath, '', (string)$object));
            }
        }
        
        if (strpos($namespace, 'SessionManager') !== false || strpos($namespace, 'TeamManager') !== false || strpos($namespace, 'KitsManager') !== false) {
            return new $namespace();
        } elseif (strpos($namespace, 'GameManager') !== false || strpos($namespace, 'Handler') !== false) {
            return new $namespace($this, $this->getClass('Border'));
        } elseif (strpos($namespace, 'Border') !== false) {
            return new $namespace($this->getServer()->getLevelByName($this->map));
        } elseif (strpos($namespace, 'DataConnector') !== false) {
            return $this->data;
        } elseif (strpos($namespace, 'Database') !== false) {
            return new Database($this);
        }
        return new $namespace($this);
    }

    /**
     * getDirContents
     * 
     * had to keep this here due to complications
     * (try to move it to Directory in util if you want)
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
            $path = (string)realpath($dir . DIRECTORY_SEPARATOR . $value); 

            if (!is_dir($path)) {
                if (empty($filter) || preg_match($filter, $path)) $results[] = $path;
            } elseif ($value != "." && $value != "..") {
                $this->getDirContents($path, $filter, $results);
            }
        }
        return $results;
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
    
    /**
     * isPhar
     *
     * @return bool
     */
    public function isPhar(): bool
    {
        return parent::isPhar();
    }
    
    /**
     * getFile
     *
     * @return string
     */
    public function getFile(): string
    {
        return parent::getFile();
    }
}
