<?php

namespace Drupal\paypal_payments\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class PayPalPaymentsController extends ControllerBase {

  /** @var  \Drupal\Core\Entity\EntityTypeManagerInterface */
  protected $entityTypeManager;


  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  public function viewAllPayPalReceipts(Request $request){

    $items = $this->entityTypeManager->getStorage('paypal_payments');

    $query = $items->getQuery();
    $query->pager(10)
      ->sort('id', 'ASC');

    $ids = $query->execute();

    $items = $items->loadMultiple($ids);

    $header = [
      'id' => [
        'data' => $this->t('ID'),
        'specifier' => 'id',
      ],
      'title' => [
        'data' => $this->t('Payer Email'),
        'specifier' => 'email',
      ],

      'payment_id' => [
        'data' => $this->t('Payment Id'),
        'specifier' => 'payment_id',
      ],
      'created' => [
        'data' => $this->t('Created'),
        'specifier' => 'created',
        // Set default sort criteria.
        'sort' => 'desc',
      ],

      'status' => [
        'data' => $this->t('Status'),
        'specifier' => 'payment_status',
      ],
      'uid' => [
        'data' => $this->t('Author'),
        'specifier' => 'uid',
      ],
    ];


    $date_formatter = \Drupal::service('date.formatter');
    $rows = [];
    /** @var \Drupal\paypal_payments\Entity\PaypalPayments $item */
    foreach ($items as $item) {
      //
      $row = [];
      $row[] = $item->id();
      $row[] = $item->getPayerEmail();
      $row[] = $item->getSaleId();
      $created = $item->getCreatedTime();
      $row[] = [
        'data' => [
          '#theme' => 'time',
          '#text' => $date_formatter->format($created),
          '#attributes' => [
            'datetime' => $date_formatter->format($created, 'custom', \DateTime::RFC3339),
          ],
        ],
      ];
      $row[] = $item->getPaymentStatus();
      $row[] = [
        'data' => $item->get('uid')->view(),
      ];
      $rows[] = $row;
    }

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No content has been found.'),
    ];

    $build['pager'] = [
      '#type' => 'pager',
    ];

    return $build;

  }

  public function viewOwnPayPalPayments($user, Request $request){
    $items = $this->entityTypeManager->getStorage('paypal_payments');

    /** @var \Drupal\Core\Session\AccountProxy $current_user */
    $current_user = \Drupal::currentUser();
    $query = $items->getQuery();
    $query->pager(10)
      ->condition('uid', $current_user->id())
      ->sort('id', 'ASC');

    $ids = $query->execute();

    $items = $items->loadMultiple($ids);

    $header = [
      'id' => [
        'data' => $this->t('ID'),
        'specifier' => 'id',
      ],
      'title' => [
        'data' => $this->t('Payer Email'),
        'specifier' => 'email',
      ],

      'payment_id' => [
        'data' => $this->t('Payment Id'),
        'specifier' => 'payment_id',
      ],
      'created' => [
        'data' => $this->t('Created'),
        'specifier' => 'created',
        // Set default sort criteria.
        'sort' => 'desc',
      ],

      'status' => [
        'data' => $this->t('Status'),
        'specifier' => 'payment_status',
      ],
      'uid' => [
        'data' => $this->t('Author'),
        'specifier' => 'uid',
      ],
    ];

    $date_formatter = \Drupal::service('date.formatter');
    $rows = [];
    /** @var \Drupal\paypal_payments\Entity\PaypalPayments $item */
    foreach ($items as $item) {
      //
      $row = [];
      $row[] = $item->id();
      $row[] = $item->getPayerEmail();
      $row[] = $item->getSaleId();
      $created = $item->getCreatedTime();
      $row[] = [
        'data' => [
          '#theme' => 'time',
          '#text' => $date_formatter->format($created),
          '#attributes' => [
            'datetime' => $date_formatter->format($created, 'custom', \DateTime::RFC3339),
          ],
        ],
      ];
      $row[] = $item->getPaymentStatus();
      $row[] = [
        'data' => $item->get('uid')->view(),
      ];
      $rows[] = $row;
    }

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No content has been found.'),
    ];

    $build['pager'] = [
      '#type' => 'pager',
    ];

    return $build;
  }
  public function payPalDisputes(Request $request){

  }
}