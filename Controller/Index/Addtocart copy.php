<?php
namespace Laurensmedia\Productdesigner\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;

class Addtocart extends Action
{

    /**
     * @var PageFactory
     */
    protected $_resultPageFactory;
 
    /**
     * @var JsonFactory
     */
    protected $_resultJsonFactory;
 
 
    /**
     * View constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(Context $context, PageFactory $resultPageFactory, JsonFactory $resultJsonFactory)
    {
 
        $this->_resultPageFactory = $resultPageFactory;
        $this->_resultJsonFactory = $resultJsonFactory;
 
        parent::__construct($context);
    }
    
    public function execute()
    {
        $result = $this->_resultJsonFactory->create();
        $resultPage = $this->_resultPageFactory->create();
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
		$baseUrl = $storeManager->getStore()->getBaseUrl();
		$mediaUrl = $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);


        $data = $this->getRequest()->getParam('cart');
        $options = $this->getRequest()->getParam('options');

        // Remove all items with the same connect id
        $connectId = $this->getRequest()->getParam('connect_id');
		$quote = $objectManager->create('\Magento\Checkout\Model\Cart')->getQuote();

        if ($this->getRequest()->getParam('isupdatequoteitem') != 'false'){
			$quoteItem = $objectManager->create('\Magento\Quote\Model\Quote\Item')
				->load($this->getRequest()->getParam('isupdatequoteitem'));
            $quote->removeItem($quoteItem->getId())->save();
            $quote->save();
            $quote = $objectManager->create('\Magento\Checkout\Model\Cart')->getQuote();
        }

        $cartItems = $quote->getAllVisibleItems();
        foreach ($cartItems as $item) {
            if ($item->getOptionByCode('connect_id')) {
                $itemConnectId = $item->getOptionByCode('connect_id')->getValue();
                if(is_array($itemConnectId)){
                    $itemConnectId = $itemConnectId['connect_id'];
                }
                if ($itemConnectId == $connectId) {
                    $quote->removeItem($item->getId())->save();
                    $quote->save();
                }
            }
        }
        if ($connectId == '') {
            $connectId = mt_rand(0, mt_getrandmax());
        }

        $product_id = $data['productid'];
		$product = $objectManager->create('\Magento\Catalog\Model\Product')->load($product_id);

		$productoptions = array();
		$superattributes = array();
		if($options != ""){
			foreach($options as $option){
				if(strpos($option['name'], 'option') !== false){
					$id = preg_replace("/[^0-9]+/", "", $option['name']);
					$value = $option['value'];
					if(strpos($option['name'], '[]') !== false){
						if(!isset($productoptions[$id])){
							$productoptions[$id] = array();
						}
						$productoptions[$id][] = $value;
					} else {
						$productoptions[$id] = $value;
					}
				} elseif(strpos($option['name'], 'super_attribute') !== false) {
					$id = preg_replace("/[^0-9]+/", "", $option['name']);
					$value = $option['value'];
					$superattributes[$id] = $value;
				}
			}
		}
		$productoptions = array_filter($productoptions);

		$qty = $this->getRequest()->getParam('qty');
		if($qty < 1){
			$qty = 1;
		}

        $cart = $objectManager->create('\Magento\Checkout\Model\Cart');
        $cart->addProduct($product, array('qty' => $qty, 'product' => $product, 'options' => $productoptions, 'super_attribute' => $superattributes));
        $cart->save();

        $result->setData(array('success' => true));
        return $result;
    }
}