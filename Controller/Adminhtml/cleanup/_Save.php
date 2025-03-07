<?php
namespace Laurensmedia\Productdesigner\Controller\Adminhtml\cleanup;

use Magento\Backend\App\Action;
use Magento\Framework\App\Filesystem\DirectoryList;


class Save extends \Magento\Backend\App\Action
{

    /**
     * @param Action\Context $context
     */
    public function __construct(Action\Context $context)
    {
        parent::__construct($context);
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
		ini_set('memory_limit', "1024M");
		error_reporting(0);
        $data = $this->getRequest()->getPostValue();
		$postData = $data;
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$mediaDirectory = $objectManager->get('Magento\Framework\Filesystem')
			->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
// 		echo '<pre>';print_r($postData);exit;

		$startDateTime = strtotime($postData['start_date']);
		$endDateTime = strtotime($postData['end_date']);
		$startYear = date('Y', $startDateTime);
		$startMonth = date('m', $startDateTime);
		$startDate = date('d', $startDateTime);
		$endYear = date('Y', $endDateTime);
		$endMonth = date('m', $endDateTime);
		$endDate = date('d', $endDateTime);

		// Clean up quote items table
		if($this->getRequest()->getParam('quote_items') == 'true'){
			$quoteItems = Mage::getModel('sales/quote_item')
				->getCollection()
				->addFieldToFilter('updated_at', array('gteq' => "$startYear-$startMonth-$startDate 00:00:00"))
				->addFieldToFilter('updated_at', array('lteq' => "$endYear-$endMonth-$endDate 23:59:59"));
		}
		
		// Clean up saved designs table
/*
		if($this->getRequest()->getParam('prod_design_saved') == 'true'){
	        $items = $objectManager->create('Laurensmedia\Productdesigner\Model\ResourceModel\Saved\Collection')
				->addFieldToFilter('is_ordered', '0')
				->addFieldToFilter('save_id', array('lteq' => $this->getRequest()->getParam('prod_design_saved_number')));
			foreach($items as $item){
				$item->delete();
			}
		}
*/

/*
		$orderImages = array();
        $svgFiles = $objectManager->create('Laurensmedia\Productdesigner\Model\ResourceModel\Saved\Collection')
			->addFieldToFilter('is_ordered', 1)
			->getColumnValues('svg');
		foreach($svgFiles as $svgFile){
			$svgFileLocation = $mediaDirectory->getAbsolutePath().'productdesigner/svg/'.$svgFile;
			$handle = fopen($svgFileLocation, 'r');
			$svgContent = fread($handle, filesize($svgFileLocation));
			fclose($handle);
			
			// Look for images in SVG file
			$svgObject = simplexml_load_file($svgFileLocation);
			if($svgObject && !empty($svgObject)){
				foreach($svgObject->children() as $svgElement){
					if($svgElement->getName() == 'image'){
						$imgSrc = $svgElement->attributes('xlink', true)->href;
						$orderImages[] = (string)$imgSrc[0];
					} elseif($svgElement->getName() == 'g'){
						foreach($svgElement->children() as $svgSubElement){
							if($svgSubElement->getName() == 'image'){
								$imgSrc = $svgSubElement->attributes('xlink', true)->href;
								$orderImages[] = (string)$imgSrc[0];
							}
						}
					}
				}
			}
		}
*/

		$orderItems = $objectManager->create('Magento\Sales\Model\ResourceModel\Order\Item\Collection')
			->addFieldToSelect('productdesigner_data')
			->addFieldToSelect('item_id')
			->addFieldToSelect('order_id')
			->addFieldToFilter('productdesigner_data', array('like' => '%connect_id%'));
		$orderImages = array();
		foreach($orderItems as $orderItem){
			$itemJson = json_decode($orderItem->getProductdesignerData(), true);
			$connectId = '';
			if(isset($itemJson['connect_id'])){
				$connectId = $itemJson['connect_id']['connect_id'];
			}
	        $svgFiles = $objectManager->create('Laurensmedia\Productdesigner\Model\ResourceModel\Saved\Collection')
				->addFieldToFilter('connect_id', $connectId)
				->getColumnValues('svg');
			foreach($svgFiles as $svgFile){
				$svgFileLocation = $mediaDirectory->getAbsolutePath().'productdesigner/svg/'.$svgFile;
				$handle = fopen($svgFileLocation, 'r');
				$svgContent = fread($handle, filesize($svgFileLocation));
				fclose($handle);
				
				// Look for images in SVG file
				$svgObject = simplexml_load_file($svgFileLocation);
				if($svgObject && !empty($svgObject)){
					foreach($svgObject->children() as $svgElement){
						if($svgElement->getName() == 'image'){
							$imgSrc = $svgElement->attributes('xlink', true)->href;
							$imgSrc = (string)$imgSrc[0];
							$imgSrc = explode('/pub/media/', $imgSrc);
							if(isset($imgSrc[1])){
								$orderImages[] = $mediaDirectory->getAbsolutePath().$imgSrc[1];
							}
						} elseif($svgElement->getName() == 'g'){
							foreach($svgElement->children() as $svgSubElement){
								if($svgSubElement->getName() == 'image'){
									$imgSrc = $svgSubElement->attributes('xlink', true)->href;
									$imgSrc = (string)$imgSrc[0];
									$imgSrc = explode('/pub/media/', $imgSrc);
									if(isset($imgSrc[1])){
										$orderImages[] = $mediaDirectory->getAbsolutePath().$imgSrc[1];
									}
								}
							}
						}
					}
				}
			}
		}
		
		$count = 0;
		$dir = $mediaDirectory->getAbsolutePath().'productdesigner_uploads/';
		$yearDirs = scandir($dir);
		foreach($yearDirs as $yearDir){
			if($yearDir == '.' || $yearDir == '..' || !is_dir($dir.$yearDir)){
				continue;
			}
			$monthDirs = scandir($dir.$yearDir.'/');
			foreach($monthDirs as $monthDir){
				if($monthDir == '.' || $monthDir == '..' || !is_dir($dir.$yearDir.'/'.$monthDir)){
					continue;
				}

				$files = scandir($dir.$yearDir.'/'.$monthDir.'/');
				foreach($files as $fileName){
					if(!is_dir($dir.$yearDir.'/'.$monthDir.'/'.$fileName)){
						$filePath = $mediaDirectory->getAbsolutePath().'productdesigner_uploads/'.$yearDir.'/'.$monthDir.'/'.$fileName;
						if(!in_array($filePath, $orderImages)){
							unlink($mediaDirectory->getAbsolutePath().'productdesigner_uploads/'.$yearDir.'/'.$monthDir.'/'.$fileName);
									//unlink($mediaDirectory->getAbsolutePath().'productdesigner_uploads/thumbs/'.$yearDir.'/'.$monthDir.'/'.$fileName);
							$deletePath = $mediaDirectory->getAbsolutePath().'productdesigner_uploads/'.$yearDir.'/'.$monthDir.'/'.$fileName;
							$dateCreated = filectime($deletePath);
							if($dateCreated < $startDateTime || $dateCreated > $endDateTime){
								echo "Out of date range ".$filePath.'<br />';
								continue;
							}
							echo "Deleted ".$filePath.'<br />';
							$count++;
						} else {
							echo "Exists ".$filePath.'<br />';
						}
					}
				}
			}
		}
		
		$this->messageManager->addSuccess(__('Successfully deleted '.$count.' images'));
        
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('*/*/edit');
    }
}