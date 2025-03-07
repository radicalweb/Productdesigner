<?php
namespace Laurensmedia\Productdesigner\Controller\Adminhtml\imagecategories;

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
        $data = $this->getRequest()->getPostValue();
        
        
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            $model = $this->_objectManager->create('Laurensmedia\Productdesigner\Model\Imagecategories');

            $id = $this->getRequest()->getParam('id');
            if ($id) {
                $model->load($id);
                $model->setCreatedAt(date('Y-m-d H:i:s'));
            }
            $images = $data['images'];
//             $data['stores'] = implode(',', $data['stores']);
			
            $model->setData($data);

            try {
                $model->save();
                
				$imageModel = $this->_objectManager->create('Laurensmedia\Productdesigner\Model\Images');
				$catId = $model->getId();
				$allImages = $this->_objectManager->create('Laurensmedia\Productdesigner\Model\ResourceModel\Images\Collection')
					->addFieldToFilter('categorie', array('finset' => $id));
				foreach($allImages as $image){
					$image = $image->getId();
					$imageModel->load($image);
	                $categories = explode(',', $imageModel->getCategorie());
					if (($key = array_search($catId, $categories)) !== false) {
					    unset($categories[$key]);
					}
	                $categories = implode(',', array_filter(array_unique($categories)));
	                $imageModel->setId($image)->setData(array('id' => $image, 'categorie' => $categories));
	                $imageModel->save();
				}

				foreach($images as $image){
					$imageModel->load($image);
	                $categories = explode(',', $imageModel->getCategorie());
	                $categories[] = $catId;
	                $categories = implode(',', array_filter(array_unique($categories)));
	                $imageModel->setId($image)->setData(array('id' => $image, 'categorie' => $categories));
	                $imageModel->save();
				}

                $this->messageManager->addSuccess(__('The Imagecategories has been saved.'));
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId(), '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the Imagecategories.'));
            }

            $this->_getSession()->setFormData($data);
            return $resultRedirect->setPath('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }
}