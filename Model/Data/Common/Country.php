<?php

namespace Oyst\OneClick\Model\Data\Common;

class Country extends \Magento\Framework\Api\AbstractSimpleObject implements \Oyst\OneClick\Api\Data\Common\CountryInterface
{
    public function getCode()
    {
        return $this->_get(self::CODE);
    }

    public function setCode($code)
    {
        return $this->setData(self::CODE , $code);
    }

    public function getLabel()
    {
        return $this->_get(self::LABEL);
    }

    public function setLabel($label)
    {
        return $this->setData(self::LABEL , $label);
    }
}
