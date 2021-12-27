<?php
declare(strict_types=1);

namespace AGTHARN\uhc\util\virion;

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
		$this->classLoader = new VirionClassLoader();

		$dirs = [$this->plugin->getServer()->getDataPath() . 'plugins/UHC/src/AGTHARN/uhc/virion/'];
		foreach ((array) (getopt('', ['load-virions::'])['load-virions'] ?? []) as $path) {
			$dirs[] = $path;
		}
		foreach ($dirs as $dir) {
			if (!is_dir($dir)) {
				@mkdir($dir);
			}
			$directory = dir($dir);
			while (is_string($file = $directory->read())) {
				if (is_dir($dir . $file) and $file !== '.' and $file !== '..') {
					$path = $dir . rtrim($file, '\\/') . '/';
				} elseif (is_file($dir . $file) && substr($file, -5) === '.phar') {
					$path = 'phar://' . rtrim(str_replace(DIRECTORY_SEPARATOR, '/', realpath($dir . $file)), '/') . '/';
				} else {
					continue;
				}
				$this->loadVirion($path);
			}
			$directory->close();
		}

		foreach ((array) (getopt('', ['load-virion::'])['load-virion'] ?? []) as $path) {
			$this->loadVirion($path, true);
		}

		if (count($this->classLoader->getKnownAntigens()) > 0) {
			$this->classLoader->register(true);
			$size = $this->plugin->getServer()->getAsyncPool()->getSize();
			for ($i = 0; $i < $size; $i++) {
				$this->plugin->getServer()->getAsyncPool()->submitTaskToWorker(new RegisterClassLoaderAsyncTask($this->classLoader), $i);
			}
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
		if (!is_file($path . 'virion.yml')) {
			return;
		}
		$data = yaml_parse(file_get_contents($path . 'virion.yml'));
		if (!is_array($data)) {
			return;
		}
		if (!isset($data['name'])) {
			return;
		}
		$name = $data['name'];
		$authors = [];
		if (isset($data['author'])) {
			$authors[] = $data['author'];
		}
		if (isset($data['authors'])) {
			$authors = array_merge($authors, (array) $data['authors']);
		}
		if (!isset($data['version'])) {
			return;
		}
		$virionVersion = $data['version'];
		if (!isset($data['antigen'])) {
			return;
		}
		if (isset($data['php'])) {
			foreach ((array) $data['php'] as $php) {
				$parts = array_map('intval', array_pad(explode('.', (string) $php), 2, '0'));
				if ($parts[0] !== PHP_MAJOR_VERSION) {
					continue;
				}
				if ($parts[1] <= PHP_MINOR_VERSION) {
					$ok = true;
					break;
				}
			}
			if (!isset($ok) and count((array) $data['php']) > 0) {
				return;
			}
		}
		if (isset($data['api'])) {
			$compatible = false;
			foreach ((array) $data['api'] as $version) {
				$version = (string) $version;
				if ($version !== $this->plugin->getServer()->getApiVersion()) {
					$virionApi = array_pad(explode('-', $version), 2, '');
					$serverApi = array_pad(explode('-', $this->plugin->getServer()->getApiVersion()), 2, '');

					if (strtoupper($virionApi[1]) !== strtoupper($serverApi[1])) {
						continue;
					}

					$virionNumbers = array_map('intval', explode('.', $virionApi[0]));
					$serverNumbers = array_map('intval', explode('.', $serverApi[0]));

					if ($virionNumbers[0] !== $serverNumbers[0]) {
						continue;
					}
					if ($virionNumbers[1] > $serverNumbers[1]) {
						continue;
					}
				}
				$compatible = true;
				break;
			}
			if($compatible === false){
				return;
			}
		}

		if (!isset($data['api']) && !isset($data['php'])) {
			return;
		}
		$antigen = $data['antigen'];
		$this->classLoader->addAntigen($antigen, $path . 'src/');
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
