<?php

namespace Drupal\layout_builder_st\EventSubscriber;

use Drupal\block_content\BlockContentInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\layout_builder\EventSubscriber\SetInlineBlockDependency as SetInlineBlockDependencyBase;

/**
 * An event subscriber that returns an access dependency for inline blocks.
 *
 * This overrides the core version to also check revision IDs in translated
 * components.
 */
class SetInlineBlockDependency extends SetInlineBlockDependencyBase {

  /**
   * {@inheritDoc}
   *
   * We override this to load the translated entity version if it is available.
   */
  protected function getInlineBlockDependency(BlockContentInterface $block_content, $operation): ?\Drupal\Core\Access\AccessibleInterface {
    $layout_entity_info = $this->usage->getUsage($block_content->id());
    if (empty($layout_entity_info)) {
      // If the block does not have usage information then we cannot set a
      // dependency. It may be used by another module besides layout builder.
      return NULL;
    }
    $layout_entity = $this->entityRepository->getActive($layout_entity_info->layout_entity_type, $layout_entity_info->layout_entity_id);
    // Overridden section starts here - we load the translation for the current
    // language if it is available.
    $current_langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    if ($layout_entity->hasTranslation($current_langcode)) {
      $layout_entity = $layout_entity->getTranslation($current_langcode);
    }
    // Overridden section ends here.
    if ($this->isLayoutCompatibleEntity($layout_entity)) {
      if ($this->isBlockRevisionUsedInEntity($layout_entity, $block_content)) {
        return $layout_entity;
      }

    }
    return $layout_entity;
  }

  /**
   * {@inheritDoc}
   *
   * We override this to check for revision IDs in translated components as
   * well.
   */
  protected function isBlockRevisionUsedInEntity(EntityInterface $layout_entity, BlockContentInterface $block_content) {
      $sections_blocks_revision_ids = [];
      $translations = $layout_entity->getTranslationLanguages();
      foreach ($translations as $langcode => $language) {
          if ($layout_entity->hasTranslation($langcode)) {
              $translated_entity = $layout_entity->getTranslation($langcode);
              /** @var \Drupal\layout_builder_st\Plugin\SectionStorage\OverridesSectionStorage $section_storage */
              $section_storage = $this->getSectionStorageForEntity($translated_entity);
              $translated_sections = $this->getEntitySections($translated_entity);
              $sections_blocks_revision_ids = array_merge($sections_blocks_revision_ids, $this->getInlineBlockRevisionIdsInSections($translated_sections));
              foreach ($this->getInlineBlockComponents($translated_sections) as $component) {
                  $configuration = $section_storage->getTranslatedComponentConfiguration($component->getUuid());
                  if (!empty($configuration['block_revision_id'])) {
                      $sections_blocks_revision_ids[] = $configuration['block_revision_id'];
                  }
              }
              $sections_blocks_revision_ids = array_unique($sections_blocks_revision_ids);
          }
      }
    return in_array($block_content->getRevisionId(), $sections_blocks_revision_ids);
  }

}
