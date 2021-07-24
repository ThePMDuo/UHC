<?php
declare(strict_types=1);

namespace AGTHARN\uhc\util\virion;

use pocketmine\scheduler\AsyncTask;

class RegisterClassLoaderAsyncTask extends AsyncTask
{
	/** @var VirionClassLoader */
	private $classLoader;
	
	/**
	 * __construct
	 *
	 * @param  VirionClassLoader $classLoader
	 * @return void
	 */
	public function __construct(VirionClassLoader $classLoader)
    {
		$this->classLoader = $classLoader;
	}
	
	/**
	 * onRun
	 *
	 * @return void
	 */
	public function onRun(): void
    {
		$this->classLoader->register(true);
	}
}