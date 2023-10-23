<?php

namespace Drupal\performance_graph\Plugin\GraphQL\Schema;

use Drupal\Core\Entity\EntityInterface;
use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistry;
use Drupal\graphql\Plugin\GraphQL\Schema\SdlSchemaPluginBase;
use Drupal\performance_graph\Wrappers\QueryConnection;

/**
 * @Schema(
 *   id = "performance",
 *   name = "Performance schema",
 *   extensions = "composable",
 * )
 */
class BaseSchema extends SdlSchemaPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getResolverRegistry() {
    $builder = new ResolverBuilder();
    $registry = new ResolverRegistry();

    $this->addQueryFields($registry, $builder);
    $this->addTaxonomyFields($registry, $builder);
    $this->addForumFields($registry, $builder);
    $this->addEntityDefinitionFields($registry, $builder);

    return $registry;
  }

  /**
   * @param \Drupal\graphql\GraphQL\ResolverRegistry $registry
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   */
  protected function addTaxonomyFields(ResolverRegistry $registry, ResolverBuilder $builder): void {
    $registry->addFieldResolver('TaxonomyTerm', 'id',
      $builder->produce('entity_id')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('TaxonomyTerm', 'uuid',
      $builder->produce('entity_uuid')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('TaxonomyTerm', 'name',
      $builder->produce('entity_label')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('TaxonomyTerm', 'description',
      $builder->compose(
        $builder->fromPath('entity:node', 'body'),
        $builder->map(
          $builder->callback(function ($parent) {
            return $parent['value'];
          })
        )
      )
    );
  }

  /**
   * @param \Drupal\graphql\GraphQL\ResolverRegistry $registry
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   */
  protected function addForumFields(ResolverRegistry $registry, ResolverBuilder $builder): void {
    $registry->addFieldResolver('Forum', 'id',
      $builder->produce('entity_id')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('Forum', 'uuid',
      $builder->produce('entity_uuid')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('Forum', 'name',
      $builder->produce('entity_label')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('Forum', 'parent',
      $builder->compose(
        $builder->produce('entity_reference')
          ->map('entity', $builder->fromParent())
          ->map('field', $builder->fromValue('parent')),
        $builder->callback(function ($value) {
          return reset($value);
        })
      )
    );

    $registry->addFieldResolver('Forum', 'topics',
      $builder->compose(
        $builder->callback(function (EntityInterface $entity) {
          $storage = \Drupal::entityTypeManager()->getStorage('node');
          $entityType = $storage->getEntityType();

          $nodes = $storage->loadByProperties([
            $entityType->getKey('bundle') => 'forum',
            'taxonomy_forums' => $entity->id()
          ]);

          $ids = array_map(function ($node) { return $node->id(); }, $nodes);

          return $ids;
        }),
        $builder->map(
          $builder->produce('entity_load')
            ->map('type', $builder->fromValue('node'))
            ->map('bundles', $builder->fromValue(['forum']))
            ->map('id', $builder->fromParent())
        )
      )
    );

    $registry->addFieldResolver('ForumTopic', 'id',
      $builder->produce('entity_id')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('ForumTopic', 'uuid',
      $builder->produce('entity_uuid')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('ForumTopic', 'subject',
      $builder->produce('entity_label')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('ForumTopic', 'body',
      $builder->compose(
        $builder->fromPath('entity:node', 'body'),
        $builder->map(
          $builder->callback(function ($parent) {
            return $parent['value'];
          })
        )
      )
    );
  }

  /**
   * @param \Drupal\graphql\GraphQL\ResolverRegistry $registry
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   */
  protected function addEntityDefinitionFields(ResolverRegistry $registry, ResolverBuilder $builder): void {
    // Entity definition query.
    $registry->addFieldResolver('Query', 'entityDefinition',
      $builder->produce('entity_definition', [
        'entity_type' => $builder->fromArgument('entity_type'),
        'bundle' => $builder->fromArgument('bundle'),
        'field_types' => $builder->fromArgument('field_types'),
      ])
    );
    // Entity definition fields.
    $registry->addFieldResolver('EntityDefinition', 'label',
      $builder->produce('entity_definition_label', [
        'entity_definition' => $builder->fromParent(),
      ])
    );
    $registry->addFieldResolver('EntityDefinition', 'fields',
      $builder->produce('entity_definition_fields', [
        'entity_definition' => $builder->fromParent(),
        'bundle_context' => $builder->fromContext('bundle'),
        'field_types_context' => $builder->fromContext('field_types'),
      ])
    );
    $registry->addFieldResolver('EntityDefinitionField', 'id',
      $builder->produce('entity_definition_field_id', [
        'entity_definition_field' => $builder->fromParent(),
      ])
    );
    $registry->addFieldResolver('EntityDefinitionField', 'label',
      $builder->produce('entity_definition_field_label', [
        'entity_definition_field' => $builder->fromParent(),
      ])
    );
    $registry->addFieldResolver('EntityDefinitionField', 'description',
      $builder->produce('entity_definition_field_description', [
        'entity_definition_field' => $builder->fromParent(),
      ])
    );
    $registry->addFieldResolver('EntityDefinitionField', 'type',
      $builder->produce('entity_definition_field_type', [
        'entity_definition_field' => $builder->fromParent(),
      ])
    );
    $registry->addFieldResolver('EntityDefinitionField', 'required',
      $builder->produce('entity_definition_field_required', [
        'entity_definition_field' => $builder->fromParent(),
      ])
    );
    $registry->addFieldResolver('EntityDefinitionField', 'multiple',
      $builder->produce('entity_definition_field_multiple', [
        'entity_definition_field' => $builder->fromParent(),
      ])
    );
    $registry->addFieldResolver('EntityDefinitionField', 'maxNumItems',
      $builder->produce('entity_definition_field_max_num_items', [
        'entity_definition_field' => $builder->fromParent(),
      ])
    );
    $registry->addFieldResolver('EntityDefinitionField', 'status',
      $builder->produce('entity_definition_field_status', [
        'entity_definition_field' => $builder->fromParent(),
      ])
    );
    $registry->addFieldResolver('EntityDefinitionField', 'defaultValue',
      $builder->produce('entity_definition_field_default_value', [
        'entity_definition_field' => $builder->fromParent(),
      ])
    );
    $registry->addFieldResolver('EntityDefinitionField', 'defaultValues',
      $builder->produce('entity_definition_field_additional_default_value', [
        'entity_definition_field' => $builder->fromParent(),
      ])
    );
    $registry->addFieldResolver('EntityDefinitionField', 'isReference',
      $builder->produce('entity_definition_field_reference', [
        'entity_definition_field' => $builder->fromParent(),
      ])
    );
    $registry->addFieldResolver('EntityDefinitionField', 'isHidden',
      $builder->produce('entity_definition_field_hidden', [
        'entity_definition_field' => $builder->fromParent(),
        'entity_form_display_context' => $builder->fromContext('entity_form_display'),
      ])
    );
    $registry->addFieldResolver('EntityDefinitionField', 'weight',
      $builder->produce('entity_definition_field_weight', [
        'entity_definition_field' => $builder->fromParent(),
        'entity_form_display_context' => $builder->fromContext('entity_form_display'),
      ])
    );

    $registry->addFieldResolver('EntityDefinitionField', 'settings',
      $builder->compose(
        $builder->produce('entity_definition_field_settings', [
          'entity_definition_field' => $builder->fromParent(),
          'entity_form_display_context' => $builder->fromContext('entity_form_display'),
        ]),
        $builder->map($builder->callback(function ($setting) {
          return $setting;
        }))
      )

    );
  }

  /**
   * @param \Drupal\graphql\GraphQL\ResolverRegistry $registry
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   */
  protected function addQueryFields(ResolverRegistry $registry, ResolverBuilder $builder): void {
    $registry->addFieldResolver('Query', 'forum',
      $builder->produce('entity_load')
        ->map('type', $builder->fromValue('taxonomy_term'))
        ->map('bundles', $builder->fromValue(['forums']))
        ->map('id', $builder->fromArgument('id'))
    );

    $registry->addFieldResolver('Query', 'forums',
      $builder->produce('taxonomy_load_tree')
        ->map('vid', $builder->fromValue('forums'))
        ->map('parent', $builder->fromValue(0))
    );
  }
}
