<?php
/**
 * Serves to control our paypal return urls, paypal charge
 * and necessary redirects
 */

namespace Drupal\paypal_payments\Services;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Path\CurrentPathStack;
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


  public function __construct(EntityFieldManager $entity_field_manager, RequestStack $request, paypalSettings $paypal_settings, EntityManager $entity, CurrentPathStack $current_path ) {
    $this->entity_field_manager = $entity_field_manager;
    $this->request = $request;
    $this->paypal_settings = $paypal_settings;
    $this->entity = $entity;
    $this->current_path = $current_path;
  }

  public function paypalPaymentsReturn(GetResponseEvent $event){

    /**
     * get node from request
     */
    /** @var \Drupal\node\Entity\Node $node */
    $request = $this->request->getCurrentRequest();
    $node = $request->attributes->get('node');
    if ($node){
      $bundle = $node->bundle();

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
            //drupal_set_message(t('Great we will try and charge your account'));
          }

          /**
           * If the user cancels and returns or there's some
           * other errors redirect to the page of origin with
           * some message
           */

          if ($isPaypalReturn === 'false'){
            #$response = new RedirectResponse()
            $response = new RedirectResponse($current_path);
            $event->setResponse($response);
            drupal_set_message(t('There was a problem authorizing the Charge'));
            //return new RedirectResponse($base_path);
            //dd('user used the cancel url');
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
      drupal_set_message('Payment success');
    } catch (Exception $exception) {
      $data = json_decode($exception->getData());
      echo $data->message;
    }
  }
}