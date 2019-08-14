<?php
class Iceshop_Icecatlive_Helper_Data extends Mage_Core_Helper_Abstract
{

    protected function _getButtonSettings($settings)
    {
        $default_settings = array(
            'getBeforeHtml' => '',
            'getId' => '',
            'getElementName' => '',
            'getTitle' => '',
            'getType' => '',
            'getClass' => '',
            'getOnClick' => '',
            'getStyle' => '',
            'getValue' => '',
            'getDisabled' => '',
            'getLabel' => '',
            'getAfterHtml' => ''
        );
        if (!empty($settings) && is_array($settings)) {
            foreach ($settings as $key => $setting) {
                $camel_key = str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
                $default_settings['get' . $camel_key] = $setting;
            }
        }
        return $default_settings;
    }

    /**
     * @param $settings
     * @return string
     */
    public function getButtonHtml($settings)
    {
        $settings = $this->_getButtonSettings($settings);
        $html = $settings['getBeforeHtml'] . '<button '
            . ($settings['getId'] ? ' id="' . $settings['getId'] . '"' : '')
            . ($settings['getElementName'] ? ' name="' . $settings['getElementName'] . '"' : '')
            . ' title="'
            . htmlspecialchars($settings['getTitle'] ? $settings['getTitle'] : $settings['getLabel'], ENT_QUOTES, null, false)
            . '"'
            . ' type="' . $settings['getType'] . '"'
            . ' class="scalable ' . $settings['getClass'] . ($settings['getDisabled'] ? ' disabled' : '') . '"'
            . ' onclick="' . $settings['getOnClick'] . '"'
            . ' style="' . $settings['getStyle'] . '"'
            . ($settings['getValue'] ? ' value="' . $settings['getValue'] . '"' : '')
            . ($settings['getDisabled'] ? ' disabled="disabled"' : '')
            . '><span><span><span>' . $settings['getLabel'] . '</span></span></span></button>' . $settings['getAfterHtml'];

        return $html;
    }

    /**
     * Sorts a multi-dimensional array with the given values
     *
     * Seen and modified from: http://www.firsttube.com/read/sorting-a-multi-dimensional-array-with-php/
     *
     * @param  array  $arr Array to sort
     * @param  string $key Field to sort
     * @param  string $dir Direction to sort
     * @return array  Sorted array
     */
    public function sortMultiDimArr($arr, $key, $dir = 'ASC') {

      foreach ($arr as $k => $v) {
          if (isset($v[$key])) {
              $b[$k] = strtolower($v[$key]);
          }
      }

      if (!empty($b)) {
          if ($dir == 'ASC') {
              asort($b);
          } else {
              arsort($b);
          }

          foreach ($b as $key => $val) {
              if (isset($arr[$key])) {
                  $c[] = $arr[$key];
              }
          }
          return $c;
      } else {
        return $arr;
      }

  }

}