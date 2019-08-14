<?php

class Iceshop_Icecatlive_Helper_Log extends Mage_Core_Helper_Abstract
{
    /**
     * @var string
     */
    private $_logFileName;

    /**
     * @var resource
     */
    private $_log;

    public function __construct()
    {
        $this->_logFileName = Mage::getBaseDir('log'). DS. 'temp_log.txt';
    }

    public function openLogFile()
    {
        $openMode = $this->fileExists() ? 'rw' : 'w';
        $this->_log = fopen($this->_logFileName, $openMode);
    }

    public function getLogValue($key)
    {
        if($this->_log) {
            $fileContent = file_get_contents($this->_logFileName);
            $lines = explode("\n", $fileContent);

            foreach ($lines as $line) {
                if($line != ''){
                    list($log_key, $log_value) = explode(' ', $line);
                    if ($log_key == $key . ':') {
                        return $log_value;
                    }
                }
            }
        }

        return false;
    }

    public function insertUpdateLog($key, $value)
    {
        if($this->_log) {
            $fileContent = file_get_contents($this->_logFileName);
            $lines = explode("\n", $fileContent);

            if (strpos($fileContent, $key . ':') === false) {
                $fileContent .= $key . ': ' . $value . "\n";
                file_put_contents($this->_logFileName, $fileContent);

                return true;
            } else {
                foreach ($lines as $line) {
                    if($line != ''){
                        list($log_key) = explode(' ', $line);
                        if ($log_key == $key . ':') {
                            $newContent = str_replace($line, $log_key. ' '. $value, $fileContent);
                            file_put_contents($this->_logFileName, $newContent);

                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    public function deleteLogValue($key)
    {
        if($this->_log) {
            $fileContent = file_get_contents($this->_logFileName);
            $lines = explode("\n", $fileContent);

            foreach($lines as $line){
                $explodeLine = explode(' ', $line);
                if(isset($explodeLine[0])){
                    if($explodeLine[0] == $key.':'){
                        $replace = str_replace($line, '', $fileContent);
                        file_put_contents($this->_logFileName, $replace);

                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function deleteLogFile()
    {
        fclose($this->_log);
        $this->_log = null;
        unlink($this->_logFileName);
    }

    public function lastUpdate()
    {
        $time = microtime(true) - filemtime($this->_logFileName);
        $time = (int)(round($time)*1000)/1000;
        return $time;
    }

    public function fileExists()
    {
        return file_exists($this->_logFileName);
    }
}