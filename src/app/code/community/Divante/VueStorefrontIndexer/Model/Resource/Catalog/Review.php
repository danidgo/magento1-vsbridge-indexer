<?php

/**
 * Class Divante_VueStorefrontIndexer_Model_Resource_Catalog_Product
 *
 * @package     Divante
 * @category    VueStoreFrontIndexer
 * @author      Jose Castaneda <jose@qbo.tech>
 * @copyright   Copyright (C) 2018 Divante Sp. z o.o.
 * @license     See LICENSE_DIVANTE.txt for license details.
 */
class Divante_VueStorefrontIndexer_Model_Resource_Catalog_Review
{
    /**
     * @var Mage_Core_Model_Resource
     */
    private $coreResource;

    /**
     * @var Varien_Db_Adapter_Interface
     */
    private $connection;

    /**
     * @var
     */
    private $entityId;

    /**
     * Divante_VueStorefrontIndexer_Model_Resource_Catalog_Attribute_Full constructor.
     */
    public function __construct()
    {
        $this->coreResource = Mage::getSingleton('core/resource');
        $this->connection = $this->coreResource->getConnection('catalog_read');
    }

    /**
     * @param int   $storeId
     * @param array $productIds
     * @param int   $fromId
     * @param int   $limit
     *
     * @return array
     */
    public function getReviews($storeId = 1, array $reviewIds = [], $fromId = 0, $limit = 1000)
    {
        $select = $this->connection->select()->from(
            ['e' => $this->coreResource->getTableName('review')],
            [
                'review_id',
                'created_at',
                'entity_pk_value',
                'status_id',
            ]
        );

        $select->where('e.status_id = ?', Mage_Review_Model_Review::STATUS_APPROVED);

        if (!empty($reviewIds)) {
            $select->where('e.review_id IN (?)', $reviewIds);
        }

        $entityId = $this->getProductEntityId();
        $select->where('entity_id = ? ', $entityId);

        $select->limit($limit)
            ->joinLeft(
                ['store' => $this->coreResource->getTableName('review_store')],
                'e.review_id = store.review_id',
                []
            )
            ->where('e.review_id > ?', $fromId)
            ->where('store.store_id = ?', $storeId)
            ->order('e.review_id ASC');
            
        $select = $this->joinReviewDetails($select);

        return $this->connection->fetchAll($select);
    }

    /**
     * @param string $entityCode
     *
     * @return int
     */
    public function getProductEntityId()
    {
        if (null === $this->entityId) {
            $connection = $this->getConnection();
            $select = $connection->select()
                ->from('review_entity', ['entity_id'])
                ->where('entity_code = :entity_code');

            $this->entityId = (int) $connection->fetchOne(
                $select,
                [':entity_code' => Mage_Review_Model_Review::ENTITY_PRODUCT_CODE]
            );
        }

        return $this->entityId;
    }

    /**
     * @param Varien_Db_Select $select
     * @param int $storeId
     *
     * @return Varien_Db_Select
     */
    private function joinReviewDetails(Varien_Db_Select $select)
    {
        $backendTable = 'review_detail';
        $defaultJoinCond = "d.review_id = e.review_id";

        $select->joinLeft(
            ['d' => $backendTable],
            $defaultJoinCond,
            [
                'title',
                'nickname',
                'customer_id',
                'detail',
            ]
        );

        return $select;
    }

    /**
     * @return Varien_Db_Adapter_Interface
     */
    public function getConnection()
    {
        return $this->connection;
    }
}
