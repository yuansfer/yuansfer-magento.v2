<?php


namespace Pockyt\All\Model;


class AlipaySettleCurrency implements \Magento\Framework\Option\ArrayInterface
{

    public function toOptionArray()
    {
        return array(
            ['value' => 'USD', 'label' => __('USD')],
            ['value' => 'GBP', 'label' => __('GBP')],
        );
    }

    public function toArray()
    {
        return array(
            'USD' => __('USD'),
            'GBP' => __('GBP'),
        );
    }
}