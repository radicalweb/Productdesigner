<?php

namespace Laurensmedia\Productdesigner\Block\Adminhtml\Images\Bulk\Tab;

/**
 * Images edit form main tab
 */
class Main extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @var \Laurensmedia\Productdesigner\Model\Status
     */
    protected $_status;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Laurensmedia\Productdesigner\Model\Status $status,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
        $this->_status = $status;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form
     *
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        /* @var $model \Laurensmedia\Productdesigner\Model\BlogPosts */
        $model = $this->_coreRegistry->registry('images');

        $isElementDisabled = false;

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('page_');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Item Information')]);

        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
        }
									

        $fieldset->addField(
            'image',
            'file',
            [
                'name' => 'image[]',
                'label' => __('Images (jpg,png,gif,svg)'),
                'title' => __('Images (jpg,png,gif,svg)'),
				'required' => true,
				'multiple' => true,
                'disabled' => $isElementDisabled,
                'class' => 'multiple-upload'
            ]
        );


		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $imageCategories = $objectManager->create('Laurensmedia\Productdesigner\Model\ResourceModel\Imagecategories\Collection')->load();
        $finalCategories = array();
/*
        if($model->getId()){
	        foreach($imageCategories as $cat){
		        if(in_array($model->getId(), $categories)){
			        $finalCategories[] = $image->getId();
		        }
	        }
	    }
*/
	    $model->addData(array('images' => $finalCategories));
	    
        $fieldset->addField(
            'categories',
            'multiselect',
            [
                'label' => __('Categories'),
                'title' => __('Categories'),
                'name' => 'categories',
                'values' => \Laurensmedia\Productdesigner\Block\Adminhtml\Images\Grid::getValueArray3(),
                'disabled' => $isElementDisabled
            ]
        );
						
						

        if (!$model->getId()) {
            $model->setData('is_active', $isElementDisabled ? '0' : '1');
        }

        $form->setValues($model->getData());
        $this->setForm($form);
		
        return parent::_prepareForm();
    }

    /**
     * Prepare label for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Item Information');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Item Information');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
    
    public function getTargetOptionArray(){
    	return array(
    				'_self' => "Self",
					'_blank' => "New Page",
    				);
    }
}
