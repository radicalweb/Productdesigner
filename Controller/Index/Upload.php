<?php
namespace Laurensmedia\Productdesigner\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Filesystem\DirectoryList;

class Upload extends Action
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
		
		$minimalImageWidth = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('lm_productdesigner/lm_pd_settings/lm_pd_imagewidth');
		$minimalImageHeight = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('lm_productdesigner/lm_pd_settings/lm_pd_imageheight');
/*
		$mediumImageWidth = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('lm_productdesigner/lm_pd_settings/lm_pd_imagewidthmedium');
		$mediumImageHeight = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('lm_productdesigner/lm_pd_settings/lm_pd_imageheightmedium');
		$goodImageWidth = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('lm_productdesigner/lm_pd_settings/lm_pd_imagewidthfine');
		$goodImageHeight = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('lm_productdesigner/lm_pd_settings/lm_pd_imageheightfine');
*/

		$basePath = $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).'/';
		if(!isset($_FILES['images']) || !$_FILES['images']['name']){
	        $result->setData(array('html' => 'Geen bestand ingevoerd'));
	        return $result;
		}
		
		// Upload the file
		try{
			$images = $this->getRequest()->getFiles('images');
			$uploader = $objectManager->create(
				'Magento\MediaStorage\Model\File\Uploader',
				['fileId' => 'images[0]']
			);
			$uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'svg']);
			$imageAdapter = $objectManager->get('Magento\Framework\Image\AdapterFactory')->create();
			$uploader->setAllowRenameFiles(true);
			$uploader->setFilesDispersion(false);
			$mediaDirectory = $objectManager->get('Magento\Framework\Filesystem')
				->getDirectoryRead(DirectoryList::MEDIA);
			$savePath = $mediaDirectory->getAbsolutePath('productdesigner_uploads').'/'.date('Y').'/'.date('m').'/'.date('d').'/';
			$uploadResult = $uploader->save($savePath, $this->randomKey(20).'.'.$uploader->getFileExtension());
			if($uploadResult['error']==0)
			{
				// If necessary, rotate image for mobile uploads
				if($uploadResult['type'] == 'image/jpeg'){
					$exif = exif_read_data($uploadResult['path'].$uploadResult['file']);
					if(!empty($exif['Orientation'])) {
						switch($exif['Orientation']) {
							case 8:
								$image = imagecreatefromstring(file_get_contents($uploadResult['path'].$uploadResult['file']));
								$image = imagerotate($image,90,0);
								imagejpeg($image, $uploadResult['path'].$uploadResult['file']);
								imagedestroy($image);
								break;
							case 3:
								$image = imagecreatefromstring(file_get_contents($uploadResult['path'].$uploadResult['file']));
								$image = imagerotate($image,180,0);
								imagejpeg($image, $uploadResult['path'].$uploadResult['file']);
								imagedestroy($image);
								break;
							case 6:
								$image = imagecreatefromstring(file_get_contents($uploadResult['path'].$uploadResult['file']));
								$image = imagerotate($image,-90,0);
								imagejpeg($image, $uploadResult['path'].$uploadResult['file']);
								imagedestroy($image);
								break;
						}
					}
				}
				if($this->getRequest()->getParam('grayscale') == 'true' && file_exists($uploadResult['path'].$uploadResult['file'])){
					exec("/usr/bin/convert ".$uploadResult['path'].$uploadResult['file']." -set colorspace Gray -separate -average ".$uploadResult['path'].$uploadResult['file']);
				}
				$url = $mediaUrl.'productdesigner_uploads'.'/'.date('Y').'/'.date('m').'/'.date('d').'/'.$uploadResult['file'];
				$ext = pathinfo($url, PATHINFO_EXTENSION);
				if($ext == 'svg' || $ext == 'SVG'){
					$width = 300;
					$height = 300;
				} else {
					list($width, $height) = getimagesize($savePath.$uploadResult['file']);
					
					// Check dimensions
					if($width < $minimalImageWidth || $height < $minimalImageHeight){
						// Error
						$result->setData(array('error' => 'Image quality not good enough'));
						return $result;
/*
					} elseif(($width >= $minimalImageWidth && $width < $mediumImageWidth) || ($height >= $minimalImageHeight && $height < $mediumImageHeight)){
						// Bad quality
						$result->setData(array('message' => 'Image quality is not very good', 'image_src' => $url, 'filename' => basename($url)));
						return $result;
					} elseif(($width >= $mediumImageWidth && $width < $goodImageWidth) || ($height >= $mediumImageHeight && $height < $goodImageHeight)){
						// Medium quality
						$result->setData(array('message' => 'Image quality is medium, but not great', 'image_src' => $url, 'filename' => basename($url)));
						return $result;
*/
					}
					
					$factor = $width / $height;
					$width = 300;
					$height = $width / $factor;
				}
				$result->setData(array('image_src' => $url, 'filename' => basename($url)));
// 				$result->setData(array('url' => $url, 'width' => $width, 'height' => $height));
				return $result;
			}
		} catch (\Exception $e) {
	        $result->setData(array('html' => $e->getMessage()));
	        return $result;
        }

        
        $result->setData(array('html' => 'Upload mislukt'));
        return $result;
    }

	public function randomKey($length) {
	    $pool = array_merge(range(0,9), range('a', 'z'),range('A', 'Z'));
		$key = '';
	    for($i=0; $i < $length; $i++) {
	        $key .= $pool[mt_rand(0, count($pool) - 1)];
	    }
	    return $key;
	}
}