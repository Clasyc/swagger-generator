<?php

namespace Clasyc\Bundle\SwaggerGeneratorBundle\Services;

class DirectoryFinder
{
    private $find;

    public function __construct(){
    }

    public function findBundlesDirectory($dir, array $find){
        $this->find = $find;
        return $this->findBundlePath($dir, $find);
    }

    private function findBundlePath($dir, $find, &$results = array()){
        $files = scandir($dir);

        foreach($files as $key => $value){
            $path = realpath($dir.DIRECTORY_SEPARATOR.$value);
            if(!is_dir($path)) {
                if(preg_match('/('.$this->regexBundles().')$/', $path)){
                    $results[] = $path;
                }
            } else if($value != "." && $value != "..") {
                $this->findBundlePath($path, $find ,$results);
                if(preg_match('/('.$this->regexBundles().')$/', $path)){
                    $results[] = $path;
                }
            }
        }
        return $results;
    }

    private function regexBundles(){
        $firstElement = true;
        $regex = '';
        foreach($this->find as $string){
            if($firstElement){
                $firstElement = false;
                $regex .= $string;
            }else{
                $regex .= '|'.$string;
            }
        }
        return $regex;
    }
}