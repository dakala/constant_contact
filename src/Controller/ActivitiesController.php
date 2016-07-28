<?php

namespace Drupal\constant_contact\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\constant_contact\AccountInterface;
use Ctct\ConstantContact;
use GuzzleHttp;
use Symfony\Component\HttpFoundation\Request;
use Drupal\constant_contact\ConstantContactManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

class ActivitiesController extends ControllerBase {

  /** @var \Drupal\constant_contact\ConstantContactManagerInterface */
  protected $constantContactManager;

  /**
   * ActivitiesController constructor.
   * @param \Drupal\constant_contact\ConstantContactManagerInterface $constant_contact_manager
   */
  public function __construct(ConstantContactManagerInterface $constant_contact_manager) {
    $this->constantContactManager = $constant_contact_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('constant_contact.manager'));
  }
  /**
   * @param \Drupal\constant_contact\AccountInterface $constant_contact_account
   * @return mixed
   */
  public function index() {
    $activities = $this->constantContactManager->getActivities();

    $fields = $this->getFields();
    $header = \Drupal::service('constant_contact.manager')->normalizeFieldNamesArray($fields);
    $header[] = $this->t('Operations');

    $rows = [];
    foreach ($activities as $activity) {
      $row = [];
      foreach ($fields as $field) {
        $row[] = $activity->{$field};
      }

      $links['view'] = array(
        'title' => $this->t('View'),
        'url' => Url::fromRoute('constant_contact.activities.view', ['id' => $activity->id]),
      );

      $row[] = [
        'data' => [
          '#type' => 'operations',
          '#links' => $links,
        ],
      ];
      $rows[] = $row;
    }

    $build['activities'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No activities available.'),
    ];

    return $build;
  }

  /**
   * @param \Drupal\constant_contact\AccountInterface $constant_contact_account
   * @param $id
   * @return array
   */
  public function view($id) {
    $activity = \Drupal::service('constant_contact.manager')->getActivity($id);

    return $build = [
      '#theme' => 'cc_activity',
      '#fields' => \Drupal::service('constant_contact.manager')->convertObjectToArray($activity),
    ];
  }

  /**
   * @return array
   */
  public function getFields() {
    return [
      'type',
      'status',
      'start_date',
      'finish_date',
      'error_count',
      'contact_count',
    ];
  }


  public function todo() {
    return ['#type' => 'markup', '#markup' => 'todo:'];
  }

}
