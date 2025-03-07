<?php
namespace Laurensmedia\Productdesigner\Ui\Component\Listing\Column;
 
use \Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Framework\View\Element\UiComponent\ContextInterface;
use \Magento\Framework\View\Element\UiComponentFactory;
use \Magento\Ui\Component\Listing\Columns\Column;
use \Magento\Framework\Api\SearchCriteriaBuilder;
 
class Custom extends Column
{
 
	protected $_orderRepository;
	protected $_searchCriteria;
	protected $_orderCollectionFactory;
	protected $orderResourceModel;
	protected $order;
 
	public function __construct(
		ContextInterface $context,
		UiComponentFactory $uiComponentFactory,
		OrderRepositoryInterface $orderRepository,
		SearchCriteriaBuilder $criteria,
		\Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
		\Magento\Sales\Model\ResourceModel\Order $orderResourceModel,
		\Magento\Sales\Model\Order $order,
		array $components = [],
		array $data = []
	){
		$this->_orderRepository = $orderRepository;
		$this->_searchCriteria  = $criteria;
		$this->_orderCollectionFactory = $orderCollectionFactory;
		$this->orderResourceModel = $orderResourceModel;
		$this->_order = $order;
		parent::__construct($context, $uiComponentFactory, $components, $data);
	}
 
	public function prepareDataSource(array $dataSource)
	{
		// $orderIds = array();
		// if (isset($dataSource['data']['items'])) {
		// 	foreach ($dataSource['data']['items'] as & $item) {
		// 		$orderIds[] = $item["entity_id"];
		// 	}
		// }
		// $orderIds = array_unique(array_filter($orderIds));
		// 
		// $collection = $this->_orderCollectionFactory->create()
		// 	->addAttributeToSelect('*')
		// 	->addFieldToFilter('entity_id', $orderIds);
		// 	
		// $orders = array();
		// foreach($collection as $order){
		// 	$id = $order->getId();
		// 	$orders[$id] = $order;
		// }
		
		if (isset($dataSource['data']['items'])) {
			foreach ($dataSource['data']['items'] as & $item) {
				if(!isset($item['custom_info_labels']) || $item['custom_info_labels'] == ''){
					// $order = $this->_orderRepository->get($item["entity_id"]);
					$order = $this->_order->load($item["entity_id"]);
					if($order->getCustomInfoLabels() != ''){
						$item[$this->getData('name')] = $order->getCustomInfoLabels();
						continue;
					}
					// $order = $orders[$item["entity_id"]];
					$shippingMethod = $order->getShippingDescription();
					
					$labels = array();
					// $labels[] = '<span class="custom-label shipping-label">'.$order->getShippingDescription().'</span>';
					if(strpos($shippingMethod, 'Colissimo') !== false){
						$labels[] = '<span class="custom-label shipping-label" style="background:green; color: white;">Colissimo</span>';
					} elseif(strpos($shippingMethod, 'Chronopost') !== false){
						$labels[] = '<span class="custom-label shipping-label" style="background:blue; color: white;">Chronopost</span>';
					} elseif(strpos($shippingMethod, 'lettre_suivie') !== false){
						$labels[] = '<span class="custom-label shipping-label">Lettre suivie</span>';
					}
					
					$orderItems = $order->getAllItems();
					foreach($orderItems as $orderItem){
						$addHtml = '<span class="custom-label product-options-label">'.$orderItem->getName();
						$options = $orderItem->getProductOptions();
						if(isset($options['options'])){
							$options = $options['options'];
							$addHtml .= '<ul>';
							foreach($options as $option){
								$addHtml .= '<li><span>'.$option['label'].'</span>: '.$option['value'].'</li>';
							}
							$addHtml .= '</ul>';
						}
						$addHtml .= '</span>';
						$labels[] = $addHtml;
					}
					
					$labelHtml = implode('', $labels);
					
					$item[$this->getData('name')] = $labelHtml;
					
					$order->setCustomInfoLabels($labelHtml);
					$order->save();
					// $this->_orderRepository->save($order);
				} else {
					
				}
			}
		}
		return $dataSource;
	}
}