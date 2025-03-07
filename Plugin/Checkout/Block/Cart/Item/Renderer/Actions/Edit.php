<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Laurensmedia\Productdesigner\Plugin\Checkout\Block\Cart\Item\Renderer\Actions;

/**
 * @api
 * @since 100.0.2
 */
class Edit extends \Magento\Checkout\Block\Cart\Item\Renderer\Actions\Edit
{

    public function afterGetConfigureUrl(
        \Magento\Checkout\Block\Cart\Item\Renderer\Actions\Edit $subject,
        $result
    ) {
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $data = json_decode($subject->getItem()->getProductdesignerData(), true);
        if(isset($data['connect_id']) && $data['connect_id'] != ''){
	        return $result.'?quoteitem='.$subject->getItem()->getId().'&saved='.$data['connect_id']['connect_id'];
			return $subject->getItem()->getProduct()->getProductUrl().'?quoteitem='.$subject->getItem()->getId().'&saved='.$data['connect_id']['connect_id'];
        }
        return $result; 
    }
}
