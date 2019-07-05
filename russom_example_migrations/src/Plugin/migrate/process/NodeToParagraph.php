<?php
/**
 * @file
 * Contains \Drupal\russom_example_migrations\Plugin\migrate\process\NodeToParagraph.
 */

namespace Drupal\russom_example_migrations\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\MigratePluginManagerInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This plugin converts D7 node values to D8 paragraph values.
 *
 * @code
 * process:
 *   field_name_destination:
 *     plugin: node_to_paragraph
 *     source: node_field_name_source
 *     node_field: destination_node_paragraph_field
 *     paragraph_type: hero
 *     migration: example_node_page
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "node_to_paragraph"
 * )
 */
class NodeToParagraph extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The process plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigratePluginManager
   */
  protected $processPluginManager;

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * The migration to be executed.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, MigrationPluginManagerInterface $migration_plugin_manager, MigratePluginManagerInterface $process_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migrationPluginManager = $migration_plugin_manager;
    $this->migration = $migration;
    $this->processPluginManager = $process_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('plugin.manager.migration'),
      $container->get('plugin.manager.migrate.process')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    /**
     * @see https://gist.github.com/pfaocle/4b945309f18716a5f24c759fa0d9ae8b  For context and similar example.
     */

    $paragraphs = [];

    // Configuration.
    $bundle = $this->configuration['paragraph_type'];
    $destination = $this->configuration['destination'];
    $node_field = $this->configuration['node_field'];

    if ($row->getSource()['nid']) {
      $source_nid = $row->getSource()['nid'];
    }
    $destination_nid = $this->getDestinationIds($source_nid);

    if (!is_null($destination_nid[0][0])) {
      $nid = $destination_nid[0][0];
      $langcode = $destination_nid[0][1];

      $node = Node::load($nid);

      $field_type_paragraphs = [];

      if (!empty($node->$node_field)) {
        if (!is_null($langcode) && $node->hasTranslation($langcode)) {
          $translated_entity = $node->getTranslation($langcode);
          $field_type_paragraphs = $translated_entity->$node_field->getValue();
        }
        else {
          $field_type_paragraphs = $node->$node_field->getValue();
        }
      }

      // Loop through all the paragraph types associated with the node.
      foreach ($field_type_paragraphs as $paragraph_source) {
        $target_id = $paragraph_source['target_id'];
        $target_revision_id = $paragraph_source['target_revision_id'];

        $paragraph_data = Paragraph::load($target_id);

        if (!is_null($paragraph_data)) {
          if ($paragraph_data->bundle() == $bundle) {
            $paragraph_value = [
              'value' => $value['value'],
            ];
            $paragraph_data->set($destination, $paragraph_value);
            $paragraph_data->save();
          }
          // All the existing paragraphs types will be captured.
          // This is done to avoid removal of existing paragraphs types.
          $paragraphs[] = [
            'target_id' => $target_id,
            'target_revision_id' => $target_revision_id,
          ];
        }
      }
    }
    return $paragraphs;
  }

  private function getDestinationIds(string $source_id) {
    // Cache the migrations.
    $migrations = &drupal_static(__FUNCTION__);

    if (!isset($migrations)) {
      $migration_ids = $this->configuration['migration'];

      if (!is_array($migration_ids)) {
        $migration_ids = [$migration_ids];
      }

      $migrations = $this->migrationPluginManager->createInstances($migration_ids);
    }

    $destination_ids = NULL;

    foreach ($migrations as $migration) {
      $destination_ids = $migration->getIdMap()
        ->lookupDestinationIds([$source_id]);
    }
    return $destination_ids;
  }
}
