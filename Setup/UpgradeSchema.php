<?php

namespace MageSuite\FreeGift\Setup;

class UpgradeSchema implements \Magento\Framework\Setup\UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(\Magento\Framework\Setup\SchemaSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.1.0') < 0) {
            $connection = $setup->getConnection();

            if (!$connection->tableColumnExists($setup->getTable('quote_item'), 'is_gift')) {
                $connection->addColumn(
                    $setup->getTable('quote_item'),
                    'is_gift',
                    ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                        'nullable' => false,
                        'default' => '0',
                        'comment' => 'Is item a gift'
                    ]
                );
            }
        }

        $setup->endSetup();
    }
}
