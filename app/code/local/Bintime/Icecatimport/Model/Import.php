<?php
/**
 * Class performs Curl request to ICEcat and fetches xml data with product description
 *  @author Sergey Gozhedrianov <info@bintime.com>
 *
 */
class Bintime_Icecatimport_Model_Import extends Mage_Core_Model_Abstract {
    
    public  $entityId;
    private $productDescriptionList  = array();
    private $productDescription;
    private $fullProductDescription;
    private $lowPicUrl;
    private $highPicUrl;
    private $errorMessage;
    private $galleryPhotos           = array();
    private $productName;
    private $relatedProducts         = array();
    private $thumb;
    private $errorSystemMessage; //depricated
    private $_cacheKey               = 'bintime_icecatimport_';

    private $_warrantyInfo           = '';
    private $_shortSummaryDesc       = '';
    private $_longSummaryDesc        = '';

    private $_manualPdfUrl           = '';
    private $_pdfUrl                 = '';
    private $_multimedia             = '';
    
    protected function _construct()
    {
        $this->_init('icecatimport/import');
    }
    
    /**
     * Perform Curl request with corresponding param check and error processing
     * @param int $productId
     * @param string $vendorName
     * @param string $locale
     * @param string $userName
     * @param string $userPass
     */
    public function getProductDescription($productId, $vendorName, $locale, $userName, $userPass, $entityId){

        $this->entityId = $entityId;
        if (null === $this->simpleDoc) {
            if (!$cacheDataXml = Mage::app()->getCache()->load($this->_cacheKey . $entityId)) {
                $dataUrl = 'http://data.icecat.biz/xml_s3/xml_server3.cgi';
                if (empty($productId)) {
                    $this->errorMessage = 'Given product has no MPN (SKU)';
                    return false;
                }
                if (empty($vendorName)){
                    $this->errorMessage = "Given product has no manufacturer specified.";
                    return false;
                }
                if (empty($locale)) {
                    $this->errorMessage = "Please specify product description locale";
                    return false;
                }
                if ( empty($userName)) {
                    $this->errorMessage = "No ICEcat login provided";
                    return false;
                }
                 if (empty($userPass)){
                    $this->errorMessage = "No ICEcat password provided";
                    return false;
                }
 
                $getParamsArray = array("prod_id" => $productId,
                                        "lang" =>$locale,
                                        "vendor" => $vendorName,
                                        "output" =>'productxml'
                );
                Varien_Profiler::start('Bintime FILE DOWNLOAD:');
                try{
                    $webClient = new Zend_Http_Client();
                    $webClient->setUri($dataUrl);
                    $webClient->setMethod(Zend_Http_Client::GET);
                    $webClient->setHeaders('Content-Type: text/xml; charset=UTF-8');
                    $webClient->setParameterGet($getParamsArray);
                    $webClient->setAuth($userName, $userPass, Zend_Http_CLient::AUTH_BASIC);
                    $response = $webClient->request();
                    if ($response->isError()){
                        $this->errorMessage = 'Response Status: '.$response->getStatus()." Response Message: ".$response->getMessage();
                        return false;
                    }
                }
                catch (Exception $e) {
                    $this->errorMessage = "Warning: cannot connect to ICEcat. {$e->getMessage()}";
                    return false;
                }
                 Varien_Profiler::stop('Bintime FILE DOWNLOAD:');
                $resultString = $response->getBody();
                Mage::app()->getCache()->save($resultString, $this->_cacheKey . $entityId);
            } else {
                $resultString = $cacheDataXml;
            }
            if(!$this->parseXml($resultString)){
                return false;
            }
            
            if ($this->checkIcecatResponse($productId, $vendorName)){
                return false;
            }
            
            $this->loadProductDescriptionList();
            $this->loadOtherProductParams($productId);
            $this->loadGalleryPhotos();
             Varien_Profiler::start('Bintime FILE RELATED');
            $this->loadRelatedProducts();
             Varien_Profiler::stop('Bintime FILE RELATED');
        }
        return true;
    }
    
    public function getSystemError(){
        return $this->errorSystemMessage;
    }
    
    public function getProductName(){
        return $this->productName;
    }
    
    public function getGalleryPhotos(){
        return $this->galleryPhotos;
    }
    
    public function getThumbPicture(){
		return $this->thumb;
	}
    
    /**
     * load Gallery array from XML
     */
    private function loadGalleryPhotos(){
        $galleryPhotos = $this->simpleDoc->Product->ProductGallery->ProductPicture;
        if (!count($galleryPhotos)){
            return false;
        }
        foreach($galleryPhotos as $photo){
        if($photo["Size"] > 0){//if
            $picHeight = (int)$photo["PicHeight"];
            $picWidth = (int)$photo["PicWidth"];
            $thumbUrl = (string)$photo["ThumbPic"];
            $picUrl = (string)$photo["Pic"];
            
            array_push($this->galleryPhotos, array(
                                        'height' => $picHeight,
                                        'width' => $picWidth,
                                        'thumb' => $thumbUrl,
                                        'pic' => $picUrl
                                        ));
        }//endif
        }
    }
    
    public function getErrorMessage(){
        return $this->errorMessage;
    }
    
    /**
     * Checks response XML for error messages
     * @param int $productId
     * @param string $vendorName
     */
    private function checkIcecatResponse(){
        $errorMessage = $this->simpleDoc->Product['ErrorMessage'];
        if ($errorMessage != ''){
            if (preg_match('/^No xml data/', $errorMessage)){
                $this->errorSystemMessage = $errorMessage;
                return true;
            }
            if (preg_match('/^The specified vendor does not exist$/', $errorMessage)) {
                                $this->errorSystemMessage = $errorMessage;
                return true;
            }
            $this->errorMessage = "Ice Cat Error: ".$errorMessage;
            return true;
        }
        return false;
    }
    
    public function getProductDescriptionList(){
        return $this->productDescriptionList;
    }
    
    
    public function getShortProductDescription(){
        return $this->productDescription;
    }
    
    public function getFullProductDescription(){
        return $this->fullProductDescription;
    }
    
    public function getLowPicUrl(){
        return $this->highPicUrl;
    }
    
    public function getRelatedProducts(){
        return $this->relatedProducts;
    }
    
    public function getVendor(){
        return $this->vendor;
    }
    
    public function getMPN(){
        return $this->productId;
    }
    
    public function getEAN(){
        return $this->EAN;
    }

    public function getWarrantyInfo(){
      return $this->_warrantyInfo;
    }

    public function getShortSummaryDescription(){
      return $this->_shortSummaryDesc; 
    }

    public function getLongSummaryDescription(){
      return $this->_longSummaryDesc;
    }

    public function getManualPDF(){
      return $this->_manualPdfUrl;
    }

    public function getPDF(){
      return $this->_pdfUrl;
    }

    public function getIceCatMedia(){
      return $this->_multimedia;
    }

    /**
     * Form related products Array
     */
    private function loadRelatedProducts(){
        $relatedProductsArray = $this->simpleDoc->Product->ProductRelated;
        if(count($relatedProductsArray)){
            foreach($relatedProductsArray as $product){
                $productArray = array();
                $productNS = $product->Product;
                $productArray['name'] = (string)$productNS['Name'];
                $productArray['thumb'] = (string)$productNS['ThumbPic'];
                $mpn = (string)$productNS['Prod_id'];
                $productSupplier = $productNS->Supplier;
                $productSupplierId = (int)$productSupplier['ID'];
                $productArray['supplier_thumb'] = 'http://images2.icecat.biz/thumbs/SUP'.$productSupplierId.'.jpg';
                $productArray['supplier_name'] = (string)$productSupplier['Name'];
                $this->relatedProducts[$mpn] = $productArray;
            }
        }
    }
    
    /**
     * Form product feature Arrray
     */
    private function loadProductDescriptionList(){
        $descriptionArray = array();
         
        $specGroups = $this->simpleDoc->Product->CategoryFeatureGroup;
        $specFeatures = $this->simpleDoc->Product->ProductFeature;
        foreach($specFeatures as $feature){
                $id = (int)$feature['CategoryFeatureGroup_ID'];
                $featureText = (string)$feature["Presentation_Value"];
                $featureName = (string)$feature->Feature->Name["Value"];
                foreach($specGroups as $group){
                    $groupId = (int)$group["ID"];
                    if ($groupId == $id){
                        $groupName = (string) $group->FeatureGroup->Name["Value"];
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
    private function loadOtherProductParams($productId){

      $productTag = $this->simpleDoc->Product;

      $this->productDescription     = (string)$productTag->ProductDescription['ShortDesc'];
      $this->fullProductDescription = (string)$productTag->ProductDescription['LongDesc'];
      $this->_warrantyInfo          = (string)$productTag->ProductDescription['WarrantyInfo'];
      $this->_shortSummaryDesc      = (string)$productTag->SummaryDescription->ShortSummaryDescription;
      $this->_longSummaryDesc       = (string)$productTag->SummaryDescription->LongSummaryDescription;

      $this->_manualPdfUrl          = (string)$productTag->ProductDescription['ManualPDFURL'];
      $this->_pdfUrl                = (string)$productTag->ProductDescription['PDFURL'];

      $this->_multimedia            = $productTag->ProductMultimediaObject->MultimediaObject;

      $this->lowPicUrl              = (string)$productTag["LowPic"];
      $this->highPicUrl             = (string)$productTag["HighPic"];
      $this->productName            = (string)$productTag["Name"];
      $this->productId              = (string)$productTag['Prod_id'];
      $this->thumb                  = (string)$productTag['ThumbPic'];
      $this->vendor                 = (string)$productTag->Supplier['Name'];

      $prodEAN = $productTag->EANCode;
      $EANstr='';
      $EANarr=null;
      $j = 0;//counter
      foreach($prodEAN as $ellEAN){
        $EANarr[]=$ellEAN['EAN'];$j++;
      }
      $i = 0;
      $str = '';
      for($i=0;$i<$j;$i++){
      $g = $i%2;
      if($g == '0'){if($j == 1){$str .= $EANarr[$i].'<br>';} else {$str .= $EANarr[$i].', ';}
      }
      else {if($i != $j-1){$str .= $EANarr[$i].', <br>';}else {$str .= $EANarr[$i].' <br>';}}
      }
      $this->EAN = $str;

    }
    
    /**
     * parse response XML: to SimpleXml
     * @param string $stringXml
     */
    private function parseXml($stringXml){
        libxml_use_internal_errors(true);
        $this->simpleDoc = simplexml_load_string($stringXml);
        if ($this->simpleDoc){
            return true;
        }
        $this->simpleDoc = simplexml_load_string(utf8_encode($stringXml));
        if ($this->simpleDoc){
            return true;
        }   
        return false;
    }
}
?>
