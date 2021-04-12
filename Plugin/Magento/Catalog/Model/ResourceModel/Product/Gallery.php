<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Beehexa\ImprovedImageApi\Plugin\Magento\Catalog\Model\ResourceModel\Product;

use Magento\Framework\App\ResourceConnection;

class Gallery
{
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;

    /**
     * Constructor
     *
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->connection = $resourceConnection->getConnection();
    }

    public function aroundSaveDataRow(
        \Magento\Catalog\Model\ResourceModel\Product\Gallery $subject,
        \Closure $proceed,
        $table,
        $data,
        $fields = []
    )
    {
        $result = $proceed($table, $data, $fields);
        $productEntityTbl = $this->connection->getTableName("catalog_product_entity");
        $galleryValueTbl = $this->connection->getTableName(\Magento\Catalog\Model\ResourceModel\Product\Gallery::GALLERY_VALUE_TABLE);
        if ($table == \Magento\Catalog\Model\ResourceModel\Product\Gallery::GALLERY_TABLE) {
            if (isset($data["value_id"])) {
                $getEntityIdQuery = $this->connection->select()
                    ->from($galleryValueTbl, ["entity_id"])
                    ->where("value_id = ?", $data["value_id"])->limit(1);
                $entityId = $this->connection->fetchOne($getEntityIdQuery);
                if ($entityId) {
                    $updateSql = $this->connection->update($productEntityTbl, ["updated_at" => new \Zend_Db_Expr('NOW()')], 'entity_id = ' . $entityId);
                }
            }
        } elseif ($table == \Magento\Catalog\Model\ResourceModel\Product\Gallery::GALLERY_VALUE_TABLE || $table == \Magento\Catalog\Model\ResourceModel\Product\Gallery::GALLERY_VALUE_TO_ENTITY_TABLE) {
            if ($data["entity_id"]) {
                $updateSql = $this->connection->update($productEntityTbl, ["updated_at" => new \Zend_Db_Expr('NOW()')], 'entity_id = ' . $data["entity_id"]);
            }
        }
        return $result;
    }

    public function aroundDeleteGallery(
        \Magento\Catalog\Model\ResourceModel\Product\Gallery $subject,
        \Closure $proceed,
        $valueId
    )
    {
        $galleryValueTbl = $this->connection->getTableName(\Magento\Catalog\Model\ResourceModel\Product\Gallery::GALLERY_VALUE_TABLE);
        $productEntityTbl = $this->connection->getTableName("catalog_product_entity");
        $getEntityIdQuery = $this->connection->select()
            ->from($galleryValueTbl, ["entity_id"])
            ->where("value_id = ?", $valueId)->limit(1);
        $entityId = $this->connection->fetchOne($getEntityIdQuery);
        if ($entityId) {
            $updateSql = $this->connection->update($productEntityTbl, ["updated_at" => new \Zend_Db_Expr('NOW()')], 'entity_id = ' . $entityId);
        }
        $result = $proceed($valueId);
        return $result;
    }
}

