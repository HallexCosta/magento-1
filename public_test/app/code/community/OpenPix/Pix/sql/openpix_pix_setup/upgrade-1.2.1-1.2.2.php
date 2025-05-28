<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$orderTable = $this->getTable("sales/order");
$invoiceTable = $this->getTable("sales/invoice");
$creditmemoTable = $this->getTable("sales/creditmemo");
$quoteAddressTable = $this->getTable("sales/quote_address");

$installer
    ->getConnection()
    ->addColumn($orderTable, "giftback_discount", "DECIMAL( 10, 2 ) NULL");
$installer
    ->getConnection()
    ->addColumn($orderTable, "base_giftback_discount", "DECIMAL( 10, 2 ) NULL");

$installer
    ->getConnection()
    ->addColumn($invoiceTable, "giftback_discount", "DECIMAL( 10, 2 ) NULL");
$installer
    ->getConnection()
    ->addColumn(
        $invoiceTable,
        "base_giftback_discount",
        "DECIMAL( 10, 2 ) NULL"
    );

$installer
    ->getConnection()
    ->addColumn($creditmemoTable, "giftback_discount", "DECIMAL( 10, 2 ) NULL");
$installer
    ->getConnection()
    ->addColumn(
        $creditmemoTable,
        "base_giftback_discount",
        "DECIMAL( 10, 2 ) NULL"
    );

$installer
    ->getConnection()
    ->addColumn(
        $quoteAddressTable,
        "giftback_discount",
        "DECIMAL( 10, 2 ) NULL"
    );
$installer
    ->getConnection()
    ->addColumn(
        $quoteAddressTable,
        "base_giftback_discount",
        "DECIMAL( 10, 2 ) NULL"
    );

$installer->endSetup();
