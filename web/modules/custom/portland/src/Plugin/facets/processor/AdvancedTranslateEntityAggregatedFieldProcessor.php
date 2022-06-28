<?php

namespace Drupal\portland\Plugin\facets\processor;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Plugin\facets\facet_source\SearchApiDisplay;
use Drupal\facets\Plugin\facets\processor\TranslateEntityAggregatedFieldProcessor;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\Core\TypedData\TranslatableInterface;

/**
 * Transforms the results to show the translated entity label.
 *
 * @FacetsProcessor(
 *   id = "advanced_translate_entity_aggregated_fields",
 *   label = @Translation("Transform entity ID in aggregated field to label with advanced features"),
 *   description = @Translation("Display the entity label instead of its ID (for example the term name instead of the taxonomy term ID) in aggregated fields."),
 *   stages = {
 *     "build" = 5
 *   }
 * )
 */
class AdvancedTranslateEntityAggregatedFieldProcessor extends TranslateEntityAggregatedFieldProcessor implements BuildProcessorInterface, ContainerFactoryPluginInterface
{
  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results)
  {
    $field_identifier = $facet->getFieldIdentifier();
    $entity_type_ids = [];
    $allowed_values = [];
    $language_interface = $this->languageManager->getCurrentLanguage();

    // Support multiple entities when using Search API.
    if ($facet->getFacetSource() instanceof SearchApiDisplay) {
      /** @var \Drupal\search_api\Entity\Index $index */
      $index = $facet->getFacetSource()->getIndex();
      /** @var \Drupal\search_api\Item\Field $field */
      $field = $index->getField($field_identifier);

      foreach ($field->getConfiguration()['fields'] as $field_configuration) {
        $parts = explode(':', $field_configuration, 2);
        if ($parts[0] !== 'entity') {
          throw new \InvalidArgumentException('Data type must be in the form of "entity:ENTITY_TYPE/FIELD_NAME."');
        }
        $parts = explode('/', $parts[1]);
        $entity_type_id = $parts[0];

        $property_path = explode(':', $parts[1]);
        /** @var \Drupal\Core\Field\FieldStorageDefinitionInterface|null $fieldDefinition */
        $fieldDefinition = NULL;
        $ids = [];
        foreach ($results as $delta => $result) {
          $ids[$delta] = $result->getRawValue();
        }

        foreach ($property_path as $path) {
          if ($path == 'entity') {
            $entity_type_id = $fieldDefinition->getSettings()['target_type'];
            continue;
          }

          $fieldStorageDefinitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);

          if (isset($fieldStorageDefinitions[$path])) {
            $fieldDefinition = $fieldStorageDefinitions[$path];
          }
        }

        if (isset($fieldDefinition)) {
          $entities = $this->entityTypeManager
            ->getStorage($fieldDefinition->getSettings()['target_type'])
            ->loadMultiple($ids);

          // Loop over all results.
          foreach ($results as $i => $result) {
            if (!isset($entities[$ids[$i]])) {
              continue;
            }

            /** @var \Drupal\Core\Entity\ContentEntityBase $entity */
            $entity = $entities[$ids[$i]];
            // Check for a translation of the entity and load that
            // instead if one's found.
            if ($entity instanceof TranslatableInterface && $entity->hasTranslation($language_interface->getId())) {
              $entity = $entity->getTranslation($language_interface->getId());
            }

            // Overwrite the result's display value.
            $results[$i]->setDisplayValue($entity->label());
          }
        }

        // If no values are found for the current field, try to see if this is a
        // bundle field.
        foreach ($entity_type_ids as $entity) {
          $list_bundles = $this->entityTypeBundleInfo->getBundleInfo($entity);
          if (!empty($list_bundles)) {
            foreach ($list_bundles as $key => $bundle) {
              $allowed_values[$key] = $bundle['label'];
            }
            $this->overWriteDisplayValues($results, $allowed_values);
          }
        }
      }
      return $results;
    }
  }
}
