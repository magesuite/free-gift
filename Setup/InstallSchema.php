<?php

namespace MageSuite\FreeGift\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (
            !$setup->getConnection()->tableColumnExists(
                $setup->getTable('salesrule'),
                \MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::RULE_DATA_KEY_SKU
            )
        )
        {
            $setup->getConnection()->addColumn(
                $setup->getTable('salesrule'),
                \MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::RULE_DATA_KEY_SKU, [
                    'type' => Table::TYPE_TEXT,
                    'comment' => 'Skus of gift products'
                ]
            );
        }

        if (
            !$setup->getConnection()->tableColumnExists(
                $setup->getTable('salesrule'),
                \MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::RULE_DATA_KEY_SKU_DISCOUNTS
            )
        )
        {
            $setup->getConnection()->addColumn(
                $setup->getTable('salesrule'),
                \MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::RULE_DATA_KEY_SKU_DISCOUNTS, [
                    'type' => Table::TYPE_TEXT,
                    'comment' => 'Discounts for gift products skus'
                ]
            );
        }

        if (
            !$setup->getConnection()->tableColumnExists(
                $setup->getTable('salesrule'),
                \MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::RULE_DATA_KEY_SKU_QTY
            )
        )
        {
            $setup->getConnection()->addColumn(
                $setup->getTable('salesrule'),
                \MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::RULE_DATA_KEY_SKU_QTY, [
                    'type' => Table::TYPE_TEXT,
                    'comment' => 'Qtys for gift products skus'
                ]
            );
        }

        $setup->endSetup();
    }
}
