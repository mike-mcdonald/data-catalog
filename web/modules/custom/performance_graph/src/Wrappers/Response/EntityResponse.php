<?php

declare(strict_types = 1);

namespace Drupal\performance_graph\Wrappers\Response;

use Drupal\Core\Entity\EntityInterface;
use Drupal\graphql\GraphQL\Response\Response;

/**
 * Type of response used when an entity is returned.
 */
class EntityResponse extends Response {

  /**
   * The entity to be served.
   *
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected $entity;

  /**
   * Sets the content.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity to be served.
   */
  public function setEntity(?EntityInterface $entity): void {
    $this->entity = $entity;
  }

  /**
   * Gets the entity to be served.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity to be served.
   */
  public function entity(): ?EntityInterface {
    return $this->entity;
  }

}
