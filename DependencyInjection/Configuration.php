<?php

namespace Clasyc\Bundle\SwaggerGeneratorBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('swagger_generator');

        $rootNode
            ->children()
                ->scalarNode('bundle')->end()
                ->scalarNode('definition_path')->end()
                ->scalarNode('route')->end()
                ->scalarNode('responses')->end()
            ->end()
        ;

        return $treeBuilder;
    }
}