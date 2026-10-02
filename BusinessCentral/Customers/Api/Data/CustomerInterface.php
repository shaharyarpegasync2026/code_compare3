<?php

/**
 * Copyright © 2009-2025 Amasty. All Rights Reserved.
 * See LICENSE for license details.
 */

namespace BusinessCentral\Customers\Api\Data;

interface CustomerInterface
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

    public function getEntityId();

    public function setEntityId($entityId);

    public function getEmail();

    public function setEmail($email);

    public function getDynamics365Code();

    public function setDynamics365Code($dynamics365Code);

    public function getCreatedAt();

    public function setCreatedAt($createdAt);
}
