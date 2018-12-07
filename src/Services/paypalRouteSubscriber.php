<?php
/**
 * Serves to control our paypal return urls, paypal charge
 * and necessary redirects
 */

namespace Drupal\paypal_payments\Services;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Session\AccountProxy;
use Drupal\paypal_payments\Form\PaypalForm;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class paypalRouteSubscriber implements EventSubscriberInterface{


  /**
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  private $entity_field_manager;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $request;

  /**
   * @var \Drupal\paypal_payments\Services\paypalSettings
   */
  private $paypal_settings;

  /**
   * @var \Drupal\Core\Entity\EntityManager
   */
  private $entity;

  /**
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  private $current_path;

  /**
   * @var \Drupal\paypal_payments\Services\paypalPaymentsEntitySave
   */
  private $paypal_payments_entity_save;

  /**
   * @var \Drupal\paypal_payments\Form\PaypalForm
   */
  private $paypal_form;

  /**
   * @var \Drupal\Core\Session\AccountProxy
   */
  private $current_user;


  public function __construct(EntityFieldManager $entity_field_manager, RequestStack $request,
                              paypalSettings $paypal_settings, EntityManager $entity,
                              CurrentPathStack $current_path, paypalPaymentsEntitySave $paypal_payments_entity_save,
                              PaypalForm $paypal_form, AccountProxy $current_user) {
    $this->entity_field_manager = $entity_field_manager;
    $this->request = $request;
    $this->paypal_settings = $paypal_settings;
    $this->entity = $entity;
    $this->current_path = $current_path;
    $this->paypal_payments_entity_save = $paypal_payments_entity_save;
    $this->paypal_form = $paypal_form;
    $this->current_user = $current_user;
  }

  public function paypalPaymentsReturn(GetResponseEvent $event){

    /**
     * get node from request
     */
    /** @var \Drupal\node\Entity\Node $node */
    $request = $this->request->getCurrentRequest();
    #$node = $request->attributes->get('node');
    //dd($this->getCurrentNode());
    if ($this->getCurrentNode()){
      $bundle = $this->getCurrentNode()->bundle();

      $fields = $this->entity_field_manager->getFieldDefinitions('node', $bundle);
      foreach ($fields as $key => $field){

        if ($field->getType() === 'field_paypal'){;
          //TODO:: create a variable with the token..maybe store in keyvalue and compare with return token for added security
          $current_path = $this->getRedirectRoute();

          $isPaypalReturn = $request->query->get('success');
          /**
           * if authorization was a success
           */
          if ($isPaypalReturn === 'true'){

            $this->chargePaypal();#charge account

            $response = new RedirectResponse($current_path);
            $event->setResponse($response);
          }

          /**
           * If the user cancels and returns or there's some
           * other errors redirect to the page of origin with
           * some message
           */

          if ($isPaypalReturn === 'false'){
            $response = new RedirectResponse($current_path);
            $event->setResponse($response);
            drupal_set_message(t('There was a problem authorizing the Charge'));
          }

        }
      }
    }
  }


  public static function getSubscribedEvents() {

    return [
      KernelEvents::REQUEST => 'paypalPaymentsReturn',
    ];
  }

  /**
   * Get the node in relation to the current request
   */
  protected function getCurrentNode(){
    /** @var \Drupal\node\Entity\Node $node */
    $request = $this->request->getCurrentRequest();
    $node = $request->attributes->get('node');

    return $node;
  }

  protected function getPriceFromNodeObject(){

  }

  /**
   * returns the relevant route for use in our
   * event redirect.TODO::do better
   */
  protected function getRedirectRoute(){
    $request = $this->request->getCurrentRequest();
    $current_path = $request->getSchemeAndHttpHost().$request->getBaseUrl().$request->getPathInfo();

    return $current_path;
  }

  /**
   * If auth was a success and we got paymentID, token and The PayerID go
   * ahead and charge the user and post to database
   */
  protected function chargePaypal(){

    $request = $this->request->getCurrentRequest();

    $paymentId = $request->query->get('paymentId');
    $token = $request->query->get('token');
    $payerId = $request->query->get('PayerID');

    //Create a charge object
    $paypal = new ApiContext(
      new OAuthTokenCredential(
        $this->paypal_settings->getClientId(),
        $this->paypal_settings->getClientSecret()
      )
    );

    $paypal->setConfig(
      ['mode' => $this->paypal_settings->getSetEnvironment()]
    );

    $payment = Payment::get($paymentId, $paypal);

    //  Execute payment
    $execute = new PaymentExecution();
    $execute->setPayerId($payerId);

    try {

      $payment->execute($execute, $paypal);
      //Load from paypal form and save to paypal_payments
      $this->paypal_payments_entity_save->paypalPaymentSuccess($this->paypal_form->getTheNode()->id(),
        $this->current_user->id(),$this->paypal_form->getPrice($this->paypal_form->getPaypalFieldItem()),
        $this->paypal_form->generateSkuFromNodeTitle());

      drupal_set_message('Payment success');
    } catch (Exception $exception) {
      $data = json_decode($exception->getData());
      echo $data->message;
    }
  }
}