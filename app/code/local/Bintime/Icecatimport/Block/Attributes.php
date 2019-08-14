<?php

class Bintime_Icecatimport_Block_Attributes extends Mage_Core_Block_Template
{   
    public function getProduct()
    {
        if (!$this->_product) {
            $this->_product = Mage::registry('product');
        }
        return $this->_product;
    }
    
    public function getAdditionalData(array $excludeAttr = array())
    {
       $data = $this->getAttributesArray();
      
       $data2 = array();
       foreach($data as $_data) {
	 if ($_data['label'] != '' && $_data['value'] != '' && $_data['label'] != 'id') {
           $value = $_data['value'];
	   /* $data2[] = array(
		'label'=>$_data['label'],
		'value'=>$_data['value'],
		'code'=>''
		);*/
           $group = 0;
           if ($tmp = $_data["id"]) {
             $group = $tmp;
           }
           
           $data2[$group]['items'][$_data['label']] = array(
                           'label' => $_data['label'],
                           'value' => $value,
                           'code'  => $_data['label']
           );

           $data2[$group]['attrid'] = $_data["id"];
 
         } else if (!empty($_data['code']) && $_data['code'] == 'header') {
           $data2[$_data['id']]["title"] = $_data['value'];  
         }
       }

       return $data2;
    }

	public function formatValue($value) {
		if($value == "Y"){
                  //return "Yes";
	   	  return '<img border="0" alt="" src="http://prf.icecat.biz/imgs/yes.gif"/>';
		}
		else if ($value == "N"){
                 // return "No";  
		  return '<img border="0" alt="" src="http://prf.icecat.biz/imgs/no.gif"/>';
		}
		return str_replace("\\n", "<br>",htmlspecialchars($value));
	}

	public function getAttributesArray() {
	 $iceModel = Mage::getSingleton('icecatimport/import');
	 $descriptionsListArray = $iceModel->getProductDescriptionList();
         $id = '';
         $arr = array();
	foreach($descriptionsListArray as $key=>$ma)
	{   $id = $key;
	    foreach($ma as $key=>$value)
	    {   
		$arr[$key] = $value;
                $arr[$key]["id"] = $id;
	    }
	}
         
		$data = array();
		foreach ($arr as $key => $value) {
			//$attributes = Mage::getModel('catalog/product')->getAttributesFromIcecat($this->getProduct()->getEntityId(), $value);
			// @todo @someday @maybe make headers
			$data[] = array(
			         'label' => '',
				 'value' => $key,
				 'code'  => 'header',
                                 'id'    => $value["id"]
				);
			$attributes = $value;	
			foreach ($attributes as $attributeLabel => $attributeValue) {
				$data[] = array(
					'label' => $attributeLabel,
					'value' => $this->formatValue($attributeValue),
					'code'  => 'descript',
                                        'id'    => $value["id"]
				);
			}
		}
		return $data;
	}



}
