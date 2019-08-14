<?php
/**
 * Class performs Curl request to ICEcat and fetches xml data with product description
 *
 */
class Iceshop_Icecatlive_Model_Import extends Mage_Core_Model_Abstract
{

    public $entityId;
    private $productDescriptionList = array();
    private $productDescription;
    private $fullProductDescription;
    private $lowPicUrl;
    private $highPicUrl;
    private $errorMessage;
    private $galleryPhotos = array();
    private $productName;
    private $relatedProducts = array();
    private $thumb;
    private $errorSystemMessage; //depricated
    public $_cacheKey = 'iceshop_icecatlive_';
    public $_connectorCacheDir = '/iceshop/icecatlive/cache/';
    public $simpleDoc;

    private $_warrantyInfo = '';
    private $_shortSummaryDesc = '';
    private $_longSummaryDesc = '';

    private $_manualPdfUrl = '';
    private $_pdfUrl = '';
    private $_multimedia = '';

    protected function _construct()
    {
        $this->_init('icecatlive/import');
    }

    /**
     * Perform Curl request with corresponding param check and error processing
     * @param int $productId
     * @param string $vendorName
     * @param string $locale
     * @param string $userName
     * @param string $userPass
     */
    public function getProductDescription($productId, $vendorName, $locale, $userName, $userPass, $entityId, $ean_code)
    {
        $current_page = Mage::app()->getFrontController()->getRequest()->getControllerName();

        if($current_page == 'product'){

            $this->entityId = $entityId;
            $error = '';
            if (null === $this->simpleDoc) {
                $cacheDataXml = $this->_getXmlIcecatLiveCache($this->entityId, $locale);
                if(empty($cacheDataXml)){
                    $cacheDataXml = Mage::app()->getCache()->load($this->_cacheKey . $entityId . '_' . $locale);
                }
                if (!empty($cacheDataXml)) {

                    $resultString = $cacheDataXml;

                    if (!$this->parseXml($resultString)) {
                        return false;
                    }
                    if ($this->checkIcecatResponse($this->simpleDoc->Product['ErrorMessage'])) {
                        return false;
                    }
                }

                $this->loadProductDescriptionList();
                $this->loadOtherProductParams($productId);
                Varien_Profiler::start('Iceshop FILE RELATED');
                $this->loadRelatedProducts();
                Varien_Profiler::stop('Iceshop FILE RELATED');
            }
            return true;
        }
    }
    public function _getXmlIcecatLiveCache($entity_id, $locale){
        $current_prodCacheXml =  Mage::getBaseDir('var') . $this->_connectorCacheDir . 'iceshop_icecatlive_' . $entity_id . '_' . $locale;
        $current_prodCache = file_get_contents($current_prodCacheXml);
        return $current_prodCache;
    }
    public function _getIceCatData($userName, $userPass, $dataUrl, $productAttributes)
    {
        try {
            $webClient = new Zend_Http_Client();
            $webClient->setUri($dataUrl);
            $webClient->setMethod(Zend_Http_Client::GET);
            $webClient->setHeaders('Content-Type: text/xml; charset=UTF-8');
            $webClient->setParameterGet($productAttributes);
            $webClient->setAuth($userName, $userPass, Zend_Http_CLient::AUTH_BASIC);
            $response = $webClient->request();
            if ($response->isError()) {
                $this->errorMessage = 'Response Status: ' . $response->getStatus() . " Response Message: " . $response->getMessage();
                return false;
            }
        } catch (Exception $e) {
            $this->errorMessage = "Warning: cannot connect to ICEcat. {$e->getMessage()}";
            return false;
        }
        return $response->getBody();
    }

    public function getSystemError()
    {
        return $this->errorSystemMessage;
    }

    public function getProductName()
    {
        return $this->productName;
    }
    public function getThumbPicture()
    {
        return $this->thumb;
    }
    /**
     * load Gallery array from XML
     */
    public function loadGalleryPhotos($galleryPhotos)
    {
        $Imagepriority = Mage::getStoreConfig('icecat_root/icecat/image_priority');
        if($Imagepriority != 'Db'){
            if (!count($galleryPhotos)) {
                return false;
            }

            foreach ($galleryPhotos as $photo) {
                if ($photo["Size"] > 0) {
                    $picUrl = $this->changeHostsImages((string)$photo["Pic"]);
                    if (!empty($picUrl) && strpos($picUrl, 'feature_logo') <= 0) {
                        $product_id = $this->entityId;
                        $this->saveImg($product_id, $picUrl, 'file');
                    }
                }

            }
        }
    }



    public function _saveProductCatalogImage($entityId, $productTag){
        $Imagepriority = Mage::getStoreConfig('icecat_root/icecat/image_priority');
        if ($Imagepriority != 'Db') {
      if (!empty($productTag["HighPic"])) {
                $this->highPicUrl = $this->saveImg($entityId, $this->changeHostsImages((string)$productTag["HighPic"]), 'image');
            } else if (!empty($productTag["LowPic"])) {
                $this->lowPicUrl = $this->saveImg($entityId, $this->changeHostsImages((string)$productTag["LowPic"]), 'image');
            } else {
                $this->thumb = $this->saveImg($entityId, $this->changeHostsImages((string)$productTag["ThumbPic"]), 'image');
            }
        }
    }

    public function changeHostsImages($productTag){
	$image_path = str_replace("http:", "https:", $productTag);
	return $image_path;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * Checks response XML for error messages
     */
    public function checkIcecatResponse($errorMessage)
    {
        if ($errorMessage != '') {
            if (preg_match('/^No xml data/', $errorMessage)) {
                $this->errorSystemMessage = (string)$errorMessage;
                return true;
            }
            if (preg_match('/^The specified vendor does not exist$/', $errorMessage)) {
                $this->errorSystemMessage = (string)$errorMessage;
                return true;
            }
            if (preg_match('/^You are not allowed to have Full ICEcat access$/', $errorMessage)) {
                $this->errorMessage = "Warning: " . $errorMessage;
                return true;
            }
            $this->errorMessage = "Ice Cat Error: " . $errorMessage;
            return true;
        }
        return false;
    }

    public function getProductDescriptionList()
    {
        return $this->productDescriptionList;
    }

    public function getShortProductDescription()
    {
        return $this->productDescription;
    }

    public function getFullProductDescription()
    {
        return $this->fullProductDescription;
    }

    public function getLowPicUrl()
    {
        return $this->highPicUrl;
    }

    public function getRelatedProducts()
    {
        return $this->relatedProducts;
    }

    public function getVendor()
    {
        return $this->vendor;
    }

    public function getMPN()
    {
        return $this->productId;
    }

    public function getEAN()
    {
        return $this->EAN;
    }

    public function getWarrantyInfo()
    {
        return $this->_warrantyInfo;
    }

    public function getShortSummaryDescription()
    {
        return $this->_shortSummaryDesc;
    }

    public function getLongSummaryDescription()
    {
        return $this->_longSummaryDesc;
    }

    public function getManualPDF()
    {
        return $this->_manualPdfUrl;
    }

    public function getPDF()
    {
        return $this->_pdfUrl;
    }

    public function getIceCatMedia()
    {
        return $this->_multimedia;
    }

    /**
     * Form related products Array
     */
    private function loadRelatedProducts()
    {
        $relatedProductsArray = $this->simpleDoc->Product->ProductRelated;
        if (count($relatedProductsArray)) {
            foreach ($relatedProductsArray as $product) {
                $productArray = array();
                $productNS = $product->Product;
                $productArray['name'] = (string)$productNS['Name'];
                $productArray['thumb'] = (string)$productNS['ThumbPic'];
                $mpn = (string)$productNS['Prod_id'];
                $productSupplier = $productNS->Supplier;
                $productSupplierId = (int)$productSupplier['ID'];
                $productArray['supplier_thumb'] = 'http://images2.icecat.biz/thumbs/SUP' . $productSupplierId . '.jpg';
                $productArray['supplier_name'] = (string)$productSupplier['Name'];
                $this->relatedProducts[$mpn] = $productArray;
            }
        }
    }

    /**
     * Form product feature Arrray
     */
    private function loadProductDescriptionList()
    {
        $descriptionArray = array();
        $specGroups = $this->simpleDoc->Product->CategoryFeatureGroup;
        $specFeatures = $this->simpleDoc->Product->ProductFeature;
        foreach ($specFeatures as $feature) {
            $id = (int)$feature['CategoryFeatureGroup_ID'];
            $featureText = (string)$feature["Presentation_Value"];
            $featureValue = (string)$feature["Value"];
            $featureName = (string)$feature->Feature->Name["Value"];
            if ($featureValue == 'Y' || $featureValue == 'N') {
                $featureText = $featureValue;
            }
            foreach ($specGroups as $group) {
                $groupId = (int)$group["ID"];
                if ($groupId == $id) {
                    $groupName = (string)$group->FeatureGroup->Name["Value"];
                    $rating = (int)$group['No'];
                    $descriptionArray[$rating][$groupName][$featureName] = $featureText;
                    break;
                }
            }
        }
        krsort($descriptionArray);
        $this->productDescriptionList = $descriptionArray;
    }

    /**
     * Form Array of non feature-value product params
     */
    private function loadOtherProductParams($productId)
    {
        $productTag = $this->simpleDoc->Product;
        $this->productDescription = (string)$productTag->ProductDescription['ShortDesc'];
        $this->fullProductDescription = (string)$productTag->ProductDescription['LongDesc'];
        $this->_warrantyInfo = (string)$productTag->ProductDescription['WarrantyInfo'];
        $this->_shortSummaryDesc = (string)$productTag->SummaryDescription->ShortSummaryDescription;
        $this->_longSummaryDesc = (string)$productTag->SummaryDescription->LongSummaryDescription;
        $this->_manualPdfUrl = (string)$productTag->ProductDescription['ManualPDFURL'];
        $this->_pdfUrl = (string)$productTag->ProductDescription['PDFURL'];
        $this->_multimedia = $productTag->ProductMultimediaObject->MultimediaObject;

        $this->productName = (string)$productTag["Title"];
        $this->productId = (string)$productTag['Prod_id'];
        $this->vendor = (string)$productTag->Supplier['Name'];
        $prodEAN = $productTag->EANCode;
        $EANstr = '';
        $EANarr = null;
        $j = 0; //counter
        foreach ($prodEAN as $ellEAN) {
            $EANarr[] = $ellEAN['EAN'];
            $j++;
        }
        $i = 0;
        $str = '';
        for ($i = 0; $i < $j; $i++) {
            $g = $i % 2;
            if ($g == '0') {
                if ($j == 1) {
                    $str .= $EANarr[$i] . '<br>';
                } else {
                    $str .= $EANarr[$i] . ', ';
                }
            } else {
                if ($i != $j - 1) {
                    $str .= $EANarr[$i] . ', <br>';
                } else {
                    $str .= $EANarr[$i] . ' <br>';
                }
            }
        }
        $this->EAN = $str;
    }

    /**
     * parse response XML: to SimpleXml
     * @param string $stringXml
     */
    public function parseXml($stringXml)
    {
        $current_page = Mage::app()->getFrontController()->getRequest()->getControllerName();

        if($current_page == 'product'){
            libxml_use_internal_errors(true);
            $this->simpleDoc = simplexml_load_string($stringXml);
            if ($this->simpleDoc) {
                return true;
            }
            $this->simpleDoc = simplexml_load_string(utf8_encode($stringXml));
            if ($this->simpleDoc) {
                return true;
            }
        }
        return false;
    }

    /**
     * save icecat img
     * @param int $productId
     * @param string $img_url
     * @param string $img_type
     */
    public function saveImg($productId, $img_url, $imgtype = '')
    {
        $pathinfo = pathinfo($img_url);
        $img_type = $pathinfo["extension"];

        if (strpos($img_url, 'high') > 0) {
            $img_name = str_replace("http://images.icecat.biz/img/norm/high/", "", $img_url);
            $img_name = md5($img_name);
        } else if (strpos($img_url, 'low') > 0) {
            $img_name = str_replace("http://images.icecat.biz/img/norm/low/", "", $img_url);
            $img_name = md5($img_name);
        } else {
            $img_name = md5($img_url);
        }

        $img = $img_name . "." . $img_type;
        $baseDir = Mage::getSingleton('catalog/product_media_config')->getBaseMediaPath() . '/';
        $local_img = strstr($img_url, Mage::getStoreConfig('web/unsecure/base_url'));

        if (!file_exists($baseDir . $img) && !$local_img) {
            $client = new Zend_Http_Client($img_url);
            $content = $client->request();
            if ($content->isError()) {
                return $img_url;
            }
            $file = file_put_contents($baseDir . $img, $content->getBody());
            if ($file) {
                $this->addProductImageQuery($productId, $img, $imgtype);
                return $img;
            } else {
                return $img_url;
            }
        } else if ($local_img) {
            return $img_url;
        } else {

            $db = Mage::getSingleton('core/resource')->getConnection('core_write');
            $tablePrefix = (array)Mage::getConfig()->getTablePrefix();
            if (!empty($tablePrefix[0])) {
                $tablePrefix = $tablePrefix[0];
            } else {
                $tablePrefix = '';
            }
            $attr_query = "SELECT @product_entity_type_id   := `entity_type_id` FROM `" . $tablePrefix . "eav_entity_type` WHERE
                                entity_type_code = 'catalog_product';
                         SELECT @attribute_set_id         := `entity_type_id` FROM `" . $tablePrefix . "eav_entity_type` WHERE
                                entity_type_code = 'catalog_product';
                         SELECT @gallery := `attribute_id` FROM `" . $tablePrefix . "eav_attribute` WHERE
                               `attribute_code` = 'media_gallery' AND entity_type_id = @product_entity_type_id;
                         SELECT @base := `attribute_id` FROM `" . $tablePrefix . "eav_attribute` WHERE `attribute_code` = 'image' AND entity_type_id = @product_entity_type_id;
                         SELECT @small := `attribute_id` FROM `" . $tablePrefix . "eav_attribute` WHERE
                               `attribute_code` = 'small_image' AND entity_type_id = @product_entity_type_id;
                         SELECT @thumb := `attribute_id` FROM `" . $tablePrefix . "eav_attribute` WHERE
                               `attribute_code` = 'thumbnail' AND entity_type_id = @product_entity_type_id;";

            $attr_set = Mage::getModel('catalog/product')->getResource()->getEntityType()->getDefaultAttributeSetId();

            $db->query($attr_query, array(':attribute_set' => $attr_set));

            $img_check = $db->fetchAll("SELECT COUNT(*) FROM `" . $tablePrefix . "catalog_product_entity_varchar`
                                  WHERE attribute_id IN (@base ,@small,@thumb)
							      AND entity_id =:entity_id AND value =:img ", array(
                ':entity_id' => $productId,
                ':img' => $img));

            $gal_check = $db->fetchAll("SELECT COUNT(*) FROM `" . $tablePrefix . "catalog_product_entity_media_gallery`
                                  WHERE attribute_id = @gallery AND entity_id =:entity_id AND value =:img ", array(
                ':entity_id' => $productId,
                ':img' => $img));
            if ((isset($img_check[0]["COUNT(*)"]) && isset($gal_check[0]["COUNT(*)"]))
                && ($img_check[0]["COUNT(*)"] == 0 && $gal_check[0]["COUNT(*)"] == 0)
            ) {
                $this->addProductImageQuery($productId, $img, $imgtype);
            }
            return $img;
        }
    }
    public function addProductImageQuery($productId, $img, $type = '')
    {
        $db = Mage::getSingleton('core/resource')->getConnection('core_write');
        $tablePrefix = (array)Mage::getConfig()->getTablePrefix();
        if (!empty($tablePrefix[0])) {
            $tablePrefix = $tablePrefix[0];
        } else {
            $tablePrefix = '';
        }

        $attr_query = "SELECT @product_entity_type_id   := `entity_type_id` FROM `" . $tablePrefix . "eav_entity_type` WHERE
                                entity_type_code = 'catalog_product';
                         SELECT @attribute_set_id         := `entity_type_id` FROM `" . $tablePrefix . "eav_entity_type` WHERE
                                entity_type_code = 'catalog_product';
                         SELECT @gallery := `attribute_id` FROM `" . $tablePrefix . "eav_attribute` WHERE
                               `attribute_code` = 'media_gallery' AND entity_type_id = @product_entity_type_id;
                         SELECT @base := `attribute_id` FROM `" . $tablePrefix . "eav_attribute` WHERE `attribute_code` = 'image' AND entity_type_id = @product_entity_type_id;
                         SELECT @small := `attribute_id` FROM `" . $tablePrefix . "eav_attribute` WHERE
                               `attribute_code` = 'small_image' AND entity_type_id = @product_entity_type_id;
                         SELECT @thumb := `attribute_id` FROM `" . $tablePrefix . "eav_attribute` WHERE
                               `attribute_code` = 'thumbnail' AND entity_type_id = @product_entity_type_id;";

        $db->query($attr_query, array(':attribute_set' => Mage::getModel('catalog/product')->getResource()->getEntityType()->getDefaultAttributeSetId()));

        $DefaultStoreId = 0;

        if (empty($type) || $type == 'image') {

            $db->query(" INSERT INTO `" . $tablePrefix . "catalog_product_entity_varchar`
                                (entity_type_id,attribute_id,store_id,entity_id,value)
                          VALUES(@product_entity_type_id,@base,:store_id,:entity_id,:img )
                          ON DUPLICATE KEY UPDATE value = :img", array(
                ':store_id' => $DefaultStoreId,
                ':entity_id' => $productId,
                ':img' => $img));

            $db->query(" INSERT INTO `" . $tablePrefix . "catalog_product_entity_varchar`
                                (entity_type_id,attribute_id,store_id,entity_id,value)
                          VALUES(@product_entity_type_id,@small,:store_id,:entity_id,:img )
                          ON DUPLICATE KEY UPDATE value = :img", array(
                ':store_id' => $DefaultStoreId,
                ':entity_id' => $productId,
                ':img' => $img));

            $db->query(" INSERT INTO `" . $tablePrefix . "catalog_product_entity_varchar`
                                (entity_type_id,attribute_id,store_id,entity_id,value)
                          VALUES(@product_entity_type_id,@thumb,:store_id,:entity_id,:img )
                          ON DUPLICATE KEY UPDATE value = :img", array(
                ':store_id' => $DefaultStoreId,
                ':entity_id' => $productId,
                ':img' => $img));
        }


        $db->query(" INSERT INTO `" . $tablePrefix . "catalog_product_entity_media_gallery` (attribute_id,entity_id,value)
                        VALUES(@gallery,:entity_id,:img )", array(
            ':entity_id' => $productId,
            ':img' => $img));
        $db->query(" INSERT INTO `" . $tablePrefix . "catalog_product_entity_media_gallery_value`
                              (value_id,store_id,label,position,disabled)
                        VALUES(LAST_INSERT_ID(),:store_id,'',1,0 )", array(
            ':store_id' => $DefaultStoreId));


    }

}

?>