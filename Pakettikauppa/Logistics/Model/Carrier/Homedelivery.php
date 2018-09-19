<?php
namespace Pakettikauppa\Logistics\Model\Carrier;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;

class Homedelivery extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements
    \Magento\Shipping\Model\Carrier\CarrierInterface
{

  protected $_code = 'pktkp_homedelivery';

  public function __construct(
    \Psr\Log\LoggerInterface $logger,
    \Magento\Framework\Registry $registry,
    \Magento\Store\Model\StoreManagerInterface $storeManager,
    \Pakettikauppa\Logistics\Helper\Data $dataHelper,
    \Pakettikauppa\Logistics\Helper\Api $apiHelper,
    \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
    \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
    \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
    \Magento\Sales\Model\Order\Shipment\Track $trackFactory,
    \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
    \Magento\Checkout\Model\Session $session,
    array $data = []
  ) {
    $this->session = $session;
    $this->registry = $registry;
    $this->storeManager = $storeManager;
    $this->dataHelper = $dataHelper;
    $this->apiHelper = $apiHelper;
    $this->rateMethodFactory = $rateMethodFactory;
    $this->rateResultFactory = $rateResultFactory;
    $this->trackFactory = $trackFactory;
    $this->scopeConfig = $scopeConfig;
    parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
  }

  public function getAllowedMethods()
  {
      return ['pktkp_homedelivery' => $this->getConfigData('name')];
  }

  public function collectRates(RateRequest $request)
  {
    if (!$this->getConfigFlag('active')) {
        return false;
    }
    $result = $this->rateResultFactory->create();
    $data = array();
    if(null !== $this->registry->registry('pktkp_icons')){
      $data = $this->registry->registry('pktkp_icons');
      $this->registry->unregister('pktkp_icons');
    }
    $homedelivery = $this->apiHelper->getHomeDelivery();
    if(count($homedelivery)>0){
      $cart_value = 0;
      foreach($this->session->getQuote()->getAllItems() as $item){
        $cart_value = $cart_value + ($item->getPrice() * $item->getQty());
      }
      foreach ($homedelivery as $hd) {

        $carrier_code = $this->dataHelper->getCarrierCode($hd->service_provider,$hd->name);
        if($carrier_code){
          $enabled = $this->scopeConfig->getValue('carriers/'.$carrier_code.'/active');
        }else{
          $enabled = 0;
        }
        if($enabled == 1){
          $method = $this->rateMethodFactory->create();
          $method->setCarrier('pktkp_homedelivery');
          $method->setCarrierTitle($hd->service_provider);
          $method->setMethod($hd->shipping_method_code);
          if(property_exists($hd, 'icon')){
            $data[$hd->service_provider] = '<img src="'.$hd->icon.'" alt="'.$hd->service_provider.'"/>';
          }
          $db_price =  $this->scopeConfig->getValue('carriers/'.$carrier_code.'/price');
          $db_title =  $this->scopeConfig->getValue('carriers/'.$carrier_code.'/title');
          $conf_price = $this->getConfigData('price');
          $conf_title = $this->getConfigData('title');
          if($db_price == ''){
            $price = $conf_price;
          }else{
            $price = $db_price;
          }
          if($db_title == ''){
            $title = $conf_title;
          }else{
            $title = $db_title;
          }

          // DISCOUNT PRICE
          $minimum =  $this->scopeConfig->getValue('carriers/'.$carrier_code.'/cart_price');
          $new_price =  $this->scopeConfig->getValue('carriers/'.$carrier_code.'/new_price');
          if($cart_value && $minimum && $cart_value >= $minimum){
            $price = $new_price;
          }
          $method->setMethodTitle($title);
          $method->setPrice($price);
          $method->setCost($price);
          $result->append($method);
        }
      }
    }
    $this->registry->register('pktkp_icons', $data);
    return $result;
  }

  public function isTrackingAvailable()
  {
    return true;
  }

  public function getTrackingInfo($tracking)
  {
    $title = 'Pakettikauppa';
    $base_url = $this->storeManager->getStore()->getBaseUrl().'logistics/tracking/';
    $track = $this->trackFactory;
    $track->setUrl($base_url.'?code='.$tracking)
          ->setCarrierTitle($title)
          ->setTracking($tracking);
    return $track;
  }
}
