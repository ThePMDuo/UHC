<?php
declare(strict_types=1);

namespace AGTHARN\uhc\util;

use AGTHARN\uhc\Main;

class Directory
{
    /** @var Main */
    private $plugin;

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
     * @param  string $directory
     * @param  callable $callable
     * @return void
     */
    public function callDirectory(string $directory, callable $callable): void
    {
        $dirname = $this->getPath();
        $path = $dirname . DIRECTORY_SEPARATOR . $directory;
        $path = str_replace(["phar:///", "phar://", "//", "phar:\\\\", "\\"], ["phar:\\\\/", "phar:\\\\", "/", "phar://", "/"], $path);
        $phar = $this->plugin->isPhar();

        foreach(array_diff(scandir($path), [".", ".."]) as $file){
            if(is_dir($path . DIRECTORY_SEPARATOR . $file)){
                $this->callDirectory($directory . DIRECTORY_SEPARATOR . $file, $callable);
            }else{
                $i = explode(".", $file);
                $extension = $i[count($i) - 1];

                if($extension === "php"){
                    $name = $i[0];
                    $namespace = "";
                    $i = explode(DIRECTORY_SEPARATOR, str_replace(getcwd() . DIRECTORY_SEPARATOR, "", $dirname));
                    for($v = 0; $v <= ($phar ? 1 : 2); $v++){
                        unset($i[$v]);
                    }
                    foreach($i as $key => $string){
                        $namespace .= $string . DIRECTORY_SEPARATOR;
                    }
                    $namespace .= $directory . DIRECTORY_SEPARATOR . $name;
                    $namespace = str_replace("/", "\\", $namespace);
                    if(($pos = strpos($namespace, "src\\")) !== false){
                        $namespace = substr($namespace, $pos + 4);
                    }
                    $callable($namespace);
                }
            }
        }
    }
    
    /**
     * getPath
     *
     * @return string
     */
    public function getPath(): string
    {
        $path = $this->removeLastDirectory($this->plugin->getDescription()->getMain());
        $path = $this->plugin->getFile() . "src" . DIRECTORY_SEPARATOR . $path;
        return $path;
    }
    
    /**
     * removeLastDirectory
     *
     * @param  string $str
     * @param  int $loop
     * @return string
     */
    public function removeLastDirectory(string $str, int $loop = 1): string
    {
        $delimiter = strpos($str, DIRECTORY_SEPARATOR) ? DIRECTORY_SEPARATOR : "\\";

        for($i = 0; $i < $loop; $i++){
            $exp = explode($delimiter, $str);
            unset($exp[array_key_last($exp)]);
            $str = implode($delimiter, $exp);
        }
        return $str;
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
            } elseif ($value != "." && $value != "..") {
                $this->getDirContents($path, $filter, $results);
            }
        }
        return $results;
    } 
}