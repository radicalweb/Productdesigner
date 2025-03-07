<?php
namespace Laurensmedia\Productdesigner\Controller\Adminhtml\Bulkexport;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Sales\Controller\Adminhtml\Order\AbstractMassAction;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Framework\App\Filesystem\DirectoryList;

class Addfromgrid extends AbstractMassAction
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Sales::delete';

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * MassDelete constructor.
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        OrderRepository $orderRepository
    )
    {
        parent::__construct($context, $filter);

        $this->collectionFactory = $collectionFactory;
        $this->orderRepository   = $orderRepository;
    }

    /**
     * @param AbstractCollection $collection
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    protected function massAction(AbstractCollection $collection)
    {
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
		$baseUrl = $storeManager->getStore()->getBaseUrl();
		$mediaUrl = $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
		$storeConfig = $objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface');

		$layout = $this->_view->getLayout();
		$block = $layout->createBlock('Laurensmedia\Productdesigner\Block\Index');
		$helper = \Magento\Framework\App\ObjectManager::getInstance()->get('Laurensmedia\Productdesigner\Helper\Tcpdfhelper');

		$path = $block->get_media_dir('productdesigner/tmporder').'/';

		// Fetch order items to be printed
		$printOrderItems = array();

		$store_id = $storeManager->getStore()->getId();
		$exportCombining = $storeConfig->getValue('lm_productdesigner/lm_pd_bulkexport/lm_pd_bulkexport_arrange', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		$emptyDesign = $storeConfig->getValue('lm_productdesigner/lm_pd_bulkexport/lm_pd_bulkexport_emptyside', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		$pdfWidth = $storeConfig->getValue('lm_productdesigner/lm_pd_bulkexport/lm_pd_bulkexport_width', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		$pdfHeight = $storeConfig->getValue('lm_productdesigner/lm_pd_bulkexport/lm_pd_bulkexport_height', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		$pdfVerticalMargin = $storeConfig->getValue('lm_productdesigner/lm_pd_bulkexport/lm_pd_bulkexport_verticalmargin', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		$pdfHorizontalMargin = $storeConfig->getValue('lm_productdesigner/lm_pd_bulkexport/lm_pd_bulkexport_horizontalmargin', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		$pdfVerticalSpacing = $storeConfig->getValue('lm_productdesigner/lm_pd_bulkexport/lm_pd_bulkexport_verticalmargin_between', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		$pdfHorizontalSpacing = $storeConfig->getValue('lm_productdesigner/lm_pd_bulkexport/lm_pd_bulkexport_horizontalmargin_between', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		
        foreach ($collection->getItems() as $order) {
			$orderItems = $order->getAllItems();
			foreach($orderItems as $orderItem){
		        if($this->getRequest()->getParam('iswood') == 'true'){
			        $pdfWidth = 600;
			        $pdfHeight = 300;
			        $pdfVerticalMargin = 0;
			        $pdfHorizontalMargin = 0;
			        $exportCombining = 'wood_board';
			        $pdfVerticalSpacing = 1;
			        $pdfHorizontalSpacing = 1;
		        }
				$orderItemProductId = $orderItem->getProductId();
				$printOrderItems[] = array(
					'order_id' => $order->getId(),
					'product_id' => $orderItemProductId,
					'order_item_id' => $orderItem->getId(),
					'export_combining' => $exportCombining,
					'empty_design' => $emptyDesign,
					'pdf_width' => $pdfWidth,
					'pdf_height' => $pdfHeight,
					'pdf_margin_vertical' => $pdfVerticalMargin,
					'pdf_margin_horizontal' => $pdfHorizontalMargin,
					'pdf_margin_items_vertical' => $pdfVerticalSpacing,
					'pdf_margin_items_horizontal' => $pdfHorizontalSpacing
				);
			}
        }
        
		foreach($printOrderItems as $item){
            $objectManager->create('Laurensmedia\Productdesigner\Model\Bulkexport')
				->addData($item)
				->save();
		}
	    $resultRedirect = $objectManager->get('Magento\Framework\Controller\Result\RedirectFactory')->create();
	    $resultRedirect->setPath('productdesigner/bulkexport');
	    return $resultRedirect;
    }
}