<?php
namespace Laurensmedia\Productdesigner\Block\Adminhtml\Fonts\Edit;

/**
 * Admin page left menu
 */
class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('fonts_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Fonts Information'));
    }
}