<?php

// src/Clasyc/Bundle/SwaggerGeneratorBundle/Services/String

namespace Clasyc\Bundle\SwaggerGeneratorBundle\Services;

class StringManager
{
    public function __construct()
    {
    }


    public function combineToString(array $names){
        $joined = '';
        foreach($names as $name){
            $joined .= ucfirst($name);
        }
        return $joined;
    }


    public function getNameFromPath($path){
        $array = array();
        $exploded = explode('/', $path);
        foreach($exploded as $part){
            preg_match('/{(.*?)}/', $part, $matched);

            if(!isset($matched[1])){
                ($exploded[1] == $part) ? : $array['names'][] = $part;
            }else if(isset($matched[1])){
                $array['params'][] = $matched[1];
            }
        }
        if(isset($array['params'])){
            $array['names'][] = $this->combineParametersToString($array['params']);
        }
        return $array;
    }

    public function getAllNames($path){
        $array = array();
        $exploded = explode('/', $path);
        foreach($exploded as $part){
            preg_match('/{(.*?)}/', $part, $matched);
            (!isset($matched[1])) ? $array[] = $part : $array[] = $matched[1];
        }
        return $array;
    }

    public function routeToDashString($route){
        return implode("-", $this->getAllNames($route));
    }


    public function combineParametersToString(array $params){
        $string = '';
        $size = count($params);
        for($i = 0; $i < $size; $i++){
            ($i == 0) ? $by = "By" : $by = "";
            ($i == ($size-1)) ? $and = "" : $and = "And";
            $string .= $by.ucfirst($params[$i]).$and;
        }
        return $string;
    }
}