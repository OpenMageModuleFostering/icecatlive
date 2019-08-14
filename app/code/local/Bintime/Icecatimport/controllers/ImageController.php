<?php
/**
 * Class provides controller for import image debug
 *  @author Sergey Gozhedrianov <info@bintime.com>
 *
 */
class Bintime_Icecatimport_ImageController extends Mage_Adminhtml_Controller_Action{
	
	/**
	 * Action calls load method which uploads data to bintime connector data table
	 */
	public function viewAction(){
		$result = Mage::getModel('icecatimport/observer')->load();
	}
} 
?>