<?php

namespace Clasyc\Bundle\SwaggerGeneratorBundle\libs\YmlGenerator;

use Symfony\Component\Yaml\Yaml;

class YmlGenerator
{
    const AT_BEGINNING = 0;
    const AT_END = 1;


    private $data = array();

    public function __construct(){

    }


    public function addOption($option, $value = NULL, $parent = NULL, $insertType = self::AT_END){
        if($parent == NULL){
            $this->data[$option] = $value;
        }else{
            if (!$returned = & $this->array_key_exists_recursive($parent, $this->data)) {
                throw new YmlException("Parent '$parent' does not exist.");
            } else{
                $returned[$parent][$option] = $value;
            }
        }
    }


    public function getOutput()
    {
        return Yaml::dump($this->data);
    }


    private function & array_key_exists_recursive($key, &$array)
    {
        if (!is_array($array)) return false;

        if (array_key_exists($key, $array)) return $array;

        foreach($array as $key => $val)
        {
            if (array_key_exists($key, $array)) return $array;
        }

        return false;
    }


}