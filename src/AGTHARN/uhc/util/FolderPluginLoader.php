<?php

/*
 * DevTools plugin for PocketMine-MP
 * Copyright (C) 2014 PocketMine Team <https://github.com/PocketMine/DevTools>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
*/

declare(strict_types=1);

namespace AGTHARN\uhc\util;

use pocketmine\plugin\PluginDescription;
use pocketmine\plugin\PluginLoader;

use function file_exists;
use function file_get_contents;
use function is_dir;

class FolderPluginLoader implements PluginLoader
{
    /** @var \ClassLoader */
	private $loader;
	
	/**
	 * __construct
	 *
	 * @param  ClassLoader $loader
	 * @return void
	 */
	public function __construct(\ClassLoader $loader)
    {
		$this->loader = $loader;
	}
	
	/**
	 * canLoadPlugin
	 *
	 * @param  string $path
	 * @return bool
	 */
	public function canLoadPlugin(string $path): bool
    {
		return is_dir($path) and file_exists($path . "/plugin.yml") and file_exists($path . "/src/");
	}
	
	/**
	 * loadPlugin
	 *
	 * @param  string $file
	 * @return void
	 */
	public function loadPlugin(string $file): void
    {
		$this->loader->addPath("$file/src");
	}
	
	/**
	 * getPluginDescription
	 *
	 * @param  string $file
	 * @return PluginDescription
	 */
	public function getPluginDescription(string $file): ?PluginDescription
    {
		if(is_dir($file) and file_exists($file . "/plugin.yml")){
			$yaml = @file_get_contents($file . "/plugin.yml");
			if($yaml != ""){
				return new PluginDescription($yaml);
			}
		}

		return null;
	}
	
	/**
	 * getAccessProtocol
	 *
	 * @return string
	 */
	public function getAccessProtocol(): string
    {
		return "";
	}
}
