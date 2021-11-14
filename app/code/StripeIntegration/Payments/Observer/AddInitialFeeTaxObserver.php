<?php

namespace StripeIntegration\Payments\Observer;

use Magento\Framework\Event\ObserverInterface;
use StripeIntegration\Payments\Helper\Logger;

class AddInitialFeeTaxObserver implements ObserverInterface
{
    public $helper = null;

    public function __construct(
        \StripeIntegration\Payments\Helper\GenericFactory $paymentsHelper,
        \StripeIntegration\Payments\Model\Config $config
    )
    {
        $this->paymentsHelperFactory = $paymentsHelper;
        $this->config = $config;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->config->isSubscriptionsEnabled())
            return $this;

        $total = $observer->getData('total');
        $quote = $observer->getData('quote');

        if ($total && $total->getInitialFeeAmount() > 0)
            $this->applyInitialFeeTax($quote, $total);

        return $this;
    }

    public function applyInitialFeeTax($quote, $total)
    {
        if ($this->config->priceIncludesTax())
            return;

        $baseExtraTax = 0;
        $extraTax = 0;
        if (!$this->helper)
            $this->helper = $this->paymentsHelperFactory->create();

        foreach ($quote->getAllItems() as $item)
        {
            $appliedTaxes = $item->getAppliedTaxes();
            if (empty($appliedTaxes))
                continue;

            $product = $this->helper->getSubscriptionProductFrom($item);
            $baseInitialFee = $product->getStripeSubInitialFee();

            if (empty($baseInitialFee) || !is_numeric($baseInitialFee) || $baseInitialFee <= 0)
                continue;

            $qty = $item->getQty();
            $baseExtraTaxableAmount = $qty * $baseInitialFee;
            $taxPercent = $item->getTaxPercent();
            $baseExtraTax += $baseExtraTaxableAmount * ($taxPercent / 100);
        }

        $rate = $quote->getBaseToQuoteRate();
        if (empty($rate))
            $rate = 1;

        $baseExtraTax = round($baseExtraTax, 4);
        $extraTax = round($baseExtraTax * $rate, 4);
        $total->addTotalAmount('tax', $extraTax);
        $total->addBaseTotalAmount('tax', $baseExtraTax);
        $total->setGrandTotal($total->getGrandTotal() + $extraTax);
        $total->setBaseGrandTotal($total->getBaseGrandTotal() + $baseExtraTax);
    }
}
