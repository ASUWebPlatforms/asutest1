<?php

namespace Drupal\Tests\scheduled_publish\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\scheduled_publish\Service\ScheduledPublishCron;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * Test scheduled publishing of nodes.
 *
 * @package Drupal\Tests\scheduled_publish\Kernel
 * @group scheduled_publish
 */
class NodeTest extends FieldKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'taxonomy',
    'scheduled_publish',
    'content_moderation',
    'workflows',
    'datetime',
  ];

  /**
   * The scheduled update service.
   *
   * @var \Drupal\scheduled_publish\Service\ScheduledPublishCron
   * */
  private ScheduledPublishCron $scheduledUpdateService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setInstallProfile('standard');
    $this->installConfig([
      'field',
      'system',
      'content_moderation',
      'scheduled_publish',
    ]);

    $this->installEntitySchema('node');
    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('user');
    $this->installEntitySchema('content_moderation_state');
    $this->installConfig('content_moderation');

    $this->scheduledUpdateService = \Drupal::service('scheduled_publish.update');
    $this->createNodeType();
  }

  /**
   * Creates a page node type to test with, ensuring that it's moderated.
   */
  protected function createNodeType() {
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_scheduled_publish',
      'type' => 'scheduled_publish',
      'entity_type' => 'node',
      'cardinality' => -1,
    ]);

    $field_storage->save();

    $node_type = NodeType::create([
      'type' => 'page',
      'name' => 'page',
    ]);
    $node_type->save();

    FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_scheduled_publish',
      'bundle' => 'page',
      'label' => 'Test field',
    ])->save();

    $workflow = Workflow::load('editorial');
    /** @var \Drupal\content_moderation\Plugin\WorkflowType\ContentModerationInterface $contentModeration */
    $contentModeration = $workflow->getTypePlugin();
    $contentModeration->addEntityTypeAndBundle('node', 'page');
    $workflow->save();
  }

  /**
   * Test update of moderation state.
   */
  public function testUpdateModerationState() {

    $page = Node::create([
      'type' => 'page',
      'title' => 'A',
    ]);

    $page->moderation_state->value = 'draft';
    $page->set('field_scheduled_publish', [
      'moderation_state' => 'published',
      'value' => '2007-12-24T18:21Z',
    ]);
    $page->save();

    $nodeID = $page->id();

    self::assertTrue((bool) $nodeID);

    $this->scheduledUpdateService->doUpdate();

    $loadedNode = Node::load($nodeID);

    self::assertEquals('published', $loadedNode->moderation_state->value);
  }

  /**
   * Test update of future moderation state.
   */
  public function testUpdateModerationStateFuture() {

    $page = Node::create([
      'type' => 'page',
      'title' => 'A',
    ]);

    $page->moderation_state->value = 'draft';
    $page->set('field_scheduled_publish', [
      'moderation_state' => 'published',
      'value' => '2100-12-24T18:21Z',
    ]);
    $page->save();

    $nodeID = $page->id();

    self::assertTrue((bool) $nodeID);

    $this->scheduledUpdateService->doUpdate();

    $loadedNode = Node::load($nodeID);

    self::assertEquals('draft', $loadedNode->moderation_state->value);
  }

  /**
   * Test update future moderation state for archived content.
   */
  public function testUpdateModerationStateFutureWithMorePagesAndArchivedContent() {

    $page = Node::create([
      'type' => 'page',
      'title' => 'A',
    ]);

    $page->moderation_state->value = 'draft';
    $page->set('field_scheduled_publish', [
      'moderation_state' => 'published',
      'value' => '2000-12-24T18:21Z',
    ]);
    $page->save();

    $page->moderation_state->value = 'published';
    $page->set('field_scheduled_publish', [
      'moderation_state' => 'archived',
      'value' => '2000-12-24T18:21Z',
    ]);
    $page->save();

    $this->scheduledUpdateService->doUpdate();

    $loadedNode = Node::load($page->id());

    self::assertEquals('archived', $loadedNode->moderation_state->value);
  }

  /**
   * Test moderation state for multiple nodes.
   */
  public function testUpdateModerationStateMultiple() {

    $page = Node::create([
      'type' => 'page',
      'title' => 'A',
    ]);

    $page->moderation_state->value = 'draft';
    $page->set('field_scheduled_publish', [
      [
        'moderation_state' => 'published',
        'value' => '2000-12-24T18:21Z',
      ],
      [
        'moderation_state' => 'archived',
        'value' => '2000-12-24T18:21Z',
      ],
    ]);
    $page->save();

    $this->scheduledUpdateService->doUpdate();

    $loadedNode = Node::load($page->id());

    self::assertEquals('archived', $loadedNode->moderation_state->value);
  }

}
