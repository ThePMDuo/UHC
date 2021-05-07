<?php
declare(strict_types=1);

namespace AGTHARN\uhc\util;

class Directory
{       
    /**
     * getDirContents
     *
     * @param  mixed $dir
     * @param  mixed $filter
     * @param  mixed $results
     * @return mixed
     */
    function getDirContents($dir, $filter = '', &$results = array()): mixed
    {
        $files = scandir($dir);
        foreach ($files as $key => $value) {
            $path = realpath($dir.DIRECTORY_SEPARATOR.$value); 
            if (!is_dir($path)) {
                if (empty($filter) || preg_match($filter, $path)) {
                    $results[] = $path;
                }
            } elseif ($value != "." && $value != "..") {
                $this->getDirContents($path, $filter, $results);
            }
        }
        return $results;
    }   
}
