<?php
namespace Laurensmedia\Productdesigner\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;

class Recolorlibraryimage extends \Magento\Framework\App\Action\Action
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
        
        $imageUrl = $this->getRequest()->getParam('imageUrl');
        $color = $this->getRequest()->getParam('newColor');
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
		$baseUrl = $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
		$basePath = $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).'/';
        $imagePath = $basePath.str_replace($baseUrl, '', $imageUrl);

		$layout = $this->_view->getLayout();
		$block = $layout->createBlock('Laurensmedia\Productdesigner\Block\Index');

        // Create color image if missing
        $image_name = str_replace($baseUrl.'productdesigner_images/', '', $imageUrl);
        $image_name = str_replace($baseUrl.'productdesigner_images/', '', $image_name);
        $image_name = str_replace('.png', '', $image_name);
        $checkCharacter = substr($image_name, -7, 1);
        if ($checkCharacter == '-') {
            $image_name = substr($image_name, 0, -7);
        }
        $color_image_location = $block->get_media_dir('').'productdesigner_images/'.$image_name.'.png';

        $color_image_save_location = $block->get_media_dir('').'productdesigner_images/'.$image_name.'.png';
        $color_image_save_location = str_replace('.png', '-'.str_replace('#', '', $color).'.png', $color_image_save_location);

        $returnUrl = $baseUrl.'productdesigner_images/'.$image_name.'.png';
        $returnUrl = str_replace('.png', '-'.str_replace('#', '', $color).'.png', $returnUrl);

        if (!file_exists($color_image_save_location)) {
            $im = $this->loadImage($color_image_location);
            $original_color = array('red' => 255, 'green' => 255, 'blue' => 255, 'alpha' => 127);
            $replacing_color = $this->hex2rgb($color);
            $colored_image = $this->recolorImage($im, $original_color, $replacing_color);
            imagepng($colored_image, $color_image_save_location);
            imagedestroy($im);
            imagedestroy($colored_image);
        }
        
        $result->setData(array('url' => $returnUrl));
        return $result;
    }
    
	public function loadImage($imagePath)
    {
        $resource = false;
        if (strstr($imagePath, '.jpg') || strstr($imagePath, '.jpeg')) {
            $resource = @imagecreatefromjpg($imagePath);
        } elseif (strstr($imagePath, '.png')) {
            $resource = @imagecreatefrompng($imagePath);
        }

        return $resource;
    }
    
    function recolorImage($img, $original_color, $replacing_color)
    {
        // pixel by pixel grid.
        $out = ImageCreateTrueColor(imagesx($img), imagesy($img));
        imagesavealpha($out, true);
        imagealphablending($out, false);
        $white = imagecolorallocatealpha($out, 255, 255, 255, 127);
        $rc = imagecolorallocatealpha($out, $replacing_color['red'], $replacing_color['green'], $replacing_color['blue'], 0);
        imagefill($out, 0, 0, $white);
        for ($y = 0; $y < imagesy($img); $y++) {
            for ($x = 0; $x < imagesx($img); $x++) {
                // find hex at x,y
                $at = imagecolorat($img, $x, $y);
                $colors = imagecolorsforindex($img, $at);

                // set $from to $to if hex matches.
                if ($colors['alpha'] == '127') {
                    imagesetpixel($out, $x, $y, $white);
                } elseif ($colors['red'] != $original_color['red']
                    || $colors['green'] != $original_color['green']
                    || $colors['blue'] != $original_color['blue']
                    || $colors['alpha'] != $original_color['alpha']) {
                    imagesetpixel($out, $x, $y, $rc);
                } else {
                    imagesetpixel($out, $x, $y, $white);
                }
            }
        }
        return $out;
    }

    function hex2rgb($hex)
    {
        $hex = str_replace("#", "", $hex);

        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1).substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1).substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1).substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        $rgb = array('red' => $r, 'green' => $g, 'blue' => $b);
       //return implode(",", $rgb); // returns the rgb values separated by commas
        return $rgb; // returns an array with the rgb values
    }
    
}