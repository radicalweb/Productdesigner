<?php
namespace Laurensmedia\Productdesigner\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;

class Checklogin extends Action
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
        $customerSession = $objectManager->get('Magento\Customer\Model\Session');
        $loggedIn = false;
        if($customerSession->isLoggedIn()) {
           $loggedIn = true;
        }

        $result->setData(array('logged_in' => $loggedIn));
        return $result;
    }
}