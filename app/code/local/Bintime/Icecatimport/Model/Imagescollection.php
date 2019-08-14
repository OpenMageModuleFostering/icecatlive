<?php

class Bintime_Icecatimport_Model_Imagescollection extends Varien_Data_Collection {

	protected $_data = array();

	public function __construct() {
		parent::__construct();

		// not extends Varien_Object
      $args = func_get_args();
      if (empty($args[0])) {
 	     $args[0] = array();
      }
      $this->_data = $args[0];
		if (isset($this->_data['product'])) {	

			$helper = new Bintime_Icecatimport_Helper_Image();
			if (isset($this->_data['is_media']) && $this->_data['is_media']){
				// add product main image also
				// @todo @someday if needed add product image also
			}
		   $gallery = $helper->getGallery();
			foreach ($gallery as $item) {
				$this->addItem($this->convertIcecatImageToVarienObject($item));
			}

		}
	}

	 private function convertIcecatImageToVarienObject($image) {
		  $data = array(
		  //		'id'=>$image['product_picture_id'],
		  //		'value_id'=>$image['product_picture_id'],
		  		'file'=>$image['pic'],
				'thumb'=>$image['thumb']
		  );
		  $item = new Varien_Object();
		  $item->addData($data);
		  return $item;
		}

}

?>
