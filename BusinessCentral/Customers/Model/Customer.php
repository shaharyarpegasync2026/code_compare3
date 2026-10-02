<?php

/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

namespace BusinessCentral\Customers\Model;

use BusinessCentral\Customers\Api\Data\CustomerInterface;
use Magento\Framework\Model\AbstractModel;

class Customer extends AbstractModel implements CustomerInterface
{
    /**
     * @var string
     */
    const ENTITY_ID = 'entity_id';

    /**
     * @var string
     */
    const EMAIL = 'email';

    /**
     * @var string
     */
    const DYNAMICS365_CODE = 'dynamics365_code';

    /**
     * @var string
     */
    const CREATED_AT = 'created_at';

    protected function _construct()
    {
        $this->_init(\BusinessCentral\Customers\Model\ResourceModel\Customer::class);
    }

    public function getEntityId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    public function getEmail()
    {
        return $this->getData(self::EMAIL);
    }

    public function setEmail($email)
    {
        return $this->setData(self::EMAIL, $email);
    }

    public function getDynamics365Code()
    {
        return $this->getData(self::DYNAMICS365_CODE);
    }

    public function setDynamics365Code($dynamics365Code)
    {
        return $this->setData(self::DYNAMICS365_CODE, $dynamics365Code);
    }

    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}
