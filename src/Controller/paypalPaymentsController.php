<?php
/**
 * Report pages for paypal payments
 */

namespace Drupal\paypal_payments\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class paypalPaymentsController extends ControllerBase {

  /**
   * @var \Drupal\paypal_payments\Services\paypalSettings
   */
  private $paypalSettings;

  /**
   * @var \Drupal\paypal_payments\Services\onPaypalPaymentsResponse
   */
  private $paymentsResponse;

  public function __construct(paypalSettings $paypalSettings, onPaypalPaymentsResponse $paymentsResponse) {
    $this->paypalSettings = $paypalSettings;
    $this->paymentsResponse = $paymentsResponse;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('paypal_payments.settings'),
      $container->get('paypal_payments.on_paypal_response')
    );
  }

  public function viewAllPaypalReceipts(){
    #Define $currency for use in our amount column
    $currency = $this->paypalSettings->getSetStoreCurrency();

    $content = [];
    $content['message'] = [
      '#markup' => $this->t('<h2>Below is a list of all available paypal payments
      plus additional details.</h2>'),
    ];

    $headers = [
      $this->t('Title'),
      $this->t('Payment For'),
      $this->t('Amount ('.$currency.')'),
      $this->t('Sale ID'),
    ];

    $rows = [];
    foreach ($entries = $this->paymentsResponse->getAllPaymentReceipts() as $entry){

      $per_nid = $entry['nid'];
      $url = Url::fromRoute('entity.node.canonical', ['node' => $per_nid]);
      $link = Link::fromTextAndUrl($this->t('View'), $url);
      $entry['nid'] = $link;

      // Sanitize each entry TODO:: sanitize output for extra security
      #$rows[] = array_map('Drupal\Component\Utility\SafeMarkup::checkPlain', $entry);
      $rows[] = $entry;
    }

    $content['table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No payment entries for your store yet.')
    ];

    // Don't cache this page.
    $content['#cache']['max-age'] = 0;
    return $content;
  }

  /**
   * Gets all the payment records per the logged in user
   *
   * {@inheritdoc}
   */
  public function viewOwnPaypalPayments(AccountInterface $user, Request $request){
    #$session = $request->getSession();
    $current_user = $this->currentUser();
    $uid = $current_user->id();

    // Define $currency for use in our amount column
    $currency = $this->paypalSettings->getSetStoreCurrency();

    $content = [];
    $content['message'] = [
      '#markup' => $this->t('<h1>Below is a list of all your paypal payments.</h1>'),
    ];

    $headers = [
      $this->t('Title'),
      $this->t('Payment For'),
      $this->t('Amount ('.$currency.')'),
      $this->t('Sale ID'),
    ];

    $rows = [];
    foreach ($entries = $this->paymentsResponse->getReceiptsPerUser($uid) as $entry){

      $per_nid = $entry['nid'];
      $url = Url::fromRoute('entity.node.canonical', ['node' => $per_nid]);
      $link = Link::fromTextAndUrl($this->t('View'), $url);
      $entry['nid'] = $link;

      // Sanitize each entry TODO:: sanitize output for extra security
      #$rows[] = array_map('Drupal\Component\Utility\SafeMarkup::checkPlain', $entry);
      $rows[] = $entry;
    }

    $content['table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No payment entries for you yet.')
    ];

    // Don't cache this page.
    $content['#cache']['max-age'] = 0;
    return $content;
  }

  /**
   * Serves the dispute page, with details and a way to resolve such
   * @return array
   */
  public function paypalDisputes(){

    return [
      '#markup' => 'Available payments disputes, please resolve :@TODO'
    ];

  }
}
