<?php
class Uninstall_Bintime_Icecatlive
{
    /**
     * Delete Icecatlive extension with old namespace
     */
    public function uninstall()
    {
//        $code_dir = Mage::getBaseDir('app') . '/code/local/Bintime/Icecatimport';
        $design_layout = Mage::getBaseDir('app') . '/design/frontend/base/default/layout/IcecatGroupAttributes.xml';
        $template_dir = Mage::getBaseDir('app') . '/design/frontend/base/default/template/icecatlive';
//        $etc_bintime_xml = Mage::getBaseDir('app') . '/etc/modules/Bintime_Icecatimport.xml';
        $package_bintime_xml = Mage::getBaseDir('var') . '/package/IcecatLive-1.5.0.xml';
        $cache_iceshop_dir = Mage::getBaseDir('var') . '/iceshop';

        $this->remove_dir($cache_iceshop_dir);
//        $this->remove_dir($code_dir);
        $this->remove_dir($template_dir);
        $this->remove_file($design_layout);
//        $this->remove_file($etc_bintime_xml);
        $this->remove_file($package_bintime_xml);
    }

    public function remove_dir($path)
    {
        if (file_exists($path) && is_dir($path)) {
            $dirHandle = opendir($path);
            while (false !== ($file = readdir($dirHandle))) {
                if ($file != '.' && $file != '..') {
                    $tmpPath = $path . '/' . $file;
                    chmod($tmpPath, 0777);

                    if (is_dir($tmpPath)) {
                        $this->remove_dir($tmpPath);
                    } else {
                        if (file_exists($tmpPath)) {
                            unlink($tmpPath);
                        }
                    }
                }
            }
            closedir($dirHandle);

            if (file_exists($path)) {
                rmdir($path);
            }
        }
    }

    public function remove_file($path)
    {
        if (file_exists($path)) {
            unlink($path);
        }
    }
}
