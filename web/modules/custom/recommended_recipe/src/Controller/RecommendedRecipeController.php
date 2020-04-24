<?php

declare(strict_types = 1);

namespace Drupal\recommended_recipe\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\usergreeting\GreetingTime;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class RecommendedRecipeController.
 */
class RecommendedRecipeController extends ControllerBase {


  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The greeting message.
   *
   * @var \Drupal\usergreeting\GreetingTime
   */
  protected $greeting;

  /**
   * RecommendedRecipeController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\usergreeting\GreetingTime $greetingTime
   *   The greeting message.
   */
  public function __construct(EntityTypeManager $entity_type_manager, GreetingTime $greetingTime) {
    $this->entityTypeManager = $entity_type_manager;
    $this->greeting = $greetingTime;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('usergreeting.greeting')
    );
  }

  /**
   * Returns the recipe title and url in a JSON format.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The recipe output.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function content(): JsonResponse {
    return new JsonResponse(
      [
        'data' => $this->getResults(),
        'method' => 'GET',
      ]
    );
  }

  /**
   * Get the data of the random recipe .
   *
   * @return array
   *   The title and the url of the recipe
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  private function getResults(): array {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();

    $ids = $query->condition('type', 'article')
      ->condition('status', '1')
      ->condition('field_type_of_food.entity.name', $this->getTaxonomyName())
      ->execute();

    if (empty($ids)) {
      return [
        'Recipe name' => 'There is no available recipe',
      ];
    }
    else {
      $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($ids);
      shuffle($nodes);
      $firstItem = reset($nodes);
      return [
        'Recipe name' => $firstItem->title->value,
        'url' => $firstItem->toUrl()->toString(),
      ];
    }
  }

  /**
   * Get the name of the taxonomy from the usergreeting module.
   *
   * @return string
   *   The name of the taxonomy.
   */
  public function getTaxonomyName(): string {
    $greetingtext = $this->greeting->greetingMessage();
    $salutation = (string) $greetingtext;
    if ($salutation == (string) $this->t('Good Morning')) {
      $taxonomy = 'Breakfast';
    }
    elseif ($salutation === (string) $this->t('Good Afternoon')) {
      $taxonomy = 'Lunch';
    }
    else {
      $taxonomy = 'Dinner';
    }
    return $taxonomy;
  }

}
