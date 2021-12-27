<?php
declare(strict_types=1);

/**
 * ███╗░░░███╗██╗███╗░░██╗███████╗██╗░░░██╗██╗░░██╗░█████╗░
 * ████╗░████║██║████╗░██║██╔════╝██║░░░██║██║░░██║██╔══██╗
 * ██╔████╔██║██║██╔██╗██║█████╗░░██║░░░██║███████║██║░░╚═╝
 * ██║╚██╔╝██║██║██║╚████║██╔══╝░░██║░░░██║██╔══██║██║░░██╗
 * ██║░╚═╝░██║██║██║░╚███║███████╗╚██████╔╝██║░░██║╚█████╔╝
 * ╚═╝░░░░░╚═╝╚═╝╚═╝░░╚══╝╚══════╝░╚═════╝░╚═╝░░╚═╝░╚════╝░
 * 
 * Copyright (C) 2020-2021 AGTHARN
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace AGTHARN\uhc\util;

use AGTHARN\uhc\Main;

class Directory
{
    /** @var Main */
    private Main $plugin;

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
     * callDirectory
     * 
     * (Code extracted from VanillaX)
     *
     * @param  string $directory
     * @param  bool $addPluginPath
     * @param  callable $callable
     * @return void
     */
    public function callDirectory(string $directory, bool $addPluginPath, callable $callable): void
    {
        $main = explode("\\", $this->plugin->getDescription()->getMain());
        unset($main[array_key_last($main)]);
        $main = implode("/", $main);
        $directory = rtrim(str_replace(DIRECTORY_SEPARATOR, "/", $directory), "/");
        $dir = $addPluginPath ? $this->plugin->getFile() . "src/$main/" . $directory : $directory;

        foreach (array_diff(scandir($dir), [".", ".."]) as $file) {
            $path = $dir . "/$file";
            $extension = pathinfo($path)["extension"] ?? null;

            if ($extension === null) {
                $this->callDirectory($directory . "/" . $file, true, $callable);
            } elseif ($extension === "php") {
                $namespaceDirectory = str_replace("/", "\\", $directory);
                $namespaceMain = str_replace("/", "\\", $main);
                $namespace = $namespaceMain . "\\$namespaceDirectory\\" . basename($file, ".php");
                $callable($namespace, $directory);
            }
        }
    }
    
    /**
     * removeDir
     * 
     * (Code extracted from VanillaX)
     *
     * @param  string $dirPath
     * @return int
     */
    public function removeDir(string $dirPath): int 
    {
        $files = 1;
        if (basename($dirPath) == '.' || basename($dirPath) == '..' || !is_dir($dirPath)) {
            return 0;
        }
        foreach (scandir($dirPath) as $item) {
            if ($item != '.' || $item != '..') {
                if (is_dir($dirPath . DIRECTORY_SEPARATOR . $item)) {
                    $files += $this->removeDir($dirPath . DIRECTORY_SEPARATOR . $item);
                }
                if (is_file($dirPath . DIRECTORY_SEPARATOR . $item)) {
                    $files += $this->removeFile($dirPath . DIRECTORY_SEPARATOR . $item);
                }
            }
        }
        rmdir($dirPath);
        return $files;
    }

    /**
     * removeFile
     * 
     * (Code extracted from VanillaX)
     *
     * @param  string $path
     * @return int
     */
    public function removeFile(string $path): int
    {
        unlink($path);
        return 1;
    }

    /**
     * getDirContents
     *
     * @param  string $dir
     * @param  string $filter
     * @param  array $results
     * @return array
     */
    public function getDirContents(string $dir, string $filter = '', array &$results = array()): array
    {
        $files = preg_grep('/^([^.])/', (array)scandir($dir));

        foreach ($files as $key => $value) {
            $path = (string)realpath($dir.DIRECTORY_SEPARATOR.$value); 

            if (!is_dir($path)) {
                if (empty($filter) || preg_match($filter, $path)) $results[] = $path;
            } elseif ($value != '.' && $value != '..') {
                $this->getDirContents($path, $filter, $results);
            }
        }
        return $results;
    } 
}