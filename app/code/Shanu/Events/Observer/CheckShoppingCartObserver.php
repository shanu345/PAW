<?php
/***/
namespace Shanu\Events\Observer;

use Magento\Framework\Event\ObserverInterface;


class CheckShoppingCartObserver implements ObserverInterface
{
    protected $_logger;
    protected $_orderFactory; 
    protected $accountManagement;
    protected $_objectManager;
    protected $orderCustomerService;
 
    public function __construct(        
        \Psr\Log\LoggerInterface $loggerInterface,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Customer\Api\AccountManagementInterface $accountManagement,
        \Magento\Sales\Api\OrderCustomerManagementInterface $orderCustomerService,
        \Magento\Framework\ObjectManager\ObjectManager $objectManager
    ) {
 
        $this->_logger = $loggerInterface;
        $this->_orderFactory = $orderFactory;
        $this->accountManagement = $accountManagement;
        $this->orderCustomerService = $orderCustomerService;
        $this->_objectManager = $objectManager;
 
 
    }
 
    public function execute(\Magento\Framework\Event\Observer $observer ) { 
 
        $orderIds = $observer->getEvent()->getOrderIds();
        $orderdata =array();
        if (count($orderIds)) {
 
            $orderId = $orderIds[0];
            $order = $this->_orderFactory->create()->load($orderId);
 
            /*Convert guest to customer*/
            if ($order->getEntityId() && $this->accountManagement->isEmailAvailable($order->getEmailAddress())) {
                $this->orderCustomerService->create($orderId);
 
            }
            /*END*/
 
        }
 
 
 
    }
}
?>