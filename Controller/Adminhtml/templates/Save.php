<?php
namespace Laurensmedia\Productdesigner\Controller\Adminhtml\templates;

use Magento\Backend\App\Action;
use Magento\Framework\App\Filesystem\DirectoryList;


class Save extends \Magento\Backend\App\Action
{

    /**
     * @param Action\Context $context
     */
    public function __construct(
    	Action\Context $context,
		\Magento\Store\Model\StoreManagerInterface $manStore,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ){
        $this->productRepository = $productRepository;
        $this->manStore = $manStore;
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
        
        
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            $model = $this->_objectManager->create('Laurensmedia\Productdesigner\Model\Templates');

            $id = $this->getRequest()->getParam('id');
            if ($id) {
                $model->load($id);
                $model->setCreatedAt(date('Y-m-d H:i:s'));
            }
            
            if(isset($data['is_duplicate']) && $data['is_duplicate'] == 'true'){
                $saveModel = $this->_objectManager->create('Laurensmedia\Productdesigner\Model\Templates');
                $saveData = $model->getData();
                unset($saveData['id']);
                unset($saveData['created_at']);
                $saveData['title'] = $saveData['title']. '(copy)';
                $saveModel->addData($saveData);
                $saveModel->save();
                
                $this->messageManager->addSuccess(__('The Templates has been duplicated.'));
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);
                return $resultRedirect->setPath('*/*/edit', ['id' => $saveModel->getId()]);
            } else {
                $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyz';
                $data['password'] = substr(str_shuffle($permitted_chars), 0, 10);
                
                if(isset($_FILES['template_preview']) && $_FILES['template_preview']['tmp_name'] != ''){
    		        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    				$sideId = $objectManager->create('Laurensmedia\Productdesigner\Model\ResourceModel\Templatedata\Collection')
    					->addFieldToFilter('template_id', $id)
    					->getFirstItem()
    					->getId();
    				$mediaDirectory = $objectManager->get('Magento\Framework\Filesystem')
    					->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
    	            $saveLocation = $mediaDirectory->getAbsolutePath().'productdesigner/templates/'.$sideId.'.jpg';
    	            copy($_FILES['template_preview']['tmp_name'], $saveLocation);
                }
    			
                $model->addData($data);
    
                try {
                    $model->save();
                    $this->messageManager->addSuccess(__('The Templates has been saved.'));
                    $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);
                    if ($this->getRequest()->getParam('back')) {
                        return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId(), '_current' => true]);
                    }
                    
    				$resultRedirect = $this->resultRedirectFactory->create();
    				$route = 'productdesigner';
                    
                    
                    $product = $this->productRepository->getById($model->getProductId());
    				$store = $this->manStore->getStore(1);
    				$url = $product->getProductUrl().'/?product_id='.$model->getProductId().'&edit_template=1&template_id='.$model->getId().'&password='.$data['password'];
    				return $resultRedirect->setUrl($url);
                    //return $resultRedirect->setPath('*/*/');
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    $this->messageManager->addError($e->getMessage());
                } catch (\RuntimeException $e) {
                    $this->messageManager->addError($e->getMessage());
                } catch (\Exception $e) {
                    $this->messageManager->addException($e, __('Something went wrong while saving the Templates.'));
                }
            }

            $this->_getSession()->setFormData($data);
            return $resultRedirect->setPath('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }
}