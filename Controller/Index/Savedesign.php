<?php
namespace Laurensmedia\Productdesigner\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;

class Savedesign extends Action
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
		$fileSystem = $objectManager->create('\Magento\Framework\Filesystem');
		$mediaPath = $fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath();

		$customerObj = $objectManager->create('\Magento\Customer\Model\Session');
        $customer = $customerObj->isLoggedIn() ? $customerObj->getCustomer() : null;
        if ($customer) {
            $customerId = $customer->getId();
        } else {
            $customerId = '';
        }
        $data = $this->getRequest()->getParam('save');
        $productId = $data['productid'];
		$colorimages = $objectManager->create('Laurensmedia\Productdesigner\Model\ResourceModel\Colorimages\Collection')
			->addFieldToFilter('product_id', $productId)
			->addFieldToFilter('store_id', $storeManager->getStore()->getId());
		if(count($colorimages->getData()) == 0){
			$colorimages = $objectManager->create('Laurensmedia\Productdesigner\Model\ResourceModel\Colorimages\Collection')
				->addFieldToFilter('product_id', $productId)
				->addFieldToFilter('store_id', array('null' => true));
		}
		$product = $objectManager->create('\Magento\Catalog\Model\Product')->load($productId);

        $sizes = isset($data['sizes']) ? $data['sizes'] : array();
        $sizesHtml = "";
        if (is_array($sizes)) {
            foreach ($sizes as $size) {
                $sizesHtml .= $size['name'].":".$size['amount'].",";
            }
        }

        $number = $data['number'];
        $json = array();
        $connectId = mt_rand(0, mt_getrandmax());
        if ($data[0]['id'] != '') {
			$customerDbId = $objectManager->create('Laurensmedia\Productdesigner\Model\ResourceModel\Saved\Collection')
                ->addFieldToSelect('customer_id')
                ->addFieldToFilter('connect_id', $data[0]['id'])
                ->setPageSize(1)
                ->setCurPage(1)
                ->getFirstItem()
                ->getCustomerId();
            if ($customerId == $customerDbId) {
                $connectId = $data[0]['id'];
				$deleteItems = $objectManager->create('Laurensmedia\Productdesigner\Model\ResourceModel\Saved\Collection')
                    ->addFieldToSelect('save_id')
                    ->addFieldToSelect('connect_id')
                    ->setPageSize(3)
                    ->setCurPage(1)
                    ->addFieldToFilter('connect_id', $data[0]['id'])
                    ->load();
                foreach ($deleteItems as $deleteItem) {
					$objectManager->create('Laurensmedia\Productdesigner\Model\Saved')
						->setId($deleteItem->getId())->delete();
                }
            }
        }
        $existingSaves = $objectManager->create('Laurensmedia\Productdesigner\Model\ResourceModel\Saved\Collection')
            ->addFieldToSelect('connect_id')
            ->addFieldToFilter('connect_id', $connectId)
            ->setPageSize(1)
            ->setCurPage(1)
            ->load();
		// $existingSaves = $objectManager->create('Laurensmedia\Productdesigner\Model\ResourceModel\Saved\Collection')
		// 	->addFieldToSelect('connect_id')->getColumnValues('connect_id');
        // if (in_array($connectId, $existingSaves)) {
        if(count($existingSaves) > 0){
            $connectId = mt_rand(0, mt_getrandmax());
        }

        for ($i=0; $i<$number; $i++) {
            $object = $data[$i];
            $label = str_replace(' ', '_', $object['label']);
            $json = $object['json'];
			$png = $object['png'];
            $svg = $object['svg'];
			$outputSvg = $object['outputsvg'];
			$droparea = $objectManager->create('Laurensmedia\Productdesigner\Model\ResourceModel\Products\Collection')
				->addFieldToFilter('product_id', $productId)->addFieldToFilter('label', $label)->addFieldToFilter('store_id', $storeManager->getStore()->getId())->getFirstItem();
			if(empty($droparea->getData())){
				$droparea = $objectManager->create('Laurensmedia\Productdesigner\Model\ResourceModel\Products\Collection')
					->addFieldToFilter('product_id', $productId)->addFieldToFilter('label', $label)->addFieldToFilter('store_id', array('null' => true))->getFirstItem();
			}
			$image = $objectManager->create('Laurensmedia\Productdesigner\Model\ResourceModel\Colorimages\Collection')
				->addFieldToFilter('product_id', $productId)->addFieldToFilter('label', $label)->addFieldToFilter('store_id', $storeManager->getStore()->getId())->getFirstItem();
			if(empty($image->getData())){
				$image = $objectManager->create('Laurensmedia\Productdesigner\Model\ResourceModel\Colorimages\Collection')
					->addFieldToFilter('product_id', $productId)->addFieldToFilter('label', $label)->addFieldToFilter('store_id', array('null' => true))->getFirstItem();
			}
            if ($image) {
                $image = $image->getImgurl();
                $imageType = 'colorimage';
            } else {
/*
                $image = Mage::getModel('shirt/droparea')->getCollection()->addFieldToFilter('product_id', $productId)->addFieldToFilter('label', $label)->getFirstItem()->getImage();
                $imageType = 'dropareaimage';
*/
            }
            
			// Save json to file
			$jsonDir = $mediaPath.'productdesigner/json/'.date('Y').'/'.date('m').'/';
			if(!file_exists($jsonDir) && !is_dir($jsonDir)){
				mkdir($jsonDir, 0777, true);
			}
			$jsonFileName = date('U').'_'.rand(0,999999).'.php';
			$jsonFileLocation = $jsonDir.$jsonFileName;
			$handle = fopen($jsonFileLocation, 'w');
			fwrite($handle, $json);
			fclose($handle);
			// Save png to file
			$pngDir = $mediaPath.'productdesigner/png_export/'.date('Y').'/'.date('m').'/';
			if(!file_exists($pngDir) && !is_dir($pngDir)){
				mkdir($pngDir, 0777, true);
			}
			$pngFileName = date('U').'_'.rand(0, 999999).'.png';
			$pngFileLocation = $pngDir.$pngFileName;
			$handle = fopen($pngFileLocation, 'w');
			$png = explode(',', $png);
			$png = base64_decode($png[1]);
			fwrite($handle, $png);
			fclose($handle);
			// Save svg to file
			$svgDir = $mediaPath.'productdesigner/svg/'.date('Y').'/'.date('m').'/';
			if(!file_exists($svgDir) && !is_dir($svgDir)){
				mkdir($svgDir, 0777, true);
			}
			$svgFileName = date('U').'_'.rand(0,999999).'.php';
			$svgFileLocation = $svgDir.$svgFileName;
			$handle = fopen($svgFileLocation, 'w');
			fwrite($handle, $svg);
			fclose($handle);
			
			$svgOutputFileName = 'output_'.$svgFileName;
			$svgOutputFileLocation = $svgDir.$svgOutputFileName;
			$handle = fopen($svgOutputFileLocation, 'w');
			fwrite($handle, $outputSvg);
			fclose($handle);

            $saveData = array();
            $saveData['color'] = isset($data['color']) ? $data['color'] : '';
            $saveData['druktype'] = isset($data['druktype']) ? $data['druktype'] : '';
            $saveData['sizes'] = $sizesHtml;
            $saveData['product_id'] = $productId;
            $saveData['customer_id'] = $customerId;
			$saveData['json'] = date('Y').'/'.date('m').'/'.$jsonFileName;
			$saveData['png'] = date('Y').'/'.date('m').'/'.$pngFileName;
			$saveData['svg'] = date('Y').'/'.date('m').'/'.$svgFileName;
            $saveData['x1'] = $droparea->getData('x1');
            $saveData['x2'] = $droparea->getData('x2');
            $saveData['y1'] = $droparea->getData('y1');
            $saveData['y2'] = $droparea->getData('y2');
			$saveData['outputwidth'] = $droparea->getOutputwidth();
			$saveData['outputheight'] = $droparea->getOutputheight();
			$saveData['output_x1'] = $droparea->getData('output_x1');
			$saveData['output_x2'] = $droparea->getData('output_x2');
			$saveData['output_y1'] = $droparea->getData('output_y1');
			$saveData['output_y2'] = $droparea->getData('output_y2');
			$saveData['imagewidth'] = $droparea->getImagewidth();
			$saveData['imageheight'] = $droparea->getImageheight();
            $saveData['image'] = $image;
            $saveData['imagetype'] = $imageType;
            $saveData['label'] = $label;
            $saveData['connect_id'] = $connectId;
            $saveData['savetype'] = $object['type'];
            $saveData['store_id'] = $storeManager->getStore()->getId();
            if ($productId != "") {
				$objectManager->create('Laurensmedia\Productdesigner\Model\Saved')->addData($saveData)->save();
            }
        }
        $result->setData(array('connect_id' => $connectId));
        return $result;
    }
}