<?php

class Iceshop_Icecatlive_Block_Attributes extends Mage_Core_Block_Template
{

    public function __construct(array $args = array()) {
      parent::__construct($args);
      $import = new Iceshop_Icecatlive_Model_Observer();
      $cache_file = $this->getCacheFile();
      if(!file_exists($cache_file) && Mage::getStoreConfig('icecat_root/icecat/product_loadingtype')){
          $this->_product = $import->loadProductInfoIntoCache((int)Mage::registry('current_product')->getId(),0,1);
      }
    }


    public function getCacheFile(){
        $import = new Iceshop_Icecatlive_Model_Observer();
        $locale = Mage::getStoreConfig('icecat_root/icecat/language');
        if ($locale == '0') {
            $systemLocale = explode("_", Mage::app()->getLocale()->getLocaleCode());
            $locale = $systemLocale[0];
        }
        return Mage::getBaseDir('var') . $import->_connectorCacheDir . 'iceshop_icecatlive_'. Mage::registry('current_product')->getId() .'_' . $locale;
    }

    public function getTemplateFile()
    {
        $config_template_file_path = Mage::getConfig()->getNode('default/icecatlive/patch')->template_file_path;
        $templateName = parent::getTemplateFile();
        return (preg_match('/product/', $templateName)) ? $config_template_file_path : $templateName;
    }

    public function getProduct()
    {
        if (!$this->_product) {
            $this->_product = Mage::registry('product');
        }
        return $this->_product;
    }

    public function getAdditionalData(array $excludeAttr = array())
    {
        $data = $this->getAttributesArray($excludeAttr);

        $data2 = array();
        foreach ($data as $_data) {
            if ($_data['label'] != '' && $_data['value'] != '' && $_data['label'] != 'id') {
                $value = $_data['value'];
                $group = 0;
                if(!empty($_data["id"])){
                  $tmp = $_data["id"];
                } else {
                  $tmp = '';
                }
                if ($tmp) {
                    $group = $tmp;
                }

                $data2[$group]['items'][$_data['label']] = array(
                    'label' => $_data['label'],
                    'value' => $value,
                    'code' => $_data['label']
                );

                if(!empty($_data["id"])){
                  $data2[$group]['attrid'] = $_data["id"];
                } else {
                  $data2[$group]['attrid'] = '';
                }

            } else if (!empty($_data['code']) && $_data['code'] == 'header') {
                $data2[$_data['id']]["title"] = $_data['value'];
            }
        }

        return $data2;
    }

    public function formatValue($value)
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) {
            if ($value == "Y" || $value == "Yes" || $value == "YES") {
                return '<img border="0" alt="" src="https://prf.icecat.biz/imgs/yes.gif"/>';
            } else if ($value == "N" || $value == "NO" || $value == "No") {
                return '<img border="0" alt="" src="https://prf.icecat.biz/imgs/no.gif"/>';
            }
        }else{
            if ($value == "Y" || $value == "Yes" || $value == "YES") {
                return '<img border="0" alt="" src="http://prf.icecat.biz/imgs/yes.gif"/>';
            } else if ($value == "N" || $value == "NO" || $value == "No") {
                return '<img border="0" alt="" src="http://prf.icecat.biz/imgs/no.gif"/>';
            }
        }
        return str_replace("\\n", "<br>", htmlspecialchars($value));
    }

    public function getAttributesArray($excludeAttr = '')
    {
        $iceModel = Mage::getSingleton('icecatlive/import');
        $descriptionsListArray = $iceModel->getProductDescriptionList();
        $id = '';
        $arr = array();
        foreach ($descriptionsListArray as $key => $ma) {
            $id = $key;
            foreach ($ma as $key => $value) {
                $arr[$key] = $value;
                $arr[$key]["id"] = $id;
            }
        }

        $data = array();
        $product = $this->getProduct();
        $attributes_general = $product->getAttributes();
        foreach ($attributes_general as $attribute) {
            if ($attribute->getIsVisibleOnFront() && !in_array($attribute->getAttributeCode(), $excludeAttr)) {
                $value = $attribute->getFrontend()->getValue($product);
                if (!$product->hasData($attribute->getAttributeCode())) {
                    $value = Mage::helper('catalog')->__('N/A');
                } elseif ((string)$value == '') {
                    $value = Mage::helper('catalog')->__('No');
                } elseif ($attribute->getFrontendInput() == 'price' && is_string($value)) {
                    $value = Mage::app()->getStore()->convertPrice($value, true);
                }
                if (is_string($value) && strlen($value)) {
                  if(!$this->isAttributeView($attribute->getAttributeCode())){
                      $data[$attribute->getAttributeCode()] = array(
                          'label' => $attribute->getStoreLabel(),
                          'value' => $this->formatValue($value),
                          'code'  => $attribute->getAttributeCode()
                      );
                  }
                }
            }
        }

        foreach ($arr as $key => $value) {
            // @todo @someday @maybe make headers
            $data[] = array(
                'label' => '',
                'value' => $key,
                'code' => 'header',
                'id' => $value["id"]
            );
            $attributes = $value;
            foreach ($attributes as $attributeLabel => $attributeValue) {
                $data[] = array(
                    'label' => $attributeLabel,
                    'value' => $this->formatValue($attributeValue),
                    'code' => 'descript',
                    'id' => $value["id"]
                );
            }
        }

        return $data;
    }

    /**
     * Return isset $attribute in not view attribute list
     * @param String $attribute
     * @return boolean
     */
    public function isAttributeView($attribute){
        if(file_exists($this->getCacheFile())){
            $view_product_attributes = explode(",",Mage::getStoreConfig('icecat_root/icecat/view_attributes'));
            return in_array($attribute, $view_product_attributes);
        }
        return false;
    }

    /**
     * Return url to send icecat statistics
     * @param Objeckt $_product
     * @return string
     */
    public function getIcecatStatisticsUrl($_product){

        $config_icecat_url = ''.Mage::getConfig()->getNode('default/icecatlive/icecatlive_icecat_statistics_url')->icecat_url;
        $icecat_statistics_send_url = $config_icecat_url;
        try{
            $store_id = Mage::app()->getStore()->getStoreId();
            $mpn = Mage::getStoreConfig('icecat_root/icecat/sku_field');
            $ean_code = Mage::getStoreConfig('icecat_root/icecat/ean_code');
            $manufacturer = Mage::getStoreConfig('icecat_root/icecat/manufacturer');
            $icecat_statistics_mpn = Mage::getResourceModel('catalog/product')->getAttributeRawValue($_product->getId(), $mpn, $store_id);
            $icecat_statistics_brand_name = Mage::getResourceModel('catalog/product')->getAttributeRawValue($_product->getId(), $manufacturer, $store_id);
            $icecat_statistics_category = '';
              $cats = $_product->getCategoryIds();
              foreach ($cats as $category_id) {
                $_cat = Mage::getModel('catalog/category')->setStoreId(Mage::app()->getStore()->getId())->load($category_id);
                    $icecat_statistics_category .= $_cat->getName() . ';';
                }
            $icecat_statistics_stock = $_product->getStockItem()->getQty();
            $icecat_statistics_ean = Mage::getResourceModel('catalog/product')->getAttributeRawValue($_product->getId(), $ean_code, $store_id);
            $icecat_statistics_price = Mage::getResourceModel('catalog/product')->getAttributeRawValue($_product->getId(), 'price', $store_id);

            $icecat_statistics_send_url .= '&mpn='.  base64_encode($icecat_statistics_mpn);
            $icecat_statistics_send_url .= '&ean='.  base64_encode($icecat_statistics_ean);
            $icecat_statistics_send_url .= '&category='.base64_encode($icecat_statistics_category);
            $icecat_statistics_send_url .= '&brand_name='.base64_encode($icecat_statistics_brand_name);
            $icecat_statistics_send_url .= '&stock='.base64_encode($icecat_statistics_stock);
            $icecat_statistics_send_url .= '&price='.base64_encode($icecat_statistics_price);
            $icecat_statistics_send_url .= '&icecat_extension='.base64_encode('icecatlive');

            return $icecat_statistics_send_url;
        } catch (Exception $e){
            return $icecat_statistics_send_url;
        }
    }
}
