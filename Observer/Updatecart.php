<?php
namespace Laurensmedia\Productdesigner\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;

class Updatecart implements ObserverInterface
{

    public function __construct(
        RequestInterface $request
    ) {
        $this->_request = $request;
    }
    
    public function execute(\Magento\Framework\Event\Observer $observer) {
		if(strpos($_SERVER['REQUEST_URI'], 'customer/account/loginPost') !== false){
			return;
		}
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
		$baseUrl = $storeManager->getStore()->getBaseUrl();
		$mediaUrl = $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
		$fileSystem = $objectManager->create('\Magento\Framework\Filesystem');
		$mediaPath = $fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath();
		$quoteItem = $observer->getItem();
		if($quoteItem == null){
			$quoteItem = $observer->getQuoteItem();
		}
        
        $qty = $quoteItem->getQty();
		$finalPrice = $quoteItem->getPriceInclTax();

		$product = $quoteItem->getProduct();
		$_product = $objectManager->create('\Magento\Catalog\Model\Product')->load($product->getId());
		$productTierPrices = $_product->getTierPrice();
		$websiteId = $storeManager->getStore()->getWebsite()->getId();
		$groupId = $objectManager->get('\Magento\Customer\Model\Session')->getCustomerGroupId();
		
		// Get discount percentage (catalog price rules)
		$basePrice = $product->getPrice();
		$finalPrice = $product->getFinalPrice();
		if($basePrice > 0 && $quoteItem->getAppliedRuleIds()){
			$discountPercentage = 100 - round(($finalPrice / $basePrice) * 100);
		} else {
			$discountPercentage = 0;
		}
		
		foreach($productTierPrices as $tierPrice){
			if(($tierPrice['website_id'] == 0 || $tierPrice['website_id'] == $websiteId)
				&& ($tierPrice['cust_group'] == 32000 || $tierPrice['cust_group'] == 0 || $tierPrice['cust_group'] == $groupId)
			){
				$tierPrice['price'] = (float)$tierPrice['price'] * (1 - ($discountPercentage / 100));
				$tierPrices[] = array('qty' => $tierPrice['price_qty'], 'price' => $tierPrice['price']);
			}
		}

		$itemPrice = $_product->getFinalPrice();
		if(!empty($tierPrices)){
			foreach($tierPrices as $tierPrice){
				if($tierPrice['qty'] <= $qty){
					$itemPrice = $tierPrice['price'];
				}
			}
		}
        
        $data = $quoteItem->getProductdesignerData();
        if($data != ''){
	        $data = json_decode($data, true);
	        
	        $quoteItemOptions = $quoteItem->getBuyRequest()->getOptions();
			$productOptions = $objectManager->create('\Magento\Catalog\Model\Product\Option')->getProductOptionCollection($product);
			$optionPrice = 0;
			foreach($productOptions as $option){
			    if ($option->getType() === 'drop_down') {
				    $values = $objectManager->create('\Magento\Catalog\Model\Product\Option\Value')->getValuesCollection($option);
			        foreach ($values as $value) {
						if(isset($quoteItemOptions[$value->getOptionId()]) && $quoteItemOptions[$value->getOptionId()] == $value->getOptionTypeId()){
							if($value->getPriceType() == 'percent'){
								$optionPrice += ($value->getPrice() / 100) * ($itemPrice + $optionPrice);
							} else {
								$optionPrice += $value->getPrice();
							}
						}
			        }
			    }
			    if ($option->getType() === 'checkbox' || $option->getType() == 'radio') {
				    $values = $objectManager->create('\Magento\Catalog\Model\Product\Option\Value')->getValuesCollection($option);
			        foreach ($values as $value) {
				        if(isset($quoteItemOptions[$value->getOptionId()])){
					        if(is_array($quoteItemOptions[$value->getOptionId()])){
								if(in_array($value->getOptionTypeId(), $quoteItemOptions[$value->getOptionId()])){
									if($value->getPriceType() == 'percent'){
										$optionPrice += ($value->getPrice() / 100) * ($itemPrice + $optionPrice);
									} else {
										$optionPrice += $value->getPrice();
									}
								}
					        } else {
								if($quoteItemOptions[$value->getOptionId()] == $value->getOptionTypeId()){
									if($value->getPriceType() == 'percent'){
										$optionPrice += ($value->getPrice() / 100) * ($itemPrice + $optionPrice);
									} else {
										$optionPrice += $value->getPrice();
									}
								}
					        }
				        }
			        }
			    }
			}

			$itemPrice += floatval($optionPrice);
	        
	        $connectId = $data['connect_id']['connect_id'];
	        $labels = $data['labels'];
	        foreach($labels as $label){
				$savedObj = $objectManager->create('Laurensmedia\Productdesigner\Model\ResourceModel\Saved\Collection')
					->addFieldToFilter('label', $label)
					->addFieldToFilter('connect_id', $connectId)
					->setPageSize(1)
					->setCurPage(1)
					->load()
					->getFirstItem();
				$droparea = $objectManager->create('Laurensmedia\Productdesigner\Model\ResourceModel\Products\Collection')
					->addFieldToFilter('label', $label)
					->addFieldToFilter('product_id', $quoteItem->getProduct()->getId())
					->addFieldToFilter('store_id', $storeManager->getStore()->getId())
					->setPageSize(1)
					->setCurPage(1)
					->load()
					->getFirstItem();
				if(empty($droparea->getData())){
					$droparea = $objectManager->create('Laurensmedia\Productdesigner\Model\ResourceModel\Products\Collection')
						->addFieldToFilter('label', $label)
						->addFieldToFilter('product_id', $quoteItem->getProduct()->getId())
						->addFieldToFilter('store_id', array('null' => true))
						->setPageSize(1)
						->setCurPage(1)
						->load()
						->getFirstItem();
				}

				if($savedObj->getData()){
					$json = json_decode(file_get_contents($mediaPath.'productdesigner/json/'.$savedObj->getJson()), true);
					$objectCounter = 0;
					foreach($json['objects'] as $object){
						if(isset($object['title']) && $object['title'] != '' && $object['title'] != 'Base' && $object['title'] != 'Overlay'){
							$objectCounter++;
						}
					}
					if($objectCounter > 0){
						// Product side is not empty
						$dropareaSurcharge = 0;
						$surchargeTable = $droparea['surcharge_table'];
						if($surchargeTable != '' && $surchargeTable != '{"":""}'){
							$surchargeTable = array_filter(json_decode($surchargeTable, true));
							foreach($surchargeTable as $surchargeQty => $surchargeValue){
								if($qty >= $surchargeQty && $surchargeQty > 0){
									$dropareaSurcharge = floatval($surchargeValue);
								}
							}
						} else {
							$dropareaSurcharge = floatval($droparea['surcharge']);
						}
						$itemPrice += $dropareaSurcharge;
					}
				}
// 				echo '<pre>';print_r($droparea->getData());exit;
	        }
			$priceProcessed = $quoteItem->getOptionByCode('pd_processed_price') ? $quoteItem->getOptionByCode('pd_processed_price')->getValue() : false;
				// var_dump($priceProcessed);exit;
	        if($itemPrice != $quoteItem->getCustomPrice() || !$priceProcessed){
				$requestInterface = $objectManager->get('Magento\Framework\App\RequestInterface');
				$actionName = $requestInterface->getFullActionName();
				
		        $quoteItem->setCustomPrice($itemPrice);
		        $quoteItem->setOriginalCustomPrice($itemPrice);
		        $quoteItem->setPrice($itemPrice);
		        $quoteItem->setOriginalPrice($itemPrice);
		        $quoteItem->getProduct()->setIsSuperMode(true);
				$quoteItem->addOption(array(
					'product' => $quoteItem->getProduct(),
					'code' => 'pd_processed_price',
					'value' => 1
				));
		        $quoteItem->save();
				$quoteItem->getQuote()->setTotalsCollectedFlag(false)->save();
				

				$checkoutSession = $objectManager->get('\Magento\Checkout\Model\Session');
				
				$isDiscountApplied = ($quoteItem->getDiscountAmount() > 0) ? true : false;
		        
		        // if(!isset($_GET['update']) && strpos($_SERVER['REQUEST_URI'], 'update=1') === false && $actionName == 'checkout_cart_index' && $checkoutSession->getIsUpdating() !== true && !$isDiscountApplied){
				// 	$quote = $objectManager->create('\Magento\Checkout\Model\Session')->getQuote();
				// 	$quote->setTotalsCollectedFlag(false);
			    //     $checkoutSession->setIsUpdating(true);
			    //     $quote->collectTotals()->save();
			    //     $checkoutSession->setIsUpdating(false);
			    // }
		        
		        if(!isset($_GET['update']) && strpos($_SERVER['REQUEST_URI'], 'update=1') === false && $actionName == 'checkout_cart_index'){
			        header('Location: '.$storeManager->getStore()->getBaseUrl().'checkout/cart?update=1');
			        die();
			    }
			}
        }
		
		$quote = $objectManager->create('\Magento\Checkout\Model\Session')->getQuote();
		$doUpdate = false;
		foreach($quote->getAllItems() as $item){
			$isDiscountApplied = ($item->getDiscountAmount() > 0) ? true : false;
			if($item->getCustomPrice() != $item->getPriceInclTax() && !$isDiscountApplied){
				$doUpdate = true;
			}
		}
		if($doUpdate){
			$checkoutSession = $objectManager->get('\Magento\Checkout\Model\Session');
			// $quote->setTotalsCollectedFlag(false);
			// $checkoutSession->setIsUpdating(true);
			// $quote->collectTotals()->save();
			// $checkoutSession->setIsUpdating(false);
		}
    }

}