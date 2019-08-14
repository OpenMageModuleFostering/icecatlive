<?php
require_once 'Adminhtml/ImportproductController.php';
class Iceshop_Icecatlive_IcecatliveController extends Mage_Adminhtml_Controller_Action
{
    public $_gridTables = NULL;

    public function _construct() {
      $this->_gridTables = new Iceshop_Icecatlive_Adminhtml_ImportproductController($this->getRequest(), $this->getResponse());
      parent::_construct();
    }

        /**
     * indexAction
     *
     * @return void
     *
     * TODO prevent hardcoded html structure
     */
    public function systemAction()
    {

        $checker = Mage::helper('icecatlive/system_systemcheck')->init();
        $helper = Mage::helper('icecatlive');
        ob_start();
        ?>
        <?php
        //Problems Digest
        $problems_digest = $checker->getExtensionProblemsDigest();
        $problems = $problems_digest->getProblems();
        if ($problems_digest->getCount() > 0) :
        ?>
        <div class="entry-edit" id="icecatlive-digest">
            <div class="entry-edit-head collapseable">
                <a href="#" class="section-toggler-icecatlive">
                    Problems Digest
                </a>
            </div>

          <?php if(count($problems)){ ?>
            <div class="fieldset">
          <?php } else { ?>
            <div class="fieldset icecatlive-hidden">
          <?php } ?>
                <div class="hor-scroll">
                    <table class="form-list" cellspacing="0" cellpadding="0">
                        <?php print sprintf($helper->__('To guarantee the correct functioning of the Icecat Live module you need to solve the following %s problems:'), '<strong class="requirement-failed">' . $problems_digest->getCount() . '</strong>'); ?>
                        <?php
                        $i = 1;
                        foreach ($problems as $problem_section_name => $problem_section) {
                            foreach($problem_section as $problem_name => $problem_value) {
                                if($problem_section_name=='extension'){
                                  if($problem_name == 'config_exists'){
                                    print '<tr><td class="label"><label class="problem-digest">' . $helper->__('Problem') . " " . $i . ':</label>';
                                    print '</td><td class="value">Some required files are missed:';
                                    print '<ul>';
                                    foreach($problem_value as $problem_value_key => $problem_value_path) {
                                        print '<li>' . $problem_value_path . '</li>';
                                    }
                                    print '</ul></td>';
                                    print '<td class="value">';
                                            print '<span class="f-right">'
                                            . '<a href="' . Mage::helper("adminhtml")->getUrl("adminhtml/icecatlive/checkwarning/", array('warning'=>$problem_name,'section_problems' => $problem_section_name)) . '">'
                                                    . Mage::helper( 'icecatlive' )->__( 'Acknowledge' ) . '</a></span>';
                                      print '</td>';
                                      print '</tr>';
                                  } elseif($problem_name == 'path_exists'){
                                      print '<tr><td class="label"><label class="problem-digest">' . $helper->__('Problem') . " " . $i . ':</label>';
                                      print '</td><td class="value">Some path error:<ul>';
                                      foreach($problem_value as $problem_value_key => $problem_value_path) {
                                         print '<li>' . $problem_value_path . '</li>';
                                      }
                                      print '</ul></td>';
                                      print '<td class="value">';
                                            print '<span class="f-right">'
                                            . '<a href="' . Mage::helper("adminhtml")->getUrl("adminhtml/icecatlive/checkwarning/", array('warning'=>$problem_name,'section_problems' => $problem_section_name)) . '">'
                                                    . Mage::helper( 'icecatlive' )->__( 'Acknowledge' ) . '</a></span>';
                                      print '</td>';
                                      print '</tr>';
                                  } elseif($problem_name == 'permission_error'){
                                      print '<tr><td class="label"><label class="problem-digest">' . $helper->__('Problem') . " " . $i . ':</label>';
                                      print '</td><td class="value">'.Mage::getConfig()->getNode('default/icecatlive/icecatlive_permission_error')->icecatlive_error_text.'<ul>';
                                      foreach($problem_value as $problem_value_key => $problem_value_path) {
                                         print '<li>' . $problem_value_path . '</li>';
                                      }
                                      print '</ul></td>';
                                      print '<td class="value">';
                                            print '<span class="f-right">'
                                            . '<a href="' . Mage::helper("adminhtml")->getUrl("adminhtml/icecatlive/checkwarning/", array('warning'=>$problem_name,'section_problems' => $problem_section_name)) . '">'
                                                    . Mage::helper( 'icecatlive' )->__( 'Acknowledge' ) . '</a></span>';
                                      print '</td>';
                                      print '</tr>';
                                  }
                                } else{

                                    print '<tr><td class="label"><label class="problem-digest">' . $helper->__('Problem') . " " . $i . ':</label>';
                                    print '</td><td class="value"><span class="requirement-passed">"'
                                  . $problem_value['label'] . '"</span> ' . $helper->__('current value is') . ' <span class="requirement-failed">"'
                                  . $problem_value['current_value'] . '"</span> ' . $helper->__('and recommended value is') . ' <span class="requirement-passed">"'
                                  . (!empty($problem_value['recommended_value']) ? $problem_value['recommended_value'] : '') . '"</span>. '
                                  . $helper->__(' Check this parameter in') . ' <a class="section-toggler-trigger requirement-passed" data-href="#'
                                  . $problem_section_name . '-section" href="#' . $problem_section_name . '-section">'
                                  . ucfirst($problem_section_name) . '</a> ' . $helper->__('section') . '.';
                                    print '</td>';
                                      if($problem_section_name != 'requirement' && $problem_section_name != 'rewrite'){
                                            print '<td class="value">';
                                            print '<span class="f-right">'
                                            . '<a href="' . Mage::helper("adminhtml")->getUrl("adminhtml/icecatlive/checkwarning/", array('warning'=>$problem_name,'section_problems' => $problem_section_name)) . '">'
                                                    . Mage::helper( 'icecatlive' )->__( 'Acknowledge' ) . '</a></span>';
                                            print '</td>';
                                      } elseif ($problem_section_name == 'requirement') {
                                            print '<td class="value">';
                                            print '<span class="f-right">'
                                            . '<a href="' . Mage::helper("adminhtml")->getUrl("adminhtml/icecatlive/checkwarning/", array('warning'=>$problem_value['label'],'section_problems' => $problem_section_name)) . '">'
                                                    . Mage::helper( 'icecatlive' )->__( 'Acknowledge' ) . '</a></span>';
                                            print '</td>';
                                      } elseif ($problem_section_name == 'rewrite') {
                                            print '<td class="value">';
                                            print '<span class="f-right">'
                                            . '<a href="' . Mage::helper("adminhtml")->getUrl("adminhtml/icecatlive/checkwarning/", array('warning'=>$problem_name,'section_problems' => $problem_section_name)) . '">'
                                                    . Mage::helper( 'icecatlive' )->__( 'Acknowledge' ) . '</a></span>';
                                            print '</td>';
                                      }
                                      print '</tr>';

                                }
                                $i++;
                            }
                        }
                        ?>
                        <tr>
                            <td class="label col1">
                                <label><?php print $helper->__("Report"); ?></label>
                            </td>
                            <td class="value col2" colspan="2">
                                <a href="<?php print Mage::helper("adminhtml")->getUrl("adminhtml/icecatlive/report/") ?>"
                                   target="_blank">&raquo;<?php print $helper->__('Click to generate'); ?></a>
                                <p class="note"><?php print $helper->__("Use this report for more info on found problems or send it to Iceshop B.V. to help analyzing the problem to speed up solution of any issues."); ?></p>
                            </td>
                            <td></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <?php
        endif;
        //Check module
        $DB_loger = Mage::helper('icecatlive/db');
        $import_info['startdate_imported_product'] = $DB_loger->getLogValue('icecatlive_startdate_imported_product');
        $import_info['startdate_update_product'] = $DB_loger->getLogValue('icecatlive_startdate_update_product');

        $import_info['endtdate_imported_product'] = $DB_loger->getLogValue('icecatlive_enddate_imported_product');
        $import_info['enddate_update_product'] = $DB_loger->getLogValue('icecatlive_enddate_update_product');


        $import_info['success_imported_product'] = $DB_loger->getLogValue('icecatlive_success_imported_product');
        $import_info['error_imported_product'] = $DB_loger->getLogValue('icecatlive_error_imported_product');
        $import_info['full_icecat_product'] = $DB_loger->getLogValue('icecatlive_full_icecat_product');
        $count_products = $DB_loger->readQuery("SELECT COUNT(*) FROM `" . $DB_loger->_prefix . "catalog_product_entity` LEFT JOIN `" . $DB_loger->_prefix . "iceshop_icecatlive_products_titles` ON entity_id = prod_id WHERE prod_id IS NULL");
        $import_info['count_not_updated_products'] = $count_products[0]['COUNT(*)'];
        if(file_exists(Mage::getBaseDir('var') .'/iceshop/icecatlive/cache/') && is_readable(Mage::getBaseDir('var') .'/iceshop/icecatlive/cache/')){
          $cache_files = scandir(Mage::getBaseDir('var') .'/iceshop/icecatlive/cache/');
          $count_cache_files = count($cache_files) - 2;
        } else {
          $count_cache_files = 0;
        }
        ?>
        <div class="entry-edit">

            <div class="entry-edit-head collapseable">
                <a href="#" class="section-toggler-icecatlive">
                    Icecatlive Info
                </a>
            </div>

            <div class="fieldset icecatlive-hidden">
                <div class="hor-scroll">
                    <table class="form-list" cellspacing="0" cellpadding="0">
                        <tr>
                            <td colspan="2" class="label">
                                <label class="icecatlive-label-uppercase icecatlive-label-bold">
                                    <?php print $helper->__('Icecatlive statistic'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td class="label"><label><?php print $helper->__("Sucess imported last time"); ?>:</label></td>
                            <td class="value">
                                <?php
                                    if($import_info['success_imported_product']){
                                        echo $import_info['success_imported_product'];
                                    }else{
                                        echo '0';
                                    }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="label"><label><?php print $helper->__("Not updated products"); ?>:</label></td>
                            <td class="value">
                                <?php
                                    if($import_info['count_not_updated_products']){
                                        echo $import_info['count_not_updated_products'];
                                    }else{
                                        echo '0';
                                    }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="label"><label><?php print $helper->__("Not imported last time"); ?>:</label></td>
                            <td class="value">
                                <?php
                                    if($import_info['error_imported_product']){
                                        echo $import_info['error_imported_product'];
                                    }else{
                                        echo '0';
                                    }
                                ?>
                                <label style="margin: 0 5px;"><?php print $helper->__("of them full icecat products"); ?>:</label>
                                <?php
                                if($import_info['full_icecat_product']){
                                    echo $import_info['full_icecat_product'];
                                }else{
                                    echo '0';
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="label"><label><?php print $helper->__("Files in cache"); ?>:</label></td>
                            <td class="value">
                                <?php
                                    if($count_cache_files){
                                        echo $count_cache_files;
                                    }else{
                                        echo '0';
                                    }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="label"><label><?php print $helper->__("Start import time"); ?>:</label></td>
                            <td class="value">
                                <?php
                                    if($import_info['startdate_imported_product']){
                                        echo $import_info['startdate_imported_product'];
                                    }else{
                                        echo 'Import was not running';
                                    }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="label"><label><?php print $helper->__("End import time"); ?>:</label></td>
                            <td class="value">
                                <?php
                                    if($import_info['endtdate_imported_product']){
                                        echo $import_info['endtdate_imported_product'];
                                    }else{
                                        echo 'Import was not completed';
                                    }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="label"><label><?php print $helper->__("Start update time"); ?>:</label></td>
                            <td class="value">
                                <?php
                                    if($import_info['startdate_update_product']){
                                        echo $import_info['startdate_update_product'];
                                    }else{
                                        echo 'Update was not running.';
                                    }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="label"><label><?php print $helper->__("End update time"); ?>:</label></td>
                            <td class="value">
                                <?php
                                    if($import_info['enddate_update_product']){
                                        echo $import_info['enddate_update_product'];
                                    }else{
                                        echo 'Update was not completed.';
                                    }
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <?php
        //Check module
        $check_module = $checker->getModulesCollection('Iceshop_Icecatlive');
        $check_module = $check_module->getLastItem()->getData();
        ?>
        <span id="extension-section"></span>
        <div class="entry-edit">
            <div class="entry-edit-head collapseable">
                <a href="#" class="section-toggler-icecatlive"><?php print $helper->__('Extension Diagnostic Info'); ?></a>
            </div>
            <div class="fieldset icecatlive-hidden">
                <div class="hor-scroll">
                    <table class="form-list" cellspacing="0" cellpadding="0">
                        <tr>
                            <td class="label"><label><?php print $helper->__('Name'); ?>:</label></td>
                            <td class="value"><?php echo $check_module['name']; ?></td>
                        </tr>
                        <tr>
                            <td class="label"><label><?php print $helper->__('Version'); ?>:</label></td>
                            <td class="value"><?php echo $check_module['version']; ?></td>
                        </tr>
                        <tr>
                            <td class="label"><label><?php print $helper->__('Code Pool'); ?>:</label></td>
                            <td class="value"><?php echo $check_module['code_pool']; ?></td>
                        </tr>
                        <tr>
                            <td class="label"><label><?php print $helper->__('Path'); ?>:</label></td>
                            <td class="value"><?php echo $check_module['path']; ?></td>
                        </tr>
                        <tr>
                            <td class="label"><label><?php print $helper->__('Path Exists'); ?>:</label></td>
                            <td class="value <?php echo $checker->renderRequirementValue($check_module['path_exists']); ?>">
                                <?php echo $checker->renderBooleanField($check_module['path_exists']); ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="label"><label><?php print $helper->__('Config Exists'); ?>:</label></td>
                            <td class="value <?php echo $checker->renderRequirementValue($check_module['config_exists']); ?>">
                                <?php echo $checker->renderBooleanField($check_module['config_exists']); ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="label"><label><?php print $helper->__('Dependencies'); ?>:</label></td>
                            <td class="value"><?php echo $check_module['dependencies']; ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <?php
        //Check rewrites
        $check_rewrites = $checker->getRewriteCollection('Iceshop_Icecatlive');
        ?>
        <span id="rewrite-section"></span>
        <div class="entry-edit">
            <div class="entry-edit-head collapseable">
                <a href="#" class="section-toggler-icecatlive"><?php print $helper->__('Extension Rewrites Status'); ?></a>
            </div>

            <div class="fieldset icecatlive-hidden">
                <div class="hor-scroll">
                    <table class="form-list" cellspacing="0" cellpadding="0">
                        <tbody>
                        <?php
                        foreach ($check_rewrites as $rewrite) {
                            ?>
                            <tr>
                                <td>
                                    <span class="icecatlive-label-bold icecatlive-label-rewrite"><?php print $helper->__('Path'); ?>:</span>
                                    <span><?php echo $rewrite['path']; ?></span>
                                    <br/>
                                    <span class="icecatlive-label-bold icecatlive-label-rewrite"><?php print $helper->__('Rewrite Class'); ?>:</span>
                                    <span><?php echo $rewrite['rewrite_class']; ?></span>
                                    <br/>
                                    <span class="icecatlive-label-bold icecatlive-label-rewrite"><?php print $helper->__('Active Class'); ?>:</span>
                                    <span><?php echo $rewrite['active_class']; ?></span>
                                    <br/>
                                    <span class="icecatlive-label-bold icecatlive-label-rewrite"><?php print $helper->__('Status'); ?>:</span>
                                            <span
                                                class="<?php echo $checker->renderRequirementValue($rewrite['status']); ?>">
                                                <?php echo $checker->renderStatusField($rewrite['status']); ?>
                                            </span>
                                    <br/>
                                    <br/>
                                </td>
                            </tr>
                        <?php
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <span id="requirement-section"></span>
        <div class="entry-edit">
            <div class="entry-edit-head collapseable">
                <a class="section-toggler-icecatlive" href="#"><?php print $helper->__('System Requirements'); ?></a>
            </div>

            <?php $requirements = $checker->getSystem()->getRequirements()->getData(); ?>
            <div class="fieldset icecatlive-hidden">
                <div class="hor-scroll">
                    <table class="form-list firegento-settings-table" cellspacing="0" cellpadding="0">
                        <thead>
                        <tr>
                            <th class="label col1"><?php print $helper->__('Requirement'); ?></th>
                            <th class="value col2"><?php print $helper->__('Current Value'); ?></th>
                            <th class="value col3"><?php print $helper->__('Recommended Value'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($requirements as $requirement): ?>
                            <tr>
                                <td class="label col1">
                                    <label><span class="icecatlive-pad-label"><?php echo $requirement['label']; ?></span><?php print $checker->renderAdvice($requirement); ?>:</label>
                                </td>
                                <td class="value col2 <?php echo $checker->renderRequirementValue($requirement['result']) ?>">
                                    <?php echo $requirement['current_value'] ?>
                                </td>
                                <td class="value col3"><?php echo $requirement['recommended_value'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td class="label col1"><label>phpinfo()</label>
                            </td>
                            <td class="value col2" colspan="2">
                                <a href="<?php print Mage::helper("adminhtml")->getUrl("adminhtml/icecatlive/phpinfo/") ?>"
                                   target="_blank">&raquo;<?php print $helper->__('More info'); ?></a>
                            </td>
                        </tr>
                        <?php ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="entry-edit">
            <div class="entry-edit-head collapseable">
                <a href="#" class="section-toggler-icecatlive">Magento Info</a>
            </div>

            <div class="fieldset icecatlive-hidden">
                <div class="hor-scroll">
                    <table class="form-list" cellspacing="0" cellpadding="0">
                        <tr>
                            <td class="label"><label>Edition:</label></td>
                            <td class="value"><?php echo $checker->getSystem()->getMagento()->getEdition() ?></td>
                        </tr>
                        <tr>
                            <td class="label"><label>Version:</label></td>
                            <td class="value"><?php echo $checker->getSystem()->getMagento()->getVersion() ?></td>
                        </tr>
                        <tr>
                            <td class="label"><label>Developer Mode:</label></td>
                            <td class="value"><?php echo $checker->renderBooleanField($checker->getSystem()->getMagento()->getDeveloperMode()) ?></td>
                        </tr>
                        <tr>
                            <td class="label"><label>Add Secret Key to URLs:</label></td>
                            <td class="value"><?php echo $checker->renderBooleanField($checker->getSystem()->getMagento()->getSecretKey()) ?></td>
                        </tr>
                        <tr>
                            <td class="label"><label>Use Flat Catalog Category:</label></td>
                            <td class="value"><?php echo $checker->renderBooleanField($checker->getSystem()->getMagento()->getFlatCatalogCategory()) ?></td>
                        </tr>
                        <tr>
                            <td class="label"><label>Use Flat Catalog Product:</label></td>
                            <td class="value"><?php echo $checker->renderBooleanField($checker->getSystem()->getMagento()->getFlatCatalogProduct()) ?></td>
                        </tr>
                        <tr>
                            <td class="label"><label>Cache status:</label></td>
                            <td class="value">
                                <?php echo $checker->getSystem()->getMagento()->getCacheStatus() ?><br/>
                                <a href="<?php echo Mage::helper("adminhtml")->getUrl('adminhtml/cache/') ?>">&raquo;Cache
                                    Management</a>
                            </td>
                        </tr>
                        <tr>
                            <td class="label"><label>Index status:</label></td>
                            <td class="value">
                                <?php echo $checker->getSystem()->getMagento()->getIndexStatus() ?><br/>
                                <a href="<?php echo Mage::helper("adminhtml")->getUrl('adminhtml/process/list') ?>">&raquo;Index
                                    Management</a>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="entry-edit">
            <div class="entry-edit-head collapseable">
                <a href="#" class="section-toggler-icecatlive">Magento Core API Info</a>
            </div>

            <div class="fieldset icecatlive-hidden">
                <div class="hor-scroll">
                    <table class="form-list" cellspacing="0" cellpadding="0">
                        <tr>
                            <td class="label"><label>Default Response Charset:</label></td>
                            <td class="value"><?php echo $checker->getSystem()->getMagentoApi()->getCharset() ?></td>
                        </tr>
                        <tr>
                            <td class="label"><label>Client Session Timeout (sec.):</label></td>
                            <?php $magento_api_session_timeout = $checker->getSystem()->getMagentoApi()->getSessionTimeout() ?>
                            <td class="value <?php echo $checker->renderRequirementValue($magento_api_session_timeout['result']) ?>"><?php echo $magento_api_session_timeout['current_value'] ?></td>
                        </tr>
                        <tr>
                            <td class="label"><label>WS-I Compliance:</label></td>
                            <td class="value"><?php echo $checker->renderBooleanField($checker->getSystem()->getMagentoApi()->getComplianceWsi()) ?></td>
                        </tr>
                        <tr>
                            <td class="label"><label>WSDL Cache:</label></td>
                            <td class="value"><?php echo $checker->renderBooleanField($checker->getSystem()->getMagentoApi()->getWsdlCacheEnabled()) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="entry-edit">
            <div class="entry-edit-head collapseable">
                <a href="#" class="section-toggler-icecatlive">PHP Info</a>
            </div>

            <div class="fieldset icecatlive-hidden">
                <div class="hor-scroll">
                    <table class="form-list" cellspacing="0" cellpadding="0">
                        <tr>
                            <td class="label"><label>Version:</label></td>
                            <td class="value"><?php echo $checker->getSystem()->getPhp()->getVersion() ?></td>
                        </tr>
                        <tr>
                            <td class="label"><label>Server API:</label></td>
                            <td class="value"><?php echo $checker->getSystem()->getPhp()->getServerApi() ?></td>
                        </tr>
                        <tr>
                            <td class="label"><label>Memory Limit:</label></td>
                            <td class="value"><?php echo $checker->getSystem()->getPhp()->getMemoryLimit() ?></td>
                        </tr>
                        <tr>
                            <td class="label"><label>Max. Execution Time:</label></td>
                            <td class="value"><?php echo $checker->getSystem()->getPhp()->getMaxExecutionTime() ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="entry-edit">
            <div class="entry-edit-head collapseable">
                <a href="#" class="section-toggler-icecatlive">MySQL Info</a>
            </div>

            <div class="fieldset icecatlive-hidden">
                <div class="hor-scroll">
                    <table class="form-list" cellspacing="0" cellpadding="0">
                        <tr>
                            <td class="label"><label>Version:</label></td>
                            <td class="value"><?php echo $checker->getSystem()->getMysql()->getVersion() ?></td>
                        </tr>
                        <tr>
                            <td class="label"><label>Server API:</label></td>
                            <td class="value"><?php echo $checker->getSystem()->getMysql()->getServerApi() ?></td>
                        </tr>
                        <tr>
                            <td class="label"><label>Database Name:</label></td>
                            <td class="value"><?php echo $checker->getSystem()->getMysql()->getDatabaseName() ?></td>
                        </tr>
                        <tr>
                            <td class="label"><label>Database Tables:</label></td>
                            <td class="value"><?php echo $checker->getSystem()->getMysql()->getDatabaseTables() ?></td>
                        </tr>
                        <tr>
                            <td class="label"><label>Database Table Prefix:</label></td>
                            <td class="value"><?php echo $checker->getSystem()->getMysql()->getTablePrefix() ?></td>
                        </tr>
                        <tr>
                            <td class="label"><label>Connection Timeout:</label></td>
                            <td class="value"><?php echo $checker->getSystem()->getMysql()->getConnectionTimeout() ?></td>
                        </tr>
                        <tr>
                            <td class="label"><label>Wait Timeout:</label></td>
                            <td class="value"><?php echo $checker->getSystem()->getMysql()->getWaitTimeout() ?></td>
                        </tr>
                        <tr>
                            <td class="label"><label>Thread stack:</label></td>
                            <?php $thread_stack = $checker->getSystem()->getMysql()->getThreadStack(); ?>
                            <td class="value <?php echo $checker->renderRequirementValue($thread_stack['result']) ?>"><?php echo $thread_stack['current_value'] ?></td>
                        </tr>
                        <tr>
                            <td class="label"><label>Max Allowed Packet:</label></td>
                            <?php $max_allowed_packet = $checker->getSystem()->getMysql()->getMaxAllowedPacket(); ?>
                            <td class="value <?php echo $checker->renderRequirementValue($max_allowed_packet['result']) ?>"><?php echo $max_allowed_packet['current_value'] ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="entry-edit">
            <div class="entry-edit-head collapseable">
                <a href="#" class="section-toggler-icecatlive">MySQL Configuration</a>
            </div>

            <div class="fieldset icecatlive-hidden">
                <div class="hor-scroll">
                    <table class="form-list" cellspacing="0" cellpadding="0">
                        <?php
                        $mysql_vars = $checker->getSystem()->getMysqlVars()->getData();
                        foreach ($mysql_vars as $mysql_var_key => $mysql_var_value) {
                            print '<tr>';
                            print '<td><strong>' . $mysql_var_key . ':</strong></td>';
                            print '<td class="value">' . $mysql_var_value . '</td>';
                            print '</tr>';
                        }
                        ?>
                    </table>
                </div>
            </div>
        </div>

        <div class="entry-edit">
            <div class="entry-edit-head collapseable">
                <a href="#" class="section-toggler-icecatlive">Server Info</a>
            </div>

            <div class="fieldset icecatlive-hidden">
                <div class="hor-scroll">
                    <table class="form-list" cellspacing="0" cellpadding="0">
                        <tr>
                            <td class="label"><label>Info:</label></td>
                            <td class="value"><?php echo $checker->getSystem()->getServer()->getInfo() ?></td>
                        </tr>
                        <tr>
                            <td class="label"><label>Domain:</label></td>
                            <td class="value"><?php echo $checker->getSystem()->getServer()->getDomain() ?></td>
                        </tr>
                        <tr>
                            <td class="label"><label>Server IP:</label></td>
                            <td class="value"><?php echo $checker->getSystem()->getServer()->getIp() ?></td>
                        </tr>
                        <tr>
                            <td class="label"><label>Server Directory:</label></td>
                            <td class="value"><?php echo $checker->getSystem()->getServer()->getDir() ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="entry-edit">
            <div class="entry-edit-head collapseable">
                <a href="#" class="section-toggler-icecatlive">Webshop IP address</a>
            </div>

            <div class="fieldset icecatlive-hidden">
                <div class="hor-scroll">
                    <table class="form-list" cellspacing="0" cellpadding="0">
                        <tr>
                            <td class="label"><label>Webshop IP:</label></td>
                            <td class="value"><?php echo $this->getCorectIP(); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="entry-edit">
            <div class="entry-edit-head collapseable">
                <a href="#" class="section-toggler-icecatlive">Not Import Product List</a>
            </div>
            <div class="fieldset icecatlive-hidden">
                <div id="gridActionClener" class="hor-scroll">
                    <?php echo $this->_gridTables->getGridTable(); ?>
                </div>
            </div>
        </div>
        <style>
            .requirement-passed {
                color: green;
            }

            .requirement-failed {
                color: red;
            }
        </style>
        <?php
        $system_check_content = ob_get_contents();
        ob_end_clean();

        $reset_button_html = $helper->getButtonHtml(array(
            'id' => 'icecatlive_check_system_refresh',
            'element_name' => 'icecatlive_check_system_refresh',
            'title' => Mage::helper('catalog')->__('Refresh'),
            'type' => 'reset',
            'class' => 'save',
            'label' => Mage::helper('catalog')->__('Refresh'),
            'OnClick' => 'refreshIcecatliveSystemCheck(\'' . base64_encode(Mage::helper("adminhtml")->getUrl("adminhtml/icecatlive/system/")) . '\');'
        ));

        $jsonData = json_encode(array('structure' => $system_check_content, 'refresh_btn' => $reset_button_html));
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($jsonData);
    }

    /**
     * Method return server IP address
     * @return string
     */
    function getCorectIP() {
        $host= gethostname();
        return gethostbyname($host);
    }

    public function phpinfoAction()
    {
        phpinfo(-1);
    }

    public function reportAction()
    {
        header("Content-Type: text/plain; charset=utf-8");
        $report_filename = 'icecatlive-report' . (string)Mage::getConfig()->getNode()->modules->Iceshop_Icecatlive->version . '.txt';
        header("Content-disposition: attachment; filename={$report_filename}");
        header("Content-Transfer-Encoding: binary");
        header("Pragma: no-cache");
        header("Expires: 0");

        //TODO add report content
        $checker = Mage::helper('icecatlive/system_systemcheck')->init();
        $helper = Mage::helper('icecatlive');

        //========================================
        //Problems Digest
        //========================================
        $problems_digest = $checker->getExtensionProblemsDigest();
        if ($problems_digest->getCount() != 0) {
            $problems = $problems_digest->getProblems();
            print str_pad('', 100, '=') . "\n";
            print 'Problems Digest' . "\n";
            print str_pad('', 100, '=') . "\n";
            foreach ($problems as $problem_name => $problem_value) {
                print $problem_name . "\n";
                print_r($problem_value);
            }
            print str_pad('', 100, '=') . "\n";
            print "\n";
        }
        //========================================

        //========================================
        //Check module
        //========================================
        $check_module = $checker->getModulesCollection(Mage::app()->getRequest()->getModuleName());
        $check_module = $check_module->getLastItem()->getData();
        print str_pad('', 100, '=') . "\n";
        print 'Extension Diagnostic Info' . "\n";
        print str_pad('', 100, '=') . "\n";
        print str_pad('Name', 50) . ':' . str_pad('', 5);
        print $check_module['name'] . "\n";

        print str_pad('Version', 50) . ':' . str_pad('', 5);
        print $check_module['version'] . "\n";

        print str_pad('Code Pool', 50) . ':' . str_pad('', 5);
        print $check_module['code_pool'] . "\n";

        print str_pad('Path', 50) . ':' . str_pad('', 5);
        print $check_module['path'] . "\n";

        print str_pad('Path Exists', 50) . ':' . str_pad('', 5);
        print $checker->renderBooleanField($check_module['path_exists']) . "\n";

        print str_pad('Config Exists', 50) . ':' . str_pad('', 5);
        print $checker->renderBooleanField($check_module['config_exists']) . "\n";

        print str_pad('Dependencies', 50) . ':' . str_pad('', 5);
        print $check_module['dependencies'] . "\n";
        print str_pad('', 100, '=') . "\n";
        print "\n";
        //========================================


        //========================================
        //Check rewrites
        //========================================
        $check_rewrites = $checker->getRewriteCollection(Mage::app()->getRequest()->getModuleName());
        print str_pad('', 100, '=') . "\n";
        print 'Extension Rewrites Status' . "\n";
        print str_pad('', 100, '=') . "\n";
        foreach ($check_rewrites as $rewrite) {
            print str_pad('Path', 50) . ':' . str_pad('', 5);
            print $rewrite['path'] . "\n";

            print str_pad('Rewrite Class', 50) . ':' . str_pad('', 5);
            print $rewrite['rewrite_class'] . "\n";

            print str_pad('Active Class', 50) . ':' . str_pad('', 5);
            print $rewrite['active_class'] . "\n";

            print str_pad('Status', 50) . ':' . str_pad('', 5);
            print $checker->renderStatusField($rewrite['status']) . "\n";
        }
        print str_pad('', 100, '=') . "\n";
        print "\n";
        //========================================

        //========================================
        //System Requirements
        //========================================
        $requirements = $checker->getSystem()->getRequirements()->getData();
        print str_pad('', 100, '=') . "\n";
        print 'System Requirements' . "\n";
        print str_pad('', 100, '=') . "\n";
        foreach ($requirements as $requirement) {
            print str_pad($requirement['label'], 50) . ':' . str_pad('', 5);
            print str_pad($requirement['recommended_value'], 30);
            print $requirement['current_value'] . "\n";
        }
        print str_pad('', 100, '=') . "\n";
        print "\n";
        //========================================

        //========================================
        //Magento Info
        //========================================
        print str_pad('', 100, '=') . "\n";
        print 'Magento Info' . "\n";
        print str_pad('', 100, '=') . "\n";
        print str_pad('Edition', 50) . ':' . str_pad('', 5);
        print $checker->getSystem()->getMagento()->getEdition() . "\n";

        print str_pad('Version', 50) . ':' . str_pad('', 5);
        print $checker->getSystem()->getMagento()->getVersion() . "\n";

        print str_pad('Developer Mode', 50) . ':' . str_pad('', 5);
        print $checker->renderBooleanField($checker->getSystem()->getMagento()->getDeveloperMode()) . "\n";

        print str_pad('Add Secret Key to URLs', 50) . ':' . str_pad('', 5);
        print print $checker->renderBooleanField($checker->getSystem()->getMagento()->getSecretKey()) . "\n";

        print str_pad('Use Flat Catalog Category', 50) . ':' . str_pad('', 5);
        print $checker->renderBooleanField($checker->getSystem()->getMagento()->getFlatCatalogCategory()) . "\n";

        print str_pad('Use Flat Catalog Product', 50) . ':' . str_pad('', 5);
        print print $checker->renderBooleanField($checker->getSystem()->getMagento()->getFlatCatalogProduct()) . "\n";
        print str_pad('', 100, '=') . "\n";
        print "\n";
        //========================================

        //========================================
        //Magento Core API Info
        //========================================
        print str_pad('', 100, '=') . "\n";
        print 'Magento Core API Info' . "\n";
        print str_pad('', 100, '=') . "\n";
        print str_pad('Default Response Charset', 50) . ':' . str_pad('', 5);
        print print $checker->getSystem()->getMagentoApi()->getCharset() . "\n";

        print str_pad('Client Session Timeout (sec.)', 50) . ':' . str_pad('', 5);
        $magento_api_session_timeout = $checker->getSystem()->getMagentoApi()->getSessionTimeout();
        print $magento_api_session_timeout['current_value'] . "\n";

        print str_pad('WS-I Compliance', 50) . ':' . str_pad('', 5);
        print $checker->renderBooleanField($checker->getSystem()->getMagentoApi()->getComplianceWsi()) . "\n";

        print str_pad('WSDL Cache', 50) . ':' . str_pad('', 5);
        print $checker->renderBooleanField($checker->getSystem()->getMagentoApi()->getWsdlCacheEnabled()) . "\n";
        print str_pad('', 100, '=') . "\n";
        print "\n";
        //========================================

        //========================================
        //PHP Info
        //========================================
        print str_pad('', 100, '=') . "\n";
        print 'PHP Info' . "\n";
        print str_pad('', 100, '=') . "\n";
        print str_pad('Version', 50) . ':' . str_pad('', 5);
        print $checker->getSystem()->getPhp()->getVersion() . "\n";

        print str_pad('Server API', 50) . ':' . str_pad('', 5);
        print $checker->getSystem()->getPhp()->getServerApi() . "\n";

        print str_pad('Memory Limit', 50) . ':' . str_pad('', 5);
        print $checker->getSystem()->getPhp()->getMemoryLimit() . "\n";

        print str_pad('Max. Execution Time', 50) . ':' . str_pad('', 5);
        print $checker->getSystem()->getPhp()->getMaxExecutionTime() . "\n";
        print str_pad('', 100, '=') . "\n";
        print "\n";
        //========================================

        //========================================
        //MySQL Info
        //========================================
        print str_pad('', 100, '=') . "\n";
        print 'MySQL Info' . "\n";
        print str_pad('', 100, '=') . "\n";
        print str_pad('Version', 50) . ':' . str_pad('', 5);
        print $checker->getSystem()->getMysql()->getVersion() . "\n";

        print str_pad('Server API', 50) . ':' . str_pad('', 5);
        print $checker->getSystem()->getMysql()->getServerApi() . "\n";

        print str_pad('Database Name', 50) . ':' . str_pad('', 5);
        print $checker->getSystem()->getMysql()->getDatabaseName() . "\n";

        print str_pad('Database Tables', 50) . ':' . str_pad('', 5);
        print $checker->getSystem()->getMysql()->getDatabaseTables() . "\n";

        print str_pad('Database Table Prefix', 50) . ':' . str_pad('', 5);
        print $checker->getSystem()->getMysql()->getTablePrefix() . "\n";

        print str_pad('Connection Timeout', 50) . ':' . str_pad('', 5);
        print $checker->getSystem()->getMysql()->getConnectionTimeout() . "\n";

        print str_pad('Wait Timeout', 50) . ':' . str_pad('', 5);
        print $checker->getSystem()->getMysql()->getWaitTimeout() . "\n";

        print str_pad('Thread stack', 50) . ':' . str_pad('', 5);
        $thread_stack = $checker->getSystem()->getMysql()->getThreadStack();
        print $thread_stack['current_value'] . "\n";

        print str_pad('Max Allowed Packet', 50) . ':' . str_pad('', 5);
        $max_allowed_packet = $checker->getSystem()->getMysql()->getMaxAllowedPacket();
        print $max_allowed_packet['current_value'] . "\n";
        print str_pad('', 100, '=') . "\n";
        print "\n";
        //========================================

        //========================================
        //Server Info
        //========================================
        print str_pad('', 100, '=') . "\n";
        print 'Server Info' . "\n";
        print str_pad('', 100, '=') . "\n";
        print str_pad('Info', 50) . ':' . str_pad('', 5);
        print $checker->getSystem()->getServer()->getInfo() . "\n";

        print str_pad('Domain', 50) . ':' . str_pad('', 5);
        print $checker->getSystem()->getServer()->getDomain() . "\n";

        print str_pad('Server IP', 50) . ':' . str_pad('', 5);
        print $checker->getSystem()->getServer()->getIp() . "\n";

        print str_pad('Server Directory', 50) . ':' . str_pad('', 5);
        print $checker->getSystem()->getServer()->getDir() . "\n";
        print str_pad('', 100, '=') . "\n";
        //========================================


        //========================================
        //phpinfo() full overview
        //========================================
        $formatter = Mage::helper('icecatlive/format');
        ob_start();
        phpinfo();
        $phpinfo = ob_get_contents();
        ob_end_clean();

        try {
            print str_pad('', 100, '=') . "\n";
            print 'phpinfo() full overview' . "\n";
            print str_pad('', 100, '=') . "\n";
            print $formatter->convert_html_to_text($phpinfo) . "\n";
            print str_pad('', 100, '=') . "\n";
            print "\n";
        } catch(Exception $e) {}
        //========================================


        //========================================
        //MySQL Configuration
        //========================================
        print str_pad('', 100, '=') . "\n";
        print 'MySQL Vars' . "\n";
        print str_pad('', 100, '=') . "\n";
        $mysql_vars = $checker->getSystem()->getMysqlVars()->getData();
        foreach ($mysql_vars as $mysql_var_key => $mysql_var_value) {
            print str_pad($mysql_var_key, 50) . ':' . str_pad('', 5);
            print $mysql_var_value . "\n";
        }
        print str_pad('', 100, '=') . "\n";
        //========================================
    }

    /**
     * Product grid for AJAX request
     */
    public function gridAction()
    {
        $this->_gridTables->getGridTable();
    }

    /**
     * Method for export to csv file
     */
    public function exportIcecatliveCsvAction()
    {
        $fileName = 'notimport_product.csv';
        $grid = $this->getLayout()->createBlock('icecatlive/adminhtml_product_list_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
    }

    /**
     * Method for export to excel  file
     */
    public function exportIcecatliveExcelAction()
    {
        $fileName = 'notimport_product.xml';
        $grid = $this->getLayout()->createBlock('icecatlive/adminhtml_product_list_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getExcelFile($fileName));
    }

    /**
     * Add to log skip notifications
     */
    public function checkwarningAction() {
      $DB_loger = Mage::helper('icecatlive/db');
      $skip_data = $DB_loger->getLogValue('icecatlive_skip_problems_digest');

        $warning = $this->getRequest()->getParam('warning');
        $section_problems = $this->getRequest()->getParam('section_problems');
        if(empty($skip_data)){
          $skip_data = array();
          $skip_data[$section_problems][] = $warning;
          $skip_data = json_encode($skip_data);
          $DB_loger->insetrtUpdateLogValue('icecatlive_skip_problems_digest', addslashes($skip_data));
        } else {
          $skip_data = (array)json_decode($skip_data);
          if(!empty($skip_data[$section_problems])){
            if(!in_array($warning, $skip_data[$section_problems], true)){
              $skip_data[$section_problems][] = $warning;
              $skip_data = json_encode($skip_data);
              $DB_loger->insetrtUpdateLogValue('icecatlive_skip_problems_digest', addslashes($skip_data));
            }
          } else {
              $skip_data[$section_problems][] = $warning;
              $skip_data = json_encode($skip_data);
              $DB_loger->insetrtUpdateLogValue('icecatlive_skip_problems_digest', addslashes($skip_data));
          }
        }

        $this->_redirectUrl(Mage::helper("adminhtml")->getUrl("*/system_config/edit", array('section' => 'icecatlive_information')));
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/config/icecatlive_information');
    }

    /**
     * Show explanation for setting
     * @return string;
     */
    public function explanationsAction()
    {
        $content = '<table class="explanation_table">
            <tbody>
            <tr>
                <th><strong>Setting\'s name</strong></th>
                <th><strong>Description</strong></th>
                <th>Default value</th>
                <th>Note</th>
            </tr>
            <tr>
                <td colspan="3"><strong>Icecat Live Settings</strong></td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td>Icecat User name</td>
                <td>Login used in your &nbsp;<a href="http://icecat.biz/" class="external-link"
                                                                     rel="nofollow">icecat.biz</a> account
                </td>
                <td style="text-align: center;" >-</td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td>Icecat Password</td>
                <td>Password used in your <a href="http://icecat.biz/" class="external-link"
                                                                  rel="nofollow">icecat.biz</a> account
                </td>
                <td style="text-align: center;" >-</td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td>Subscription level</td>
                <td>Subscription type of your <a href="http://icecat.biz/" class="external-link"
                                                                      rel="nofollow">icecat.biz</a> account
                </td>
                <td style="text-align: center;" >OpenIcecat XML</td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td colspan="1" >Language</td>
                <td colspan="1" ><p>Define a language of Front-end displaying data</p></td>
                <td style="text-align: center;" colspan="1" >Use Store Locale</td>
                <td colspan="1" >&nbsp;</td>
            </tr>
            <tr>
                <td colspan="1" >MPN</td>
                <td colspan="1" ><p><span style="color: rgb(47,47,47);">The attribute to use for mapping</span></p></td>
                <td style="text-align: center;" colspan="1" >mpn</td>
                <td colspan="1" >&nbsp;</td>
            </tr>
            <tr>
                <td colspan="1" >Brand</td>
                <td colspan="1" ><span style="color: rgb(47,47,47);">The attribute to use for mapping</span>
                </td>
                <td style="text-align: center;" colspan="1" ><span style="color: rgb(0,0,0);">brand</span>
                </td>
                <td colspan="1" >&nbsp;</td>
            </tr>
            <tr>
                <td colspan="1" >GTIN</td>
                <td colspan="1" ><span style="color: rgb(47,47,47);">The attribute to use for mapping</span>
                </td>
                <td style="text-align: center;" colspan="1" >gtin</td>
                <td colspan="1" >&nbsp;</td>
            </tr>
            <tr>
                <td colspan="1" >Use Product Image from</td>
                <td colspan="1" >Use images from your Magento database or load from IceCat</td>
                <td style="text-align: center;" colspan="1" >From Icecat</td>
                <td colspan="1" >&nbsp;</td>
            </tr>
            <tr>
                <td colspan="1" >Show/Hide products not presented at icecat</td>
                <td colspan="1" ><p>Define the type of displaying for products</p></td>
                <td style="text-align: center;" colspan="1" >Show all products</td>
                <td colspan="1" >&nbsp;</td>
            </tr>
            <tr>
                <td colspan="1" >Use Short description from</td>
                <td colspan="1" ><p>Define the place of getting a short description</p></td>
                <td style="text-align: center;" colspan="1" >From Icecat</td>
                <td colspan="1" >&nbsp;</td>
            </tr>
            <tr>
                <td colspan="1" >Use Description from</td>
                <td colspan="1" ><p>Define the place of getting a description</p></td>
                <td style="text-align: center;" colspan="1" >From Icecat</td>
                <td colspan="1" >&nbsp;</td>
            </tr>
            <tr>
                <td colspan="1" >Use Product Name from</td>
                <td colspan="1" ><p>Define the place of getting a product name</p></td>
                <td style="text-align: center;" colspan="1" >From Icecat</td>
                <td colspan="1" >&nbsp;</td>
            </tr>
            <tr>
                <td colspan="1" >Not view attribute in product</td>
                <td colspan="1" ><p>List of attributes where you can select ones you don\'t want to be
                    displayed</p></td>
                <td style="text-align: center;" colspan="1" >-</td>
                <td colspan="1" >&nbsp;</td>
            </tr>
            <tr>
                <td colspan="1" >Loading type:</td>
                <td colspan="1" ><p>Define the way of loading information on Front-end</p></td>
                <td style="text-align: center;" colspan="1" >From cache</td>
                <td colspan="1" >&nbsp;</td>
            </tr>
            <tr>
                <td>Import ONLY new products:</td>
                <td>Define a necessity of importing new products</td>
                <td style="text-align: center;" >No</td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td>Full import data</td>
                <td>&nbsp;This is&nbsp;the button that activate a full import</td>
                <td style="text-align: center;" >-</td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td colspan="1" >Update information on new products</td>
                <td colspan="1" >&nbsp;This is the button that activate updating process for new products
                </td>
                <td style="text-align: center;" colspan="1" >-</td>
                <td colspan="1" >&nbsp;</td>
            </tr>
            </tbody>
        </table>
        <style>
                .explanation_table, .explanation_table th, .explanation_table td {
                     border: solid 1px black;
                }
        </style>
        ';
        echo $content;
    }
}
