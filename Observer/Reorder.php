<?php
namespace Laurensmedia\Productdesigner\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;

class Reorder implements ObserverInterface
{

	public function __construct(
		RequestInterface $request
	) {
		$this->_request = $request;
	}
	
	public function execute(\Magento\Framework\Event\Observer $observer) {
		$quoteItem = $observer->getQuoteItem();
		$orderItem = $observer->getOrderItem();
		$quoteItem->setProductdesignerData($orderItem->getProductdesignerData());
	}

}