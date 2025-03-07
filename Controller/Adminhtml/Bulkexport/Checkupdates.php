<?php
namespace Laurensmedia\Productdesigner\Controller\Adminhtml\Bulkexport;

use Magento\Backend\App\Action;
use Magento\Framework\App\Filesystem\DirectoryList;


class Checkupdates extends \Magento\Backend\App\Action
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
		$data = $this->getRequest()->getPostValue();
		$postData = $data;       
		
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$fileSystem = $objectManager->create('\Magento\Framework\Filesystem');
		$mediaPath = $fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath();
		$storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
		$baseUrl = $storeManager->getStore()->getBaseUrl();
		$mediaUrl = $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
		$items = $objectManager->create('Laurensmedia\Productdesigner\Model\ResourceModel\Bulkexport\Collection')
			->addFieldToFilter('finished', '1');
		$itemsToProcess = $objectManager->create('Laurensmedia\Productdesigner\Model\ResourceModel\Bulkexport\Collection')
			->addFieldToFilter('finished', array('neq' => '1'));
		$types = array('engraving', 'sublimation', 'printing', 'wood');
		$output = array(
			'items' => array(),
			'downloads' => array(),
			'processing' => array()
		);
		foreach($items as $item){
			$itemId = $item->getId();
			$output['items'][$itemId] = array(
				'pdf_file_printing' => $item->getPdfFilePrinting(),
				'pdf_file_sublimation' => $item->getPdfFileSublimation(),
				'pdf_file_engraving' => $item->getPdfFileEngraving(),
				'pdf_file_wood' => $item->getPdfFileWood(),
				'store_id' => $item->getStoreId(),
				'base_url' => $mediaUrl.'productdesigner/order_export/'
			);
			foreach($types as $type){
				$file = $item->getData('pdf_file_'.$type);
				if($file != ''){
					$output['downloads'][] = 'store-'.$item->getStoreId().'-type-'.$type;
				}
			}
		}
		foreach($itemsToProcess as $item){
			$itemId = $item->getId();
			$orderItem = $objectManager->create('Magento\Sales\Model\Order\Item')->load($item->getOrderItemId());
			foreach($types as $type){
				$product = $orderItem->getProduct();
				$productPrintingTypes = explode(',', str_replace(' ', '', $product->getResource()->getAttribute('technology')->getFrontend()->getValue($product)));
				if(in_array($type, $productPrintingTypes)){
					$output['processing'][] = 'store-'.$orderItem->getStoreId().'-type-'.$type;
				}
			}
		}
		
		$output['processing'] = array_unique(array_filter($output['processing']));
		$output['downloads'] = array_unique(array_filter($output['downloads']));
		echo json_encode($output);exit;
	}
}