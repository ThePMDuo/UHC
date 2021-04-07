<?php
declare(strict_types=1);

namespace AGTHARN\uhc;

use pocketmine\level\generator\GeneratorManager;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as TF;
use pocketmine\Player;

use AGTHARN\uhc\command\SpectatorCommand;
use AGTHARN\uhc\game\scenario\ScenarioManager;
use AGTHARN\uhc\game\team\TeamManager;
use AGTHARN\uhc\game\GameManager;

class Main extends PluginBase
{   
    /** @var int */
    public $uhcServer = 1;
    /** @var int */
    public $buildNumber = 1;
    /** @var bool */
    public $operational;
    /** @var int */
    public $seed;

    /** @var GameManager */
    private $gameManager;

    /** @var Player[] */
    private $gamePlayers = [];

    /** @var PlayerSession[] */
    private $sessions = [];
    /** @var TeamManager */
    private TeamManager $teamManager;

    /** @var bool */
    private $globalMuteEnabled = false;
    
    /** @var ScenarioManager */
    private $scenarioManager;
    
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
        $this->getScheduler()->scheduleRepeatingTask($this->gameManager, 20);

        $this->getServer()->getCommandMap()->registerAll("uhc", [
            new SpectatorCommand($this)
        ]);
        $this->scenarioManager = new ScenarioManager($this);

        foreach ($this->getServer()->getPluginManager()->getPlugins() as $plugin) {
            if ($plugin->isEnabled()) {
                $this->setOperational(true);
            } else {
                $this->setOperational(false);
            }
        }
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
        
        if ($this->getServer()->isLevelGenerated($level)) {
            $this->getServer()->unloadLevel($level, true);
            rmdir($level->getProvider()->getPath());
        } else {
            $betterGen = $this->getServer()->getPluginManager()->getPlugin("BetterGen");
            
            $this->seed = $betterGen->generateRandomSeed();

            $generator = GeneratorManager::getGenerator("betternormal");
            $generatorName = "betternormal";

            if ((int)$this->seed === 0) {
                $this->seed = $this->generateRandomSeed();
            }
            $this->getServer()->generateLevel($levelName, $this->seed, $generator, []);
            $this->getServer()->loadLevel($level);
        }
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
    
    /**
     * getTeamManager
     *
     * @return TeamManager
     */
    public function getTeamManager(): TeamManager
    {
        return $this->teamManager;
    }
}
