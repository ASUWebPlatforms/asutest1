<?php

namespace Drupal\layout_builder_st;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\layout_builder_st\EventSubscriber\SetInlineBlockDependency;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class LayoutBuilderStServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');
    if (isset($modules['block_content'])) {
      $definition = new Definition(SetInlineBlockDependency::class);
      //      EntityRepositoryInterface $entity_repository, Connection $database, InlineBlockUsageInterface $usage, SectionStorageManagerInterface $section_storage_manager, CurrentRouteMatch $current_route_match
      $definition->setArguments([
        new Reference('entity.repository'),
        new Reference('database'),
        new Reference('inline_block.usage'),
        new Reference('plugin.manager.layout_builder.section_storage'),
        new Reference('current_route_match')
      ]);
      $definition->addTag('event_subscriber');
      $definition->setPublic(TRUE);
      $container->setDefinition('layout_builder_st.get_block_dependency_subscriber', $definition);
    }

    if (isset($modules['jsonapi'])) {
      $container
        ->getDefinition('jsonapi.resource_type.repository')
        ->setClass(ResourceTypeRepository::class);
    }
  }

}
