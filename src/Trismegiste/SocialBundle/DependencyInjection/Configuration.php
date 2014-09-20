<?php

namespace Trismegiste\SocialBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('trismegiste_social');

        $rootNode->children()
                    ->scalarNode('nickname_regex')->end()
                    ->scalarNode('pagination')->defaultValue(20)->end()
                    ->scalarNode('avatar_size')->defaultValue(300)->end()
                    ->arrayNode('alias')
                        ->children()
                            ->scalarNode('user')->end()
                            ->arrayNode('content')
                                ->requiresAtLeastOneElement()
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end();

        return $treeBuilder;
    }
}
