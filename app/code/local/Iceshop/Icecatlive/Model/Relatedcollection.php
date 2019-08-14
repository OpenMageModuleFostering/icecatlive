<?php
class Iceshop_Icecatlive_Model_Relatedcollection extends Varien_Data_Collection
{

    protected $_data = array();
    protected $_collection;

    public function __construct()
    {
        parent::__construct();

        // not extends Varien_Object
        $args = func_get_args();
        if (empty($args[0])) {
            $args[0] = array();
        }
        $this->_data = $args[0];
    }

    public function getCollection()
    {
        $sku = Mage::getStoreConfig('icecat_root/icecat/sku_field');
        $model = Mage::getModel('catalog/product');
        $collection = $model->getCollection();

        $filterArray = array();
        $rel = array();
        foreach ($this->_data as $res) {
            foreach ($res as $r) {
                $rel[] = $r;
            }
        }
        foreach ($rel as $item) {
            array_push($filterArray, array('attribute' => $sku, 'eq' => $item['mpn']));
        }
        $collection->addFieldToFilter($filterArray);

        $collection->joinField('is_in_stock',
            'cataloginventory/stock_item',
            'is_in_stock',
            'product_id=entity_id',
            'is_in_stock=1',
            '{{table}}.stock_id=1',
            'left');

        $myCollection = clone $collection;
        $relCnt = count($rel);
        foreach ($myCollection as &$col) {
            $model->load($col->getId());
            $price = $model->getPrice();
            $mpn = $col->getData($sku);
            $specialPrice = $model->getSpecialPrice();

            for ($i = 0; $i < $relCnt; $i++) {
                if ($rel[$i]['mpn'] == $mpn) {
                    $col->setData('name', $rel[$i]['name']);
                    $col->setData('thumbnail', $rel[$i]['thumb']);
                    $col->setData('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
                    $col->setData('price', $price);
                    $col->setData('special_price', $specialPrice);
                }
            }
        }
        return $myCollection;

    }
}

?>
