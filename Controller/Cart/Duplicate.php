<?php
namespace Laurensmedia\Productdesigner\Controller\Cart;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Model\QuoteFactory;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Model\ProductFactory;
use Magento\Quote\Model\Quote\ItemFactory;

class Duplicate extends Action
{

    /**
     * @var PageFactory
     */
    protected $_resultPageFactory;
 
    /**
     * @var JsonFactory
     */
    protected $_resultJsonFactory;
    
    protected $quoteFactory;
    protected $quoteItemFactory;
    protected $formKey;  
    protected $cart;
    protected $product;
    

    /**
     * View constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        JsonFactory $resultJsonFactory,
        QuoteFactory $quoteFactory,
        ItemFactory $quoteItemFactory,
        Cart $cart,
        ProductFactory $product
    ){
        $this->_resultPageFactory = $resultPageFactory;
        $this->_resultJsonFactory = $resultJsonFactory;
        
        $this->quoteFactory = $quoteFactory;
        $this->quoteItemFactory = $quoteItemFactory;
        $this->cart = $cart;
        $this->product = $product;
        
        parent::__construct($context);
    }
    
    public function execute()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $fileSystem = $objectManager->create('\Magento\Framework\Filesystem');
        $mediaPath = $fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath();
        
        try{
            $quoteItemId = $this->getRequest()->getParam('id');
            $quoteItem = $this->quoteItemFactory->create()->load($quoteItemId);
            $quote = $this->quoteFactory->create()->load($quoteItem->getQuoteId());
            $quoteItem = $quote->getItemsCollection()->addFieldToFilter('item_id', $quoteItemId)->getFirstItem();
            
            $sessionObj = $objectManager->create('\Magento\Checkout\Model\Session');
            $sessionQuoteId = $sessionObj->getQuote()->getId();
            
            if($sessionQuoteId != $quote->getId()){
                echo 'No access';return;
            }
            
            $productId =$quoteItem->getProductId();
            $_product = $this->product->create()->load($productId); 
            
            $options = $quoteItem->getProduct()->getTypeInstance(true)->getOrderOptions($quoteItem->getProduct());
            $additionalOptions = $quoteItem->getOptionByCode('additional_options');
            $additionalOptions = $additionalOptions ? $additionalOptions->getValue() : '';
                
            $pdData = $quoteItem->getProductdesignerData();
            $pdData = $pdData ? json_decode($pdData, true) : array();
            $oldConnectId = $pdData['connect_id']['connect_id'];
            $newConnectId = mt_rand(0, mt_getrandmax());
            
            if($oldConnectId > 0){
                // Todo: change connect ID and duplicate json and svg files
                $savedItems = $objectManager->create('Laurensmedia\Productdesigner\Model\ResourceModel\Saved\Collection')
                    ->addFieldToFilter('connect_id', $oldConnectId)
                    ->setPageSize(3)
                    ->setCurPage(1);

                $newSaveItems = array();
                foreach($savedItems as $savedItem){
                    $newSaveItem = $savedItem->getData();
                    if(isset($newSaveItem['save_id'])){
                        unset($newSaveItem['save_id']);
                    }
                    
                    // Duplicate files
                    $jsonDir = $mediaPath.'productdesigner/json/'.date('Y').'/'.date('m').'/';
                    if(!file_exists($jsonDir) && !is_dir($jsonDir)){
                        mkdir($jsonDir, 0777, true);
                    }
                    $jsonFileName = date('U').'_'.rand(0,999999).'.php';
                    $jsonFileLocation = $jsonDir.$jsonFileName;
                    copy($mediaPath.'productdesigner/json/'.$savedItem['json'], $jsonFileLocation);
                    $newSaveItem['json'] = date('Y').'/'.date('m').'/'.$jsonFileName;
                    
                    $pngDir = $mediaPath.'productdesigner/png_export/'.date('Y').'/'.date('m').'/';
                    if(!file_exists($pngDir) && !is_dir($pngDir)){
                        mkdir($pngDir, 0777, true);
                    }
                    $pngFileName = date('U').'_'.rand(0, 999999).'.png';
                    $pngFileLocation = $pngDir.$pngFileName;
                    copy($mediaPath.'productdesigner/png_export/'.$savedItem['png'], $pngFileLocation);
                    $newSaveItem['png'] = date('Y').'/'.date('m').'/'.$pngFileName;
                    
                    $svgDir = $mediaPath.'productdesigner/svg/'.date('Y').'/'.date('m').'/';
                    if(!file_exists($svgDir) && !is_dir($svgDir)){
                        mkdir($svgDir, 0777, true);
                    }
                    $svgFileName = date('U').'_'.rand(0,999999).'.php';
                    $svgFileLocation = $svgDir.$svgFileName;
                    copy($mediaPath.'productdesigner/svg/'.$savedItem['svg'], $svgFileLocation);
                    $newSaveItem['svg'] = date('Y').'/'.date('m').'/'.$svgFileName;
                    $oldSvgOutputFile = str_replace(basename($savedItem['svg']), 'output_'.basename($savedItem['svg']), $savedItem['svg']);
                    $oldSvgOutputFileLocation = $mediaPath.'productdesigner/svg/'.$oldSvgOutputFile;
                    $svgOutputFileName = 'output_'.$svgFileName;
                    if(file_exists($mediaPath.'productdesigner/svg/'.$svgOutputFileName)){
                        copy($mediaPath.'productdesigner/svg/'.$svgOutputFileName, $mediaPath.'productdesigner/svg/'.date('Y').'/'.date('m').'/'.$svgOutputFileName);
                    }
                    
                    $newSaveItem['connect_id'] = $newConnectId;
                    
                    $newSaveItems[] = $newSaveItem;
                }
                
                foreach($newSaveItems as $newSaveItem){
                    $objectManager->create('Laurensmedia\Productdesigner\Model\Saved')->addData($newSaveItem)->save();
                }
            }
            $pdData['connect_id']['connect_id'] = $newConnectId;
            
            $info = isset($options['info_buyRequest']) ? $options['info_buyRequest'] : array();
            $request1 = new \Magento\Framework\DataObject();
            $request1->setData($info);
            
            $result = $this->cart->addProduct($_product, $request1);
            $this->cart->save();
            
            $quote = $this->quoteFactory->create()->load($quoteItem->getQuoteId());
            $lastItem = $quote->getItemsCollection()->getLastItem();
            $lastItem->setProductdesignerData(json_encode($pdData));
            $lastItem->addOption(array(
                'product' => $lastItem->getProduct(),
                'code' => 'additional_options',
                'value' => $additionalOptions
            ));
            
            $itemPrice = $quoteItem->getCustomPrice();
            $lastItem->setCustomPrice($itemPrice);
            $lastItem->setOriginalCustomPrice($itemPrice);
            $lastItem->setPrice($itemPrice);
            $lastItem->setOriginalPrice($itemPrice);
            $lastItem->getProduct()->setIsSuperMode(true);
            $lastItem->addOption(array(
                'product' => $lastItem->getProduct(),
                'code' => 'pd_processed_price',
                'value' => 1
            ));
            
            $lastItem->save();
        } catch (\Exception $e){
            $this->messageManager->addError( __($e->getMessage()) );
        }
        
        $this->_redirect('checkout/cart/');
    }
}