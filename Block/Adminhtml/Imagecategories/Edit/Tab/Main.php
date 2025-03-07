<?php

namespace Laurensmedia\Productdesigner\Block\Adminhtml\Imagecategories\Edit\Tab;

/**
 * Imagecategories edit form main tab
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
        $model = $this->_coreRegistry->registry('imagecategories');

        $isElementDisabled = false;

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('page_');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Item Information')]);

        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
        }

		
        $fieldset->addField(
            'label',
            'text',
            [
                'name' => 'label',
                'label' => __('Category name'),
                'title' => __('Category name'),
				'required' => true,
                'disabled' => $isElementDisabled
            ]
        );

        $fieldset->addField(
            'is_background',
            'select',
            [
                'label' => __('Use as background pattern'),
                'title' => __('Use as background pattern'),
                'name' => 'is_background',
                'options' => array(
	                0 => __('No'),
	                1 => __('Yes'),
                ),
                'disabled' => $isElementDisabled
            ]
        );

        $fieldset->addField(
            'is_frame',
            'select',
            [
                'label' => __('Use as frame image'),
                'title' => __('Use as frame image'),
                'name' => 'is_frame',
                'options' => array(
	                0 => __('No'),
	                1 => __('Yes'),
                ),
                'disabled' => $isElementDisabled
            ]
        );
        
        $fieldset->addField(
            'scale_factor',
            'text',
            [
                'name' => 'scale_factor',
                'label' => __('Scale factor (percentage)'),
                'title' => __('Scale factor (percentage)'),
                'required' => false,
                'disabled' => $isElementDisabled
            ]
        );
											

		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $images = $objectManager->create('Laurensmedia\Productdesigner\Model\ResourceModel\Images\Collection')->load();
        $finalImages = array();
        if($model->getId()){
	        foreach($images as $image){
		        $categories = explode(',', $image->getCategorie());
		        if(in_array($model->getId(), $categories)){
			        $finalImages[] = $image->getId();
		        }
	        }
	    }
	    $model->addData(array('images' => $finalImages));
	    
        $fieldset->addField(
            'images',
            'multiselect',
            [
                'label' => __('Images'),
                'title' => __('Images'),
                'name' => 'images',
                'values' => \Laurensmedia\Productdesigner\Block\Adminhtml\Imagecategories\Grid::getValueArray3(),
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
