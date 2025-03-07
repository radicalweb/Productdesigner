<?php
namespace Laurensmedia\Productdesigner\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;

class Savetemplate extends Action
{

    /**
     * @var PageFactory
     */
    protected $_resultPageFactory;
 
    /**
     * @var JsonFactory
     */
    protected $_resultJsonFactory;
    
    protected $_filesystem;
 
 
    /**
     * View constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(Context $context, PageFactory $resultPageFactory, JsonFactory $resultJsonFactory, \Magento\Framework\Filesystem $filesystem)
    {
 
        $this->_resultPageFactory = $resultPageFactory;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_filesystem = $filesystem;
 
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
        $mediaPath = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath();
		
        $data = $this->getRequest()->getPost();
        
        $template = $objectManager->create('Laurensmedia\Productdesigner\Model\Templates')->load($data['template_id']);
        if($template->getPassword() == $data['password']){
	        // Delete old data
			$deleteItems = $objectManager->create('Laurensmedia\Productdesigner\Model\ResourceModel\Templatedata\Collection')
                ->addFieldToFilter('template_id', $template->getId());
            foreach ($deleteItems as $deleteItem) {
				$objectManager->create('Laurensmedia\Productdesigner\Model\Templatedata')
					->setId($deleteItem->getId())->delete();
            }
            
            // Now add data again
            if(!empty($data['json'])){
                $count = 0;
	            foreach($data['json'] as $label => $jsonData){
		            $saveData = array(
			            'template_id' => $template->getId(),
			            'label' => $label,
			            'svg' => $data['svg'][$count],
			            'json' => $data['json'][$label],
		            );
					$objectManager->create('Laurensmedia\Productdesigner\Model\Templatedata')->addData($saveData)->save();
                    
                    // Save image
                    if(isset($data['images']) && isset($data['images'][$label])){
                        file_put_contents($mediaPath.'productdesigner/templatethumbs/'.$data['template_id'].'-'.$label.'.png', base64_decode(str_replace('data:image/png;base64,', '', $data['images'][$label])));
                        
                    }
                    
                    $count++;
	            }
            }
        }
        echo 'Template opgeslagen';
    }
}