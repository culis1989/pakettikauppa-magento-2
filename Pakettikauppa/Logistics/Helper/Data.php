<?php
namespace Pakettikauppa\Logistics\Helper;

use Magento\Checkout\Model\Cart;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    public function __construct(
        Cart $cart
    ) {
        $this->cart = $cart;
    }

    public function getCarrierCode($carrier,$name){
      $carrier = preg_replace('/[^\00-\255]+/u', '',strtolower(preg_replace('/\s+/', '', $carrier)));
      $name = preg_replace('/[^\00-\255]+/u', '',strtolower(preg_replace('/\s+/', '', $name)));
      $code = $carrier.'_'.$name;
      if($code == 'posti_palautus'){
        return false;
      }else{
        return $code;
      }
    }

    public function getZip(){
      $zip_pickup = $this->cart->getQuote()->getData('pickuppoint_zip');
      $zip_shipping = $this->cart->getQuote()->getShippingAddress()->getPostcode();
       if($zip_pickup){
         return $zip_pickup;
      }elseif($zip_shipping){
        return $zip_shipping;
      }else{
        return false;
      }
    }
    public function getPickupPointServiceCode($data, $provider){
      $result = 0;
      foreach($data as $d){
        if($d->service_provider == $provider){
          if(count($d->additional_services)>0){
           foreach($d->additional_services as $service){
              if($service->service_code == '2106'){
                $result = $d->shipping_method_code;
                break;
              }
           }
          }
        }
      }
      return $result;
    }

    public function getMethod($code){
      if(strpos($code, 'pktkp_pickuppoint') !== false) {
        return 'pktkp_pickuppoint';
      }
      if(strpos($code, 'pktkp_homedelivery') !== false) {
        return 'pktkp_homedelivery';
      }
    }

    public function isPakettikauppa($code){
      if(strpos($code, 'pktkp_pickuppoint') !== false || strpos($code, 'pktkp_homedelivery') !== false) {
        return true;
      }else{
        return false;
      }
    }

    public function getCurrentCarrierTitle($code){
      $methods = $this->cart->getQuote()->getShippingAddress()->getShippingRatesCollection()->getData();
      foreach($methods as $method){
        if($method['code'] == $code){
          if($method['carrier'] == 'pktkp_homedelivery'){
            $title = $method['method_title'];
          }
          if($method['carrier'] == 'pktkp_pickuppoint'){
            $title =  $method['method_title'];
          }
        }
      }
      if(isset($title)){
        return $title;
      }else{
        return 'Unknown';
      }
    }

    public function getShipmentStatusText($code){
      switch ($code) {
          case "13":
              $status = "Item is collected from sender - picked up";
              break;
          case "20":
              $status = "Exception";
              break;
          case "22":
              $status = "Item has been handed over to the recipient";
              break;
          case "31":
              $status = "Item is in transport";
              break;
          case "38":
              $status = "C.O.D payment is paid to the sender";
              break;
          case "45":
              $status = "Informed consignee of arrival";
              break;
          case "48":
              $status = "Item is loaded onto a means of transport";
              break;
          case "56":
              $status = "Item not delivered – delivery attempt made";
              break;
          case "68":
              $status = "Pre-information is received from sender";
              break;
          case "71":
              $status = "Item is ready for delivery transportation";
              break;
          case "77":
              $status = "Item is returning to the sender";
              break;
          case "91":
              $status = "Item is arrived to a post office";
              break;
          case "99":
              $status = "Outbound";
              break;
          default:
              $status = "Unknown";
      }
      return $status;
    }

    public function getTrackingUrl($order){
      $track_data = $order->getTracksCollection()->getData();
      $track_number = $track_data[0]['track_number'];
      return '/pub/labels/'.$track_number.'.pdf';
    }
}
