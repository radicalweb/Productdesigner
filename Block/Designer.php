<?php
namespace Laurensmedia\Productdesigner\Block;

use Magento\Framework\View\Element\Template;

class Designer extends Template
{
	public function __construct(Template\Context $context, array $data = [])
	{
		parent::__construct($context, $data);
	}
	
	protected function _prepareLayout()
	{
		return parent::_prepareLayout();
	}

}