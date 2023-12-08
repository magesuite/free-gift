<?php

namespace MageSuite\FreeGift\Setup;

use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\UninstallInterface;

class Uninstall implements UninstallInterface
{
    /**
     * {@inheritdoc}
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if ($setup->getConnection()->tableColumnExists(
            $setup->getTable('salesrule'),
            \MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::RULE_DATA_KEY_SKU
        )) {
            $setup->getConnection()->dropColumn(
                $setup->getTable('salesrule'),
                \MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::RULE_DATA_KEY_SKU
            );
        }

        if ($setup->getConnection()->tableColumnExists(
            $setup->getTable('salesrule'),
            \MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::RULE_DATA_KEY_SKU_DISCOUNTS
        )) {
            $setup->getConnection()->dropColumn(
                $setup->getTable('salesrule'),
                \MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::RULE_DATA_KEY_SKU_DISCOUNTS
            );
        }

        if ($setup->getConnection()->tableColumnExists(
            $setup->getTable('salesrule'),
            \MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::RULE_DATA_KEY_SKU_QTY
        )) {
            $setup->getConnection()->dropColumn(
                $setup->getTable('salesrule'),
                \MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::RULE_DATA_KEY_SKU_QTY
            );
        }

        if ($setup->getConnection()->tableColumnExists(
            $setup->getTable('quote_item'),
            'is_gift'
        )) {
            $setup->getConnection()->dropColumn(
                $setup->getTable('quote_item'),
                'is_gift'
            );
        }

        $setup->endSetup();
    }
}
