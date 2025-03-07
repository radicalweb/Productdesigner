<?php
namespace Laurensmedia\Productdesigner\Controller\Adminhtml\images;

use Magento\Backend\App\Action;
use Magento\Framework\App\Filesystem\DirectoryList;


class Savebulk extends \Magento\Backend\App\Action
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
        
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            $model = $this->_objectManager->create('Laurensmedia\Productdesigner\Model\Images');
			
			foreach($_FILES['image']['name'] as $count => $filename)
			{
				try{

// 					move_uploaded_file($_FILES['image']['tmp_name'][$count], $temp);
					
					$uploader = $this->_objectManager->create(
						'Magento\MediaStorage\Model\File\Uploader',
						['fileId' => 'image['.$count.']']
					);
					$uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png', 'svg']);
					/** @var \Magento\Framework\Image\Adapter\AdapterInterface $imageAdapter */
					$imageAdapter = $this->_objectManager->get('Magento\Framework\Image\AdapterFactory')->create();
					$uploader->setAllowRenameFiles(true);
					$uploader->setFilesDispersion(false);
					/** @var \Magento\Framework\Filesystem\Directory\Read $mediaDirectory */
					$mediaDirectory = $this->_objectManager->get('Magento\Framework\Filesystem')
						->getDirectoryRead(DirectoryList::MEDIA);
					$result = $uploader->save($mediaDirectory->getAbsolutePath('productdesigner_images'));
					if($result['error']==0)
					{
						$filename = $result['file'];
						$ext = pathinfo($filename, PATHINFO_EXTENSION);
						$data['image'] = 'productdesigner_images' . $result['file'];
						
						// Create thumb
						if($ext != 'svg'){
							$imageUrl = $mediaDirectory->getAbsolutePath('productdesigner_images') . '/' .$filename;
							$imageResized = $mediaDirectory->getAbsolutePath('productdesigner_images') . '/thumbs/' . $filename;
							
							$imageObj = $this->_objectManager->get('\Magento\Framework\Image\AdapterFactory')->create();
							$imageObj->open($imageUrl);
							$imageObj->constrainOnly(TRUE);
							$imageObj->keepAspectRatio(TRUE);
							$imageObj->keepFrame(false);
							$imageObj->keepTransparency(True);
							//$imageObj->setImageBackgroundColor(false);
							$imageObj->backgroundColor(false);
							$imageObj->quality(80);
							$imageObj->setWatermarkImageOpacity(0);
							$imageObj->resize(300);
							//destination folder                
							$destination = $imageResized ;    
							//save image      
							$imageObj->save($destination);
						} else {
							copy($mediaDirectory->getAbsolutePath('productdesigner_images').'/'.$filename, $mediaDirectory->getAbsolutePath('productdesigner_images').'/thumbs/'.$filename);
						}
					}
				} catch (\Exception $e) {
					//unset($data['image']);
	            }
	
				$data = array();
				$data['label'] = $filename;
				$data['url'] = "thumbs/".$filename;
	
				$data['categorie'] = implode(',', $postData['categories']);
	
				
	            $model->setData($data);
	
	            try {
	                $model->save();
	            } catch (\Magento\Framework\Exception\LocalizedException $e) {
	                $this->messageManager->addError($e->getMessage());
	            } catch (\RuntimeException $e) {
	                $this->messageManager->addError($e->getMessage());
	            } catch (\Exception $e) {
	                $this->messageManager->addException($e, __('Something went wrong while saving the Images.'));
	            }
	        }
        }
        $this->messageManager->addSuccess(__('The Images has been saved.'));
        $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);
        return $resultRedirect->setPath('*/*/');
    }
}