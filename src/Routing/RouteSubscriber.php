<?php

/**
* @file
* Contains \Drupal\form_overwrite\Routing\RouteSubscriber.
*/

namespace Drupal\logintoboggan\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
* Listens to the dynamic route events.
*/
class RouteSubscriber extends RouteSubscriberBase {

/**
* {@inheritdoc}
*/
protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('user.login')) {
      $route->setDefault('_form', '\Drupal\logintoboggan\Form\LogintobogganUserLoginForm');
    }
  }
}