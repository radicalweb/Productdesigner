<?php
namespace Laurensmedia\Productdesigner\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;

class Checkconnectid extends Action
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
		
		$connectId = $this->getRequest()->getParam('connectid');
		// $items = $objectManager->create('Laurensmedia\Productdesigner\Model\ResourceModel\Saved\Collection')
        //     ->addFieldToFilter('connect_id', $connectId);
            
        $existingSaves = $objectManager->create('Laurensmedia\Productdesigner\Model\ResourceModel\Saved\Collection')
            ->addFieldToSelect('connect_id')
            ->addFieldToFilter('connect_id', $connectId)
            ->setPageSize(1)
            ->setCurPage(1)
            ->load();
        if(count($existingSaves) > 0){
            $result->setData(array('count' => 1));
        } else {
            $result->setData(array('count' => 0)); 
        }

        // $result->setData(array('count' => count($items)));
        return $result;
    }
}