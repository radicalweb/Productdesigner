<?php
namespace Laurensmedia\Productdesigner\Block;
class Index extends \Magento\Framework\View\Element\Template
{
    public function _prepareLayout()
    {
        parent::_prepareLayout();
//         $this->pageConfig->getTitle()->set(__('Product Designer'));
        return $this;
    }

	public function get_current_product_id(){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $registry = $objectManager->get('Magento\Framework\Registry');
        $product = $registry->registry('current_product')->getId();
        return $product;
	}
	
	public function get_media_url(){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$mediaDirectory = $objectManager->get('Magento\Framework\Filesystem')
			->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
		$mediaUrl = $objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
		return $mediaUrl;
	}
	
	public function get_base_url(){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$mediaDirectory = $objectManager->get('Magento\Framework\Filesystem')
			->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
		$baseUrl = $objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
		return $baseUrl;
	}
	
	public function get_media_dir($dir){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$mediaDirectory = $objectManager->get('Magento\Framework\Filesystem')
			->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
		$result = $mediaDirectory->getAbsolutePath($dir);
		return $result;
	}
	
	public function get_base_dir($dir){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$mediaDirectory = $objectManager->get('Magento\Framework\Filesystem')
			->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::LIB_INTERNAL);
		$result = $mediaDirectory->getAbsolutePath($dir);
		return $result;
	}

	public function resize($newWidth, $targetFile, $originalFile) {
		if(!file_exists($originalFile)){
			return;
		}
	    $info = getimagesize($originalFile);
	    $mime = $info['mime'];
	
	    switch ($mime) {
	            case 'image/jpeg':
	                    $image_create_func = 'imagecreatefromjpeg';
	                    $image_save_func = 'imagejpeg';
	                    $new_image_ext = 'jpg';
	                    break;
	
	            case 'image/png':
	                    $image_create_func = 'imagecreatefrompng';
	                    $image_save_func = 'imagepng';
	                    $new_image_ext = 'png';
	                    break;
	
	            case 'image/gif':
	                    $image_create_func = 'imagecreatefromgif';
	                    $image_save_func = 'imagegif';
	                    $new_image_ext = 'gif';
	                    break;
	
	            default: 
	                    throw new Exception('Unknown image type.');
	    }
	
	    $img = $image_create_func($originalFile);
	    list($width, $height) = getimagesize($originalFile);
	
	    $newHeight = ($height / $width) * $newWidth;
	    $tmp = imagecreatetruecolor($newWidth, $newHeight);
		imagesavealpha($tmp, true);
		imagealphablending($tmp, false);
		# important part two
		$white = imagecolorallocatealpha($tmp, 255, 255, 255, 127);
		imagefill($tmp, 0, 0, $white);
	    imagecopyresampled($tmp, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
	
	    if (file_exists($targetFile)) {
	            unlink($targetFile);
	    }
	    $image_save_func($tmp, "$targetFile");
	}

}