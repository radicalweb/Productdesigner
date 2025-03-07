<?php
namespace Laurensmedia\Productdesigner\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;

class Customprice implements ObserverInterface
{

    public function __construct(
        RequestInterface $request
    ) {
        $this->_request = $request;
    }
    
    public function execute(\Magento\Framework\Event\Observer $observer) {
        $data = $this->_request->getParam('cart');
        $connectId = $this->_request->getParam('connect_id');
        $options = $this->_request->getParam('options');
        $isUpdate = $this->_request->getParam('isupdatequoteitem');
        if(!empty($data)){
	        $finalPrice = $data['finalprice'];
	        $item = $observer->getEvent()->getData('quote_item');
	        $product = $observer->getEvent()->getData('product');
/*
	        $item->setCustomPrice($finalPrice);
	        $item->setOriginalCustomPrice($finalPrice);
	        // Enable super mode on the product.
	        $item->getProduct()->setIsSuperMode(true);
*/

	        $product_id = $data['productid'];
	        $finalPrice = $data['finalprice'];
	        $number = $data['number'] - 1;
	        $color = isset($data['color']) ? $data['color'] : '';
	        $druktype = isset($data['druktype']) ? $data['druktype'] : '';
	        $bevestiging = isset($data['bevestiging']) ? $data['bevestiging'] : '';
	        $sizes = isset($data['sizes']) ? $data['sizes'] : '';
	        $sizeshtml = "";
	        if ($sizes != "") {
	            foreach ($sizes as $size) {
	                $sizeshtml .= $size['name'].": ".$size['amount']."x, ";
	            }
	        }
	        $svgdata = array();
	        $jsondata = array();
	        $colorimages = array();
	        $width = array();
	        $height = array();
	        $x = array();
	        $y = array();
	        $labels = array();
	        $html = '';
	        for($i=0; $i<=$number; $i++) {
	            $label = $data[$i]['label'];
	            $labels[] = $label;
	            $svgdata[$label] = $data[$i]['svg'];
	            $jsondata[$label] = $data[$i]['json'];
	            $colorimages[$label] = $data[$i]['image'];
	            $width[$label] = $data[$i]['width'];
	            $height[$label] = $data[$i]['height'];
	            $x[$label] = $data[$i]['x'];
	            $y[$label] = $data[$i]['y'];
				$rand = rand(1, 99999);

                $previewwidth = $width[$label] / 5.67;
                $previewheight = $height[$label] / 5.67;
                $previewx = $x[$label] / 5.67;
                $previewy = $y[$label] / 5.67;
	        }
	        
	        // Store data
	        $saveData = array(
		        'labels' => $labels,
		        //'svgdata' => $svgdata,
		        //'jsondata' => $jsondata,
		        'colorimages' => $colorimages,
		        'width' => $width,
		        'height' => $height,
		        'x' => $x,
		        'y' => $y,
		        'connect_id' => $connectId,
		        'color' => $color
	        );
	        $item->setProductdesignerData(json_encode($saveData));
	    }
        return $this;
    }

}