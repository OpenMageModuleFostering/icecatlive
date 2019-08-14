<?php

class Bintime_Icecatimport_Block_Attributes extends Mage_Catalog_Block_Product_View_Attributes
{
    public function getAdditionalData(array $excludeAttr = array())
    {
			$data = $this->getAttributesArray();
		$data2 = array();
		foreach($data as $_data)
		{
		if( $_data['label'] != '' && $_data['value'] != '' && $_data['code'] == 'descript'){
		$data2[] = array(
		'label'=>$_data['label'],
		'value'=>$_data['value'],
		'code'=>''
		);}
		
		if( $_data['label'] == '' && $_data['value'] != '' && $_data['code'] == 'header')
		{
		    $data2[] = array(
		'label'=>$_data['label'],
		'value'=>$_data['value'],
		'code'=>''
		);}  
//		
		}
        return $data2;
    }

	public function formatValue($value) {
		if($value == "Y"){
	   	return '<img border="0" alt="" src="http://prf.icecat.biz/imgs/yes.gif"/>';
		}
		else if ($value == "N"){
			return '<img border="0" alt="" src="http://prf.icecat.biz/imgs/no.gif"/>';
		}

		return 
			str_replace("\\n", "<br>",
				htmlspecialchars($value)
			);
	}

	public function getAttributesArray() {
	 $iceModel = Mage::getSingleton('icecatimport/import');
	 $descriptionsListArray = $iceModel->getProductDescriptionList();
	foreach($descriptionsListArray as $ma)
	{
	    foreach($ma as $key=>$value)
	    {
		$arr[$key] = $value;
	    }
	}

		$data = array();
		foreach ($arr as $key => $value) {
			//$attributes = Mage::getModel('catalog/product')->getAttributesFromIcecat($this->getProduct()->getEntityId(), $value);
			// @todo @someday @maybe make headers
				$data[] = array(
					'label'=>'',
					'value'=>'<h1>'.$key.'</h1>',
					'code'=>'header'
				);
			$attributes = $value;	
			foreach ($attributes as $attributeLabel => $attributeValue) {
				$data[] = array(
					'label'=>$attributeLabel,
					'value'=>$this->formatValue($attributeValue),
					'code'=>'descript'
				);
			}
		}
		return $data;
	}



}
