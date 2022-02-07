<?php

namespace Drupal\portland\Routing;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Routing\RoutingEvents;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase
{
  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a LoggerChannelFactoryInterface object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory)
  {
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection)
  {
    // don't accept POSTs to a login route
    if ($route = $collection->get("user.login.http")) {
      $route->setRequirement("_access", "FALSE");
    }
    // don't allow password resets via Drupal
    if ($route = $collection->get("user.pass")) {
      $route->setRequirement("_access", "FALSE");
    }
    // don't accept POSTs to a password reset form
    if ($route = $collection->get("user.pass.http")) {
      $route->setRequirement("_access", "FALSE");
    }
  }
}
