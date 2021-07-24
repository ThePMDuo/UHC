<?php
declare(strict_types=1);

namespace AGTHARN\uhc\util\virion;

use BaseClassLoader;
use ClassLoader;
use Threaded;

class VirionClassLoader extends BaseClassLoader
{
    /** @var Threaded|string[] */
	private $messages;

	/** @var Threaded|string[] */
	private $antigenMap;
	/** @var Threaded|string[] */
	private $mappedClasses;
	
	/**
	 * __construct
	 *
	 * @param  ClassLoader|null $parent
	 * @return void
	 */
	public function __construct(ClassLoader $parent = null)
    {
		parent::__construct($parent);
		$this->messages = new Threaded;
		$this->antigenMap = new Threaded;
		$this->mappedClasses = new Threaded;
	}
	
	/**
	 * addAntigen
	 *
	 * @param  string $antigen
	 * @param  string $path
	 * @return void
	 */
	public function addAntigen(string $antigen, string $path): void
    {
		$this->antigenMap[$path] = $antigen;
	}
	
	/**
	 * getKnownAntigens
	 *
	 * @return array
	 */
	public function getKnownAntigens(): array
    {
		$antigens = [];
		foreach($this->antigenMap as $antigen){
			$antigens[] = $antigen;
		}
		return $antigens;
	}
	
	/**
	 * findClass
	 *
	 * @param  mixed $class
	 * @return string|null
	 */
	public function findClass($class): ?string
    {
		$baseName = str_replace("\\", DIRECTORY_SEPARATOR, $class);
		foreach($this->antigenMap as $path => $antigen){
			if(stripos($class, $antigen) === 0){
				if(PHP_INT_SIZE === 8 and file_exists($path . DIRECTORY_SEPARATOR . $baseName . "__64bit.php")){
					$this->mappedClasses[$class] = $antigen;
					return $path . DIRECTORY_SEPARATOR . $baseName . "__64bit.php";
				}

				if(PHP_INT_SIZE === 4 and file_exists($path . DIRECTORY_SEPARATOR . $baseName . "__32bit.php")){
					$this->mappedClasses[$class] = $antigen;
					return $path . DIRECTORY_SEPARATOR . $baseName . "__32bit.php";
				}

				if(file_exists($path . DIRECTORY_SEPARATOR . $baseName . ".php")){
					$this->mappedClasses[$class] = $antigen;
					return $path . DIRECTORY_SEPARATOR . $baseName . ".php";
				}

				$this->messages[] = "DEVirion detected an attempt to load class $class, matching a known antigen but does not exist. Please note that this reference might be shaded in virion building and may fail to load.\n";
			}
		}

		return null;
	}
	
	/**
	 * loadClass
	 *
	 * @param  mixed $name
	 * @return bool|null
	 */
	public function loadClass($name): ?bool
    {
		try{
			return parent::loadClass($name);
		}catch(\ClassNotFoundException $e){
			return null;
		}
	}
	
	/**
	 * getSourceViralAntigen
	 *
	 * @param  string $loadedClass
	 * @return string|null
	 */
	public function getSourceViralAntigen(string $loadedClass): ?string
    {
		return $this->mappedClasses[$loadedClass] ?? null;
	}
	
	/**
	 * getMessages
	 *
	 * @return Threaded
	 */
	public function getMessages(): Threaded
    {
		return $this->messages;
	}
}