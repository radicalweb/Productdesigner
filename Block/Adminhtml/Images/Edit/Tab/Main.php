<?php

namespace Laurensmedia\Productdesigner\Block\Adminhtml\Images\Edit\Tab;

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
            'label',
            'text',
            [
                'name' => 'label',
                'label' => __('Label'),
                'title' => __('Label'),
				'required' => true,
                'disabled' => $isElementDisabled
            ]
        );
									

        $fieldset->addField(
            'image',
            'image',
            [
                'name' => 'image',
                'label' => __('Image (jpg,png,gif,svg)'),
                'title' => __('Image (jpg,png,gif,svg)'),
				'required' => true,
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
	    $model->addData(array('categories' => explode(',', $model->getData('categorie'))));
	    
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
        $model->setData('image', 'productdesigner_images/'.$model->getData('url'));

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
