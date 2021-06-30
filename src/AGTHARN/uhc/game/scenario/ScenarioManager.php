<?php
declare(strict_types=1);

namespace AGTHARN\uhc\game\scenario;

use AGTHARN\uhc\game\scenario\type\Scenario;
use AGTHARN\uhc\Main;

use Throwable;

class ScenarioManager
{
    /** @var Main */
    private $plugin;
    /** @var Scenario[] */
    private $registeredScenarios = [];
    
    /**
     * __construct
     *
     * @param  Main $plugin
     * @return void
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
        $this->loadDirectoryScenarios($plugin->getDataFolder() . 'scenarios/');
    }
    
    /**
     * loadDirectoryScenarios
     *
     * @param  string $directory
     * @return void
     */
    public function loadDirectoryScenarios(string $directory): void
    {
        if (!is_dir($directory)) {
            mkdir($directory);
        }
        $dir = scandir($directory);
        if (is_array($dir)) {
            foreach ($dir as $file) {
                if (substr($file, -4) === '.php') {
                    $fileLocation = $directory . $file;
                    try {
                        require($fileLocation);
                        $class = '\\' . str_replace('.php', '', $file);
                        if (($scenario = new $class($this->plugin)) instanceof Scenario) {
                            $this->registerScenario($scenario);
                        }
                    } catch (Throwable $error) {
                        $this->plugin->getLogger()->error('File $file failed to load with reason: ' . $error->getMessage());
                    }
                }
            }
        }
    }

    /**
     * getScenarios
     *
     * @return array
     */
    public function getScenarios(): array
    {
        return $this->registeredScenarios;
    }
    
    /**
     * getScenarioByName
     *
     * @param  string $name
     * @return Scenario
     */
    public function getScenarioByName(string $name) : ?Scenario
    {
        return isset($this->registeredScenarios[$name]) ? $this->registeredScenarios[$name] : null;
    }
    
    /**
     * registerScenario
     *
     * @param  Scenario $scenario
     * @return void
     */
    public function registerScenario(Scenario $scenario): void
    {
        if (isset($this->registeredScenarios[$scenario->getName()])) {
            $this->plugin->getLogger()->notice('Ignored duplicate scenario: {$scenario->getName()}');
        }
        $this->registeredScenarios[$scenario->getName()] = $scenario;
    }
    
    /**
     * unregisterScenario
     *
     * @param  Scenario $scenario
     * @return void
     */
    public function unregisterScenario(Scenario $scenario): void
    {
        unset($this->registeredScenarios[$scenario->getName()]);
    }
}