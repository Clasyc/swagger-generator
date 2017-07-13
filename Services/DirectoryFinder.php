<?php

namespace Clasyc\Bundle\SwaggerGeneratorBundle\Services;

class DirectoryFinder
{
    public function __construct(){
    }

    public function findBundlesDirectory($dir, $find){
        return $this->findBundlePath($dir, $find);
    }

    private function findBundlePath($dir, $find, &$results = array()){
        $files = scandir($dir);

        foreach($files as $key => $value){
            $path = realpath($dir.DIRECTORY_SEPARATOR.$value);
            if(!is_dir($path)) {
                if(preg_match('/('.$find.')$/', $path)){
                    $results[] = $path;
                }
            } else if($value != "." && $value != "..") {
                $this->findBundlePath($path, $find ,$results);
                if(preg_match('/('.$find.')$/', $path)){
                    $results[] = $path;
                }
            }
        }
        return $results;
    }
}