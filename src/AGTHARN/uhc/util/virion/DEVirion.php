<?php
declare(strict_types=1);

namespace AGTHARN\uhc\util\virion;

use pocketmine\scheduler\Task;

use AGTHARN\uhc\Main;

class DEVirion
{
    /** @var Main */
    private $plugin;
	/** @var VirionClassLoader */
	private $classLoader;

    /**
     * __construct
     *
     * @param  Main $plugin
     * @return void
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }
        
    /**
     * virionLoad
     *
     * @return void
     */
    public function virionLoad(): void
    {
		$this->classLoader = new VirionClassLoader($this->plugin->getServer()->getLoader());

		$dirs = [$this->plugin->getServer()->getDataPath() . "plugins/UHC/src/AGTHARN/uhc/virion/"];
		foreach((array) (getopt("", ["load-virions::"])["load-virions"] ?? []) as $path){
			$dirs[] = $path;
		}
		foreach($dirs as $dir){
			if(!is_dir($dir)){
				@mkdir($dir);
			}
			$directory = dir($dir);
			while(is_string($file = $directory->read())){
				if(is_dir($dir . $file) and $file !== "." and $file !== ".."){
					$path = $dir . rtrim($file, "\\/") . "/";
				}elseif(is_file($dir . $file) && substr($file, -5) === ".phar"){
					$path = "phar://" . rtrim(str_replace(DIRECTORY_SEPARATOR, "/", realpath($dir . $file)), "/") . "/";
				}else{
					continue;
				}
				$this->loadVirion($path);
			}
			$directory->close();
		}

		foreach((array) (getopt("", ["load-virion::"])["load-virion"] ?? []) as $path){
			$this->loadVirion($path, true);
		}

		if(count($this->classLoader->getKnownAntigens()) > 0){
			$this->classLoader->register(true);
			$size = $this->plugin->getServer()->getAsyncPool()->getSize();
			for($i = 0; $i < $size; $i++){
				$this->plugin->getServer()->getAsyncPool()->submitTaskToWorker(new RegisterClassLoaderAsyncTask($this->classLoader), $i);
			}
		}
	}
	
	/**
	 * virionEnable
	 *
	 * @return void
	 */
	public function virionEnable(): void
    {
		if(count($this->classLoader->getKnownAntigens()) > 0){
			$this->plugin->getScheduler()->scheduleRepeatingTask(new class($this) extends Task{
				/** @var DEVirion */
				private $plugin;

				public function __construct(DEVirion $plugin){
					$this->plugin = $plugin;
				}

				public function onRun(int $currentTick) : void{
					$messages = $this->plugin->getVirionClassLoader()->getMessages();
					while($messages->count() > 0){
						$this->plugin->getLogger()->warning($messages->shift());
					}
				}
			}, 1);
		}
	}
    
    /**
     * loadVirion
     *
     * @param  string $path
     * @param  bool $explicit
     * @return void
     */
    public function loadVirion(string $path, bool $explicit = false): void
	{
		if(!is_file($path . "virion.yml")){
			if($explicit){
				$this->plugin->getLogger()->error("Cannot load virion: virion.yml missing");
			}
			return;
		}
		$data = yaml_parse(file_get_contents($path . "virion.yml"));
		if(!is_array($data)){
			$this->plugin->getLogger()->error("Cannot load virion: Error parsing {$path}virion.yml");
			return;
		}
		if(!isset($data["name"])){
			$this->plugin->getLogger()->error("Cannot load virion: Attribute 'name' missing in {$path}virion.yml");
			return;
		}
		$name = $data["name"];
		$authors = [];
		if(isset($data["author"])){
			$authors[] = $data["author"];
		}
		if(isset($data["authors"])){
			$authors = array_merge($authors, (array) $data["authors"]);
		}
		if(!isset($data["version"])){
			$this->plugin->getLogger()->error("Cannot load virion $name: Attribute 'version' missing in {$path}virion.yml");
			return;
		}
		$virionVersion = $data["version"];
		if(!isset($data["antigen"])){
			$this->plugin->getLogger()->error("Cannot load virion $name: Attribute 'antigen' missing in {$path}virion.yml");
			return;
		}
		if(isset($data["php"])){
			foreach((array) $data["php"] as $php){
				$parts = array_map("intval", array_pad(explode(".", (string) $php), 2, "0"));
				if($parts[0] !== PHP_MAJOR_VERSION){
					continue;
				}
				if($parts[1] <= PHP_MINOR_VERSION){
					$ok = true;
					break;
				}
			}
			if(!isset($ok) and count((array) $data["php"]) > 0){
				$this->plugin->getLogger()->error("Cannot load virion $name: Server is using incompatible PHP version " . PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION);
				return;
			}
		}
		if(isset($data["api"])){
			$compatible = false;
			foreach((array) $data["api"] as $version){
				$version = (string) $version;
				//Format: majorVersion.minorVersion.patch (3.0.0)
				//    or: majorVersion.minorVersion.patch-devBuild (3.0.0-alpha1)
				if($version !== $this->plugin->getServer()->getApiVersion()){
					$virionApi = array_pad(explode("-", $version), 2, ""); //0 = version, 1 = suffix (optional)
					$serverApi = array_pad(explode("-", $this->plugin->getServer()->getApiVersion()), 2, "");

					if(strtoupper($virionApi[1]) !== strtoupper($serverApi[1])){ //Different release phase (alpha vs. beta) or phase build (alpha.1 vs alpha.2)
						continue;
					}

					$virionNumbers = array_map("intval", explode(".", $virionApi[0]));
					$serverNumbers = array_map("intval", explode(".", $serverApi[0]));

					if($virionNumbers[0] !== $serverNumbers[0]){ //Completely different API version
						continue;
					}

					if($virionNumbers[1] > $serverNumbers[1]){ //If the plugin requires new API features, being backwards compatible
						continue;
					}
				}

				$compatible = true;
				break;
			}

			if($compatible === false){
				$this->plugin->getLogger()->error("Cannot load virion $name: Server has incompatible API version {$this->plugin->getServer()->getApiVersion()}");
				return;

			}
		}

		if(!isset($data["api"]) && !isset($data["php"])){
			$this->plugin->getLogger()->error("Cannot load virion $name: Either 'api' or 'php' attribute must be declared in {$path}virion.yml");
			return;
		}

		$antigen = $data["antigen"];

		$this->plugin->getLogger()->info("Loading virion $name v$virionVersion by " . implode(", ", $authors) . " (antigen: $antigen)");

		$this->classLoader->addAntigen($antigen, $path . "src/");
	}
	
	/**
	 * getVirionClassLoader
	 *
	 * @return VirionClassLoader
	 */
	public function getVirionClassLoader(): VirionClassLoader
    {
		return $this->classLoader;
	}
}
