<?php

/**
 * Class Iceshop_Icecatlive_Helper_System_Systemcheck
 */
class Iceshop_Icecatlive_Helper_System_Systemcheck extends Mage_Core_Helper_Abstract
{
    /**
     * @var Varien_Object
     */
    protected $_system;

    protected function _getHelper()
    {
        return Mage::helper('icecatlive/system_system');
    }

    /**
     * @return Varien_Db_Adapter_Pdo_Mysql
     */
    protected function _getDb()
    {
        return Mage::getSingleton('core/resource')->getConnection('core_read');
    }

    /**
     * @return $this
     */
    public function init()
    {
        $system = new Varien_Object();

        /*
         * MAGENTO
         */
        $magento = array(
            'edition' => (method_exists('Mage', 'getEdition')) ? Mage::getEdition() : '',
            'version' => (method_exists('Mage', 'getVersion')) ? Mage::getVersion() : '',
            'developer_mode' => (method_exists('Mage', 'getIsDeveloperMode')) ? Mage::getIsDeveloperMode() : '',
            'secret_key' => (method_exists('Mage', 'getStoreConfigFlag')) ? Mage::getStoreConfigFlag('admin/security/use_form_key') : '',
            'flat_catalog_category' => (method_exists('Mage', 'getStoreConfigFlag')) ? Mage::getStoreConfigFlag('catalog/frontend/flat_catalog_category') : '',
            'flat_catalog_product' => (method_exists('Mage', 'getStoreConfigFlag')) ? Mage::getStoreConfigFlag('catalog/frontend/flat_catalog_product') : ''
        );
        try {
            $magento['cache_status'] = $this->_getHelper()->checkCaches();
            $magento['index_status'] = $this->_getHelper()->checkIndizes();
        } catch (Exception $e) {
            $magento['exception'][] = array(
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'message' => $e->getMessage()
            );
        }
        $system->setData('magento', new Varien_Object($magento));

        /*
         * SERVER
         */

        $server = array(
            'domain' => isset($_SERVER['HTTP_HOST']) ? str_replace('www.', '', $_SERVER['HTTP_HOST']) : null,
            'ip' => isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : (isset($_SERVER['LOCAL_ADDR']) ? $_SERVER['LOCAL_ADDR'] : ''),
            'dir' => (method_exists('Mage', 'getBaseDir')) ? Mage::getBaseDir() : '',
            'info' => php_uname(),

        );
        $system->setData('server', new Varien_Object($server));

        /*
         * PHP
         */

        $php = array(
            'version' => @phpversion(),
            'server_api' => @php_sapi_name(),
            'memory_limit' => @ini_get('memory_limit'),
            'max_execution_time' => @ini_get('max_execution_time')
        );
        $system->setData('php', new Varien_Object($php));

        /*
         * MySQL
         */

        // Get MySQL Server API
        $connection = $this->_getDb()->getConnection();
        if ($connection instanceof PDO) {
            $mysqlServerApi = $connection->getAttribute(PDO::ATTR_CLIENT_VERSION);
        } else {
            $mysqlServerApi = 'n/a';
        }

        // Get table prefix
        $tablePrefix = (string)Mage::getConfig()->getTablePrefix();
        if (empty($tablePrefix)) {
            $tablePrefix = $this->__('Disabled');
        }

        // Get MySQL vars
        $sqlQuery = "SHOW VARIABLES;";
        $sqlResult = $this->_getDb()->fetchAll($sqlQuery);
        $mysqlVars = array();
        foreach ($sqlResult as $mysqlVar) {
            $mysqlVars[$mysqlVar['Variable_name']] = $mysqlVar['Value'];
        }

        $mysql = array(
            'version' => $this->_getDb()->getServerVersion(),
            'server_api' => $mysqlServerApi,
            'database_name' => (string)Mage::getConfig()->getNode('global/resources/default_setup/connection/dbname'),
            'database_tables' => count($this->_getDb()->listTables()),
            'table_prefix' => $tablePrefix,
            'connection_timeout' => $mysqlVars['connect_timeout'] . ' sec.',
            'wait_timeout' => $mysqlVars['wait_timeout'] . ' sec.',
            'max_allowed_packet' => array(
                'current_value' => $mysqlVars['max_allowed_packet'] . ' bytes',
                'recommended_value' => '>= ' . (1 * 1024 * 1024),
                'result' => ($mysqlVars['max_allowed_packet'] >= (1 * 1024 * 1024)),
                'label' => 'Max Allowed Packet',
                'advice' => array(
                    'label' => $this->__('The maximum size of one packet or any generated/intermediate string.'),
                    'link' => 'https://dev.mysql.com/doc/refman/5.1/en/server-system-variables.html#sysvar_max_allowed_packet'
                )
            ),
            'thread_stack' => array(
                'current_value' => $mysqlVars['thread_stack'] . ' bytes',
                'recommended_value' => '>= ' . (192 * 1024),
                'result' => ($mysqlVars['thread_stack'] >= (192 * 1024)),
                'label' => 'Thread Stack',
                'advice' => array(
                    'label' => $this->__('The stack size for each thread.'),
                    'link' => 'https://dev.mysql.com/doc/refman/5.1/en/server-system-variables.html#sysvar_thread_stack'
                )
            )
        );
        $system->setData('mysql', new Varien_Object($mysql));
        $system->setData('mysql_vars', new Varien_Object($mysqlVars));

        /*
         * System Requirements
         */

        if (version_compare($php['version'], '5.3.0', '>=')) {
            $safeMode['current_value'] = 'Deprecated';
            $safeMode['result'] = false;
        } else {
            $safeMode['result'] = (@ini_get('safe_mode')) ? true : false;
            $safeMode['current_value'] = $this->renderBooleanField($safeMode['result']);
        }
        $memoryLimit = $php['memory_limit'];
        $memoryLimit = substr($memoryLimit, 0, strlen($memoryLimit) - 1);
        $phpCurl = @extension_loaded('curl');
        $phpDom = @extension_loaded('dom');
        $phpGd = @extension_loaded('gd');
        $phpHash = @extension_loaded('hash');
        $phpIconv = @extension_loaded('iconv');
        $phpMcrypt = @extension_loaded('mcrypt');
        $phpPcre = @extension_loaded('pcre');
        $phpPdo = @extension_loaded('pdo');
        $phpPdoMysql = @extension_loaded('pdo_mysql');
        $phpSimplexml = @extension_loaded('simplexml');

        $requirements = array(
            'php_version' => array(
                'label' => 'PHP Version',
                'recommended_value' => '>= 5.3.0',
                'current_value' => $php['version'],
                'result' => version_compare($php['version'], '5.3.0', '>='),
                'advice' => array(
                    'label' => $this->__('PHP version at least 5.3.0 required, recommended to use latest stable release.'),
                    'link' => 'http://php.net/downloads.php'
                )
            ),
            'mysql_version' => array(
                'label' => 'MySQL Version',
                'recommended_value' => '>= 4.1.20',
                'current_value' => $mysql['version'],
                'result' => version_compare($mysql['version'], '4.1.20', '>='),
                'advice' => array(
                    'label' => $this->__('MySQL version at least 4.1.20 required, recommended to use latest stable release.'),
                    'link' => 'http://dev.mysql.com/downloads/mysql/'
                )
            ),
            'safe_mode' => array(
                'label' => 'Safe Mode',
                'recommended_value' => $this->renderBooleanField(false),
                'current_value' => $safeMode['current_value'],
                'result' => !$safeMode['result'],
                'advice' => array(
                    'label' => $this->__('The PHP safe mode is an attempt to solve the shared-server security problem. Deprecated since PHP 5.3.0'),
                    'link' => 'http://www.php.net/manual/en/features.safe-mode.php'
                )
            ),
            'memory_limit' => array(
                'label' => 'Memory Limit',
                'recommended_value' => '>= 128M',
                'current_value' => $php['memory_limit'],
                'result' => ($this->checkMemoryLimit($php['memory_limit'], (int)128)),
                'advice' => array(
                    'label' => $this->__('Maximum amount of memory in bytes that a script is allowed to allocate.'),
                    'link' => 'http://ua2.php.net/manual/en/ini.core.php#ini.memory-limit'
                )
            ),
            'max_execution_time' => array(
                'label' => 'Max. Execution Time',
                'recommended_value' => '>= 30 sec.',
                'current_value' => $php['max_execution_time'],
                'result' => ($php['max_execution_time'] >= 30),
                'advice' => array(
                    'label' => $this->__('This sets the maximum time in seconds a script is allowed to run before it is terminated by the parser.'),
                    'link' => 'http://ua2.php.net/manual/en/info.configuration.php#ini.max-execution-time'
                )
            ),
            'curl' => array(
                'label' => 'curl',
                'recommended_value' => $this->renderBooleanField(true),
                'current_value' => $this->renderBooleanField($phpCurl),
                'result' => $phpCurl,
                'advice' => array(
                    'label' => $this->__('CURL is a library that allows to connect and communicate via a variety of different protocols such as HTTP, HTTPS, FTP, Telnet etc.'),
                    'link' => 'http://www.tomjepson.co.uk/enabling-curl-in-php-php-ini-wamp-xamp-ubuntu/'
                )
            ),
            'dom' => array(
                'label' => 'dom',
                'recommended_value' => $this->renderBooleanField(true),
                'current_value' => $this->renderBooleanField($phpDom),
                'result' => $phpDom,
                'advice' => array(
                    'label' => $this->__('The DOM extension allows to operate on XML documents through the DOM API with PHP 5.'),
                    'link' => 'http://www.php.net/manual/en/dom.setup.php'
                )
            ),
            'gd' => array(
                'label' => 'gd',
                'recommended_value' => $this->renderBooleanField(true),
                'current_value' => $this->renderBooleanField($phpGd),
                'result' => $phpGd,
                'advice' => array(
                    'label' => $this->__('GD is a library which supports a variety of formats, below is a list of formats supported by GD and notes to their availability including read/write support.'),
                    'link' => 'http://www.php.net/manual/en/image.installation.php'
                )
            ),
            'hash' => array(
                'label' => 'hash',
                'recommended_value' => $this->renderBooleanField(true),
                'current_value' => $this->renderBooleanField($phpHash),
                'result' => $phpHash,
                'advice' => array(
                    'label' => $this->__('Hash is a library which allows direct or incremental processing of arbitrary length messages using a variety of hashing algorithms.'),
                    'link' => 'http://www.php.net/manual/en/hash.setup.php'
                )
            ),
            'iconv' => array(
                'label' => 'iconv',
                'recommended_value' => $this->renderBooleanField(true),
                'current_value' => $this->renderBooleanField($phpIconv),
                'result' => $phpIconv,
                'advice' => array(
                    'label' => $this->__('Iconv is a module which contains an interface to iconv character set conversion facility.'),
                    'link' => 'http://ua1.php.net/manual/en/iconv.installation.php'
                )
            ),
            'mcrypt' => array(
                'label' => 'mcrypt',
                'recommended_value' => $this->renderBooleanField(true),
                'current_value' => $this->renderBooleanField($phpMcrypt),
                'result' => $phpMcrypt,
                'advice' => array(
                    'label' => $this->__('Mcrypt library supports a wide variety of block algorithms such as DES, TripleDES, Blowfish (default), 3-WAY, SAFER-SK64, SAFER-SK128, TWOFISH, TEA, RC2 and GOST in CBC, OFB, CFB and ECB cipher modes.'),
                    'link' => 'http://www.php.net/manual/en/mcrypt.installation.php'
                )
            ),
            'pcre' => array(
                'label' => 'pcre',
                'recommended_value' => $this->renderBooleanField(true),
                'current_value' => $this->renderBooleanField($phpPcre),
                'result' => $phpPcre,
                'advice' => array(
                    'label' => $this->__('The PCRE library is a set of functions that implement regular expression pattern matching using the same syntax and semantics as Perl 5, with just a few differences.'),
                    'link' => 'http://www.php.net/manual/en/pcre.installation.php'
                )
            ),
            'pdo' => array(
                'label' => 'pdo',
                'recommended_value' => $this->renderBooleanField(true),
                'current_value' => $this->renderBooleanField($phpPdo),
                'result' => $phpPdo,
                'advice' => array(
                    'label' => $this->__('The PHP Data Objects (PDO) extension defines a lightweight, consistent interface for accessing databases in PHP.'),
                    'link' => 'http://www.php.net/manual/en/pdo.installation.php'
                )
            ),
            'pdo_mysql' => array(
                'label' => 'pdo_mysql',
                'recommended_value' => $this->renderBooleanField(true),
                'current_value' => $this->renderBooleanField($phpPdoMysql),
                'result' => $phpPdoMysql,
                'advice' => array(
                    'label' => $this->__('PDO_MYSQL is a driver that implements the PHP Data Objects (PDO) interface to enable access from PHP to MySQL 3.x, 4.x and 5.x databases.'),
                    'link' => 'http://ua1.php.net/pdo_mysql#ref.pdo-mysql.installation'
                )
            ),
            'simplexml' => array(
                'label' => 'simplexml',
                'recommended_value' => $this->renderBooleanField(true),
                'current_value' => $this->renderBooleanField($phpSimplexml),
                'result' => $phpSimplexml,
                'advice' => array(
                    'label' => $this->__('The SimpleXML extension provides a very simple and easily usable toolset to convert XML to an object that can be processed with normal property selectors and array iterators.'),
                    'link' => 'http://www.php.net/manual/en/simplexml.installation.php'
                )
            )
        );
        $system->setData('requirements', new Varien_Object($requirements));

        $magento_api = array();
        $magento_api['charset'] = Mage::getStoreConfig("api/config/charset");
        $magento_api_session_timeout = Mage::getStoreConfig('api/config/session_timeout');
        $magento_api['session_timeout'] = array(
            'result' => ($magento_api_session_timeout >= 3600),
            'current_value' => $magento_api_session_timeout,
            'label' => 'Client Session Timeout (sec.)',
            'recommended_value' => '>= 3600',
            'advice' => array(
                'label' => $this->__('This sets the maximum time in seconds a script is allowed to use Magento Core API using one session ID.'),
                'link' => 'http://www.magentocommerce.com/boards/viewthread/19445/'
            )
        );
        $magento_api['compliance_wsi'] = Mage::getStoreConfig(Mage_Api_Helper_Data::XML_PATH_API_WSI);;
        $magento_api['wsdl_cache_enabled'] = Mage::getStoreConfig('api/config/wsdl_cache_enabled');
        $system->setData('magento_api', new Varien_Object($magento_api));

        $this->_system = $system;

        return $this;
    }

    /**
     * @return Varien_Object
     */
    public function getSystem()
    {
        return $this->_system;
    }

    /**
     * @param boolean $value
     * @return string
     */
    public function renderBooleanField($value)
    {
        if ($value) {
            return $this->__('Enabled');
        }
        return $this->__('Disabled');
    }

    /**
     * @param $result
     * @return string
     */
    public function renderRequirementValue($result)
    {
        if ($result) {
            return 'requirement-passed';
        }
        return 'requirement-failed';
    }

    /**
     * @param $result
     * @return string
     */
    public function renderStatusField($value)
    {
        if ($value) {
            return $this->__('Ok');
        }
        return $this->__('Error');
    }
    public function renderAdvice($value)
    {
        if (!empty($value) && !empty($value['advice'])) {
            return '<a ' . (!empty($value['advice']['link']) ? 'href="' . $value['advice']['link'] . '" target="_blank"' : 'href="#"') . '" class="iceimport-advice" title="' .  $value['advice']['label'] . '"></a>';
        }
        return '';
    }

    /**
     * Retrieve a collection of all modules
     *
     * @return Varien_Data_Collection Collection
     */
    public function getModulesCollection($exact = false)
    {
        $sortValue = Mage::app()->getRequest()->getParam('sort', 'name');
        $sortValue = strtolower($sortValue);

        $sortDir = Mage::app()->getRequest()->getParam('dir', 'ASC');
        $sortDir = strtoupper($sortDir);

        $modules = $this->_loadModules($exact);
        $modules = Mage::helper('icecatlive')->sortMultiDimArr($modules, $sortValue, $sortDir);


        $collection = new Varien_Data_Collection();
        foreach ($modules as $key => $val) {
            $item = new Varien_Object($val);
            $collection->addItem($item);
        }

        return $collection;
    }

    /**
     * Loads the module configurations and checks for some criteria and
     * returns an array with the current modules in the Magento instance.
     *
     * @return array Modules
     */
    protected function _loadModules($exact = false)
    {
        $modules = array();
        $config  = Mage::getConfig();
        foreach ($config->getNode('modules')->children() as $moduleName => $item) {
            if (!empty($exact) && strlen($exact) != 0) {
                if (strpos(strtolower($moduleName), strtolower($exact)) === false) {
                    continue;
                }
            }


            $active       = ($item->active == 'true') ? true : false;
            $codePool     = (string) $config->getModuleConfig($item->getName())->codePool;
            $path         = $config->getOptions()->getCodeDir() . DS . $codePool . DS . uc_words($item->getName(), DS);
            $pathExists   = file_exists($path);
            $pathExists   = $pathExists ? true : false;
            $configExists = file_exists($path . '/etc/config.xml');
            $configExists = $configExists ? true : false;
            $version      = (string) $config->getModuleConfig($item->getName())->version;
            $dependencies = '-';
            if ($item->depends) {
                $depends = array();
                foreach ($item->depends->children() as $depend) {
                    $depends[] = $depend->getName();
                }
                if (is_array($depends) && count($depends) > 0) {
                    asort($depends);
                    $dependencies = implode("\n", $depends);
                }
            }

            $modules[$item->getName()] = array(
                'name'          => $item->getName(),
                'active'        => $active,
                'code_pool'     => $codePool,
                'path'          => $path,
                'path_exists'   => $pathExists,
                'config_exists' => $configExists,
                'version'       => $version,
                'dependencies'  => $dependencies
            );
        }
        return $modules;
    }


    /**
     * Retrieve a collection of all rewrites
     *
     * @return Varien_Data_Collection Collection
     */
    public function getRewriteCollection($exact = false)
    {
        $collection = new Varien_Data_Collection();
        $rewrites   = $this->_loadRewrites();

        foreach ($rewrites as $rewriteNodes) {
            foreach ($rewriteNodes as $n) {
                $nParent    = $n->xpath('..');
                $module     = (string) $nParent[0]->getName();
                $nSubParent = $nParent[0]->xpath('..');
                $component  = (string) $nSubParent[0]->getName();

                if (!in_array($component, array('blocks', 'helpers', 'models'))) {
                    continue;
                }

                $pathNodes = $n->children();
                foreach ($pathNodes as $pathNode) {
                    $path = (string) $pathNode->getName();
                    $completePath = $module.'/'.$path;
                    $rewriteClassName = (string) $pathNode;

                    if (!empty($exact) && strlen($exact) != 0) {
                        if ((strpos(strtolower($completePath), strtolower($exact)) === false)
                            && (strpos(strtolower($rewriteClassName), strtolower($exact)) === false)) {
                            continue;
                        }
                    }

                    $instance = Mage::getConfig()->getGroupedClassName(
                        substr($component, 0, -1),
                        $completePath
                    );

                    $collection->addItem(
                        new Varien_Object(
                            array(
                                'path'          => $completePath,
                                'rewrite_class' => $rewriteClassName,
                                'active_class'  => $instance,
                                'status'        => ($instance == $rewriteClassName)
                            )
                        )
                    );
                }
            }
        }

        return $collection;
    }

    /**
     * Return all rewrites
     *
     * @return array All rwrites
     */
    protected function _loadRewrites()
    {
        $fileName = 'config.xml';
        $modules  = Mage::getConfig()->getNode('modules')->children();

        $return = array();
        foreach ($modules as $modName => $module) {
            if ($module->is('active')) {
                $configFile = Mage::getConfig()->getModuleDir('etc', $modName) . DS . $fileName;
                if (file_exists($configFile)) {
                    $xml = file_get_contents($configFile);
                    $xml = simplexml_load_string($xml);

                    if ($xml instanceof SimpleXMLElement) {
                        $return[$modName] = $xml->xpath('//rewrite');
                    }
                }
            }
        }

        return $return;
    }

    /**
     * @return Varien_Object
     */
    public function getExtensionProblemsDigest()
    {
      $DB_loger = Mage::helper('icecatlive/db');
      $skip_data = $DB_loger->getLogValue('icecatlive_skip_problems_digest');

      $skip_data_empty = FALSE;
      if(!empty($skip_data)){
          $skip_data_empty = true;
          $skip_data = (array)json_decode($skip_data);
      }
        $problems = array();
        $count = 0;
        //extension problems
        $check_module = $this->getModulesCollection('Iceshop_Icecatlive');
        $check_module = $check_module->getLastItem()->getData();
          $permission_error_flag = true;
          if($skip_data_empty && !empty($skip_data['extension'])){
            foreach ($skip_data['extension'] as $key=>$value){
              if($value=='permission_error'){
                $permission_error_flag =false;
              }
            }
          }
          if($permission_error_flag){
            if(!is_writable(Mage::getBaseDir('var'))){
                $problems['extension']['permission_error'][] =  ''. Mage::getBaseDir('var') . ''.Mage::getConfig()->getNode('default/icecatlive/icecatlive_cache_path')->cache_path;
                $count++;
            }
            if(!is_writable(Mage::getSingleton('catalog/product_media_config')->getBaseMediaPath() . '/')){
                $problems['extension']['permission_error'][] = Mage::getSingleton('catalog/product_media_config')->getBaseMediaPath() . '/';
                if(is_writable(Mage::getBaseDir('var'))){
                  $count++;
                }
            }
          }

        if (!$check_module['path_exists']) {
          if(!empty($check_module['path'])){
              $path_exist_flag = true;
              if($skip_data_empty && !empty($skip_data['extension'])){
                foreach ($skip_data['extension'] as $key=>$value){
                  if($value=='path_exists'){
                    $path_exist_flag =false;
                  }
                }
              }
              if($path_exist_flag){
                $check_module['path'] = trim($check_module['path']);
                if(!empty($check_module['path'])){
                  $problems['extension']['path_exists'][] = $check_module['path'];
                  $count++;
                }
              }
          }
        }
        if (!$check_module['config_exists']) {
            if(!empty($check_module['path'])){
                $config_exists_flag = true;
                if($skip_data_empty && !empty($skip_data['extension'])){
                  foreach ($skip_data['extension'] as $key=>$value){
                    if($value=='config_exists'){
                      $config_exists_flag =false;
                    }
                  }
                }
                if($config_exists_flag){
                    $check_module['path'] = trim($check_module['path']);
                  if(!empty($check_module['path'])){
                    $problems['extension']['config_exists'][] = $check_module['path'] . '/etc/config.xml';
                    $count++;
                  }
                }
            }
        }
        if(empty($problems['extension'])){
          unset($problems['extension']);
        }

        //extension rewrites problems
        $check_rewrites = $this->getRewriteCollection('Iceshop_Icecatlive');

        foreach ($check_rewrites as $rewrite) {
            if (!$rewrite['status']) {
                $problems['rewrite'][] = $rewrite;
                $count++;
            }
        }

        if(!empty($problems['rewrite'])){
          $problems_rewrite_count = count($problems['rewrite']);
          for($i = 0; $i<$problems_rewrite_count; $i++){
            if (!$problems['rewrite'][$i]['status']) {
                unset($problems['rewrite'][$i]);
                  $count--;
            }
          }
          if($skip_data_empty && !empty($skip_data['rewrite'])){
            foreach ($skip_data['rewrite'] as $key=>$value){
                if(!empty($problems['rewrite'][$value])){
                  unset($problems['rewrite'][$value]);
                  $count--;
                }
            }
          }
          if(empty($problems['rewrite'])){
            unset($problems['rewrite']);
          }
        }

        //system requirements (for magento/extension)
        $requirements = $this->getSystem()->getRequirements()->getData();
        foreach ($requirements as $requirement) {
            if (!$requirement['result']) {
                $requirement_flag = true;
                if($skip_data_empty && !empty($skip_data['requirement'])){
                  foreach ($skip_data['requirement'] as $key=>$value){
                    if($value==$requirement['label']){
                      $requirement_flag =false;
                    }
                  }
                }
                if($requirement_flag){
                  $problems['requirement'][] = $requirement;
                  $count++;
                }
            }
        }
        if(empty($problems['requirement'])){
          unset($problems['requirement']);
        }

        //magento API problems
        $magento_api_session_timeout = $this->getSystem()->getMagentoApi()->getSessionTimeout();
        if (!$magento_api_session_timeout['result']) {
            $api_skip_flag = true;
            if($skip_data_empty && !empty($skip_data['api'])){
              foreach ($skip_data['api'] as $key=>$value){
                if($value=='timeout'){
                    $api_skip_flag =false;
                }
              }
            }
            if($api_skip_flag){
              $problems['api']['timeout'] = $magento_api_session_timeout;
              $count++;
            }
        }
        if(empty($problems['api'])){
          unset($problems['api']);
        }
        $mysql = $this->getSystem()->getMysql()->getData();
        foreach ($mysql as $mysql_param_name => $mysql_param_value) {
            if (is_array($mysql_param_value) && !$mysql_param_value['result']) {
                $mysql_skip_flag = true;
                if($skip_data_empty && !empty($skip_data['mysql'])){
                  foreach ($skip_data['mysql'] as $key=>$value){
                    if($value==$mysql_param_name){
                        $mysql_skip_flag =false;
                    }
                  }
                }
                if($mysql_skip_flag){
                  $problems['mysql'][$mysql_param_name] = $mysql_param_value;
                  $count++;
                }
            }
        }
        if(empty($problems['mysql'])){
          unset($problems['mysql']);
        }

        $problems_digest = new Varien_Object();
        $problems_digest->setData('problems', $problems);
        $problems_digest->setData('count', $count);
        return $problems_digest;
    }


    /**
     * Check skip wrning iceimport
     * @return boolean
     */
    public function checkSetWarning(){
      $DB_loger = Mage::helper('icecatlive/db');
      $skip_data = $DB_loger->getLogValue('icecatlive_skip_problems_digest');
      if(empty($skip_data)){
        return true;
      } else {
        return false;
      }
    }

    /**
     * Calculate and compare needed value of memory limit
     * @param string $memoryLimit
     * @param integer $compare
     * @return boolean
     */
    protected function checkMemoryLimit($memoryLimit, $compare){
        $ml = false;
        if(strripos($memoryLimit, 'G')){
            $ml = ((int)$memoryLimit)*1024;
        } else {
            $ml = (int)$memoryLimit;
        }
        if($ml){
            if($ml>=$compare)
                return true;
            else
                return false;
        }
        return false;
    }
}
