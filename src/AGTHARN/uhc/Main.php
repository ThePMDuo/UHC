<?php
declare(strict_types=1);

namespace AGTHARN\uhc;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as TF;
use pocketmine\Player;

use AGTHARN\uhc\command\SpectatorCommand;
use AGTHARN\uhc\game\GameManager;
use AGTHARN\uhc\game\Scenario;

class Main extends PluginBase
{   
    /** @var int */
    public $uhcServer = 0001;
    /** @var int */
    public $buildNumber = 1;
    /** @var bool */
    public $operational;

    /** @var GameManager */
    private $gameManager;

    /** @var Player[] */
    private $gamePlayers = [];

    /** @var PlayerSession[] */
    private $sessions = [];

    /** @var bool */
    private $globalMuteEnabled = false;
    
    /** @var Scenario[] */
    private $scenarios = [];
    
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
        
        $this->getServer()->loadLevel("UHC");
        
        $this->gameManager = new GameManager($this);
        $this->getScheduler()->scheduleRepeatingTask($this->gameManager, 20);

        $this->getServer()->getCommandMap()->registerAll("uhc", [
            new SpectatorCommand($this)
        ]);
        $this->loadScenarios();

        foreach ($this->getServer()->getPluginManager()->getPlugins() as $plugin) {
            if ($plugin->isEnabled()) {
                $this->setOperational(true);
            } else {
                $this->setOperational(false);
            }
        }
    }
    
    /**
     * loadScenarios
     *
     * @return void
     */
    public function loadScenarios(): void
    {
        $dir = scandir($this->getDataFolder() . "scenarios");
        if (is_array($dir)) {
            foreach ($dir as $file) {
                $fileLocation = $this->getDataFolder() . "scenarios/" . $file;
                if (substr($file, -4) === ".php") {
                    require($fileLocation);
                    $class = "\\" . str_replace(".php", "", $file);
                    if (($scenario = new $class($this)) instanceof Scenario) {
                        $this->addScenario($scenario);
                    }
                }
            }
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
     * addToGame
     *
     * @param  Player $player
     * @return void
     */
    public function addToGame(Player $player): void
    {
        if (!isset($this->gamePlayers[$player->getUniqueId()->toString()])) {
            $this->gamePlayers[$player->getUniqueId()->toString()] = $player;
        }
    }
    
    /**
     * removeFromGame
     *
     * @param  Player $player
     * @return void
     */
    public function removeFromGame(Player $player): void
    {
        if (isset($this->gamePlayers[$player->getUniqueId()->toString()])) {
            unset($this->gamePlayers[$player->getUniqueId()->toString()]);
        }
    }
    
    /**
     * getGamePlayers
     *
     * @return array
     */
    public function getGamePlayers(): array
    {
        return $this->gamePlayers;
    }
    
    /**
     * isInGame
     *
     * @param  Player $player
     * @return bool
     */
    public function isInGame(Player $player): bool
    {
        return isset($this->gamePlayers[$player->getUniqueId()->toString()]);
    }
    
    /**
     * addSession
     *
     * @param  PlayerSession $session
     * @return void
     */
    public function addSession(PlayerSession $session): void
    {
        if (!isset($this->sessions[$session->getUniqueId()->toString()])) {
            $this->sessions[$session->getUniqueId()->toString()] = $session;
        }
    }
    
    /**
     * removeSession
     *
     * @param  PlayerSession $session
     * @return void
     */
    public function removeSession(PlayerSession $session): void
    {
        if (isset($this->sessions[$session->getUniqueId()->toString()])) {
            unset($this->sessions[$session->getUniqueId()->toString()]);
        }
    }
    
    /**
     * hasSession
     *
     * @param  Player $player
     * @return bool
     */
    public function hasSession(Player $player): bool
    {
        return isset($this->sessions[$player->getUniqueId()->toString()]);
    }
    
    /**
     * getSessions
     *
     * @return array
     */
    public function getSessions(): array
    {
        return $this->sessions;
    }
    
    /**
     * getSession
     *
     * @param  Player $player
     * @return PlayerSession
     */
    public function getSession(Player $player): ?PlayerSession
    {
        return $this->hasSession($player) ? $this->sessions[$player->getUniqueId()->toString()] : null;
    }
    
    /**
     * getScenarios
     *
     * @return array
     */
    public function getScenarios(): array
    {
        return $this->scenarios;
    }
    
    /**
     * getScenario
     *
     * @param  string $scenarioName
     * @return Scenario
     */
    public function getScenario(string $scenarioName): Scenario
    {
        return $this->scenarios[$scenarioName];
    }
    
    /**
     * addScenario
     *
     * @param  Scenario $scenario
     * @return void
     */
    public function addScenario(Scenario $scenario): void
    {
        $this->scenarios[$scenario->getName()] = $scenario;
    }
    
    /**
     * setOperational
     *
     * @param  bool $operational
     * @return void
     */
    public function setOperational(bool $operational): void {
        if ($this->getOperational()) {
            $this->getOperational() = $operational;
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
