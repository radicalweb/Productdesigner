<?php
namespace Laurensmedia\Productdesigner\Ui\Component\MassAction;

use Magento\Framework\UrlInterface;

abstract class OptionsAbstract
{

    /**
     * Additional options params
     *
     * @var array
     */
    protected $data;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * Sub-actions Base URL
     *
     * @var string
     */
    protected $urlPath;

    /**
     * Options
     *
     * @var array
     */
    protected $options = [];

    /**
     * @param UrlInterface $urlBuilder
     * @param array $data
     */
    public function __construct(UrlInterface $urlBuilder, array $data = [])
    {
        $this->data = $data;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Render options similar for all classes
     */
    protected function getMatchingOptions()
    {
        $this->options['order_bulkexport'] = [
            'type' => 'order_bulkexport',
            'label' => __('Bulk export'),
            'url' => $this->urlBuilder->getUrl(
                'productdesigner/bulkexport/addfromgrid'
            )
        ];
        
        $this->options['order_bulkexport_wood'] = [
            'type' => 'order_bulkexport_wood',
            'label' => __('Bulk export for wood board'),
            'url' => $this->urlBuilder->getUrl(
                'productdesigner/bulkexport/addfromgrid/iswood/true/'
            )
        ];
        
        $this->options['order_bulkexport_engraving'] = [
            'type' => 'order_bulkexport_engraving',
            'label' => __('Bulk export for engraving products'),
            'url' => $this->urlBuilder->getUrl(
                'productdesigner/bulkexport/engravingzip/'
            )
        ];

    }
}
