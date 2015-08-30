<?php
/**
 * @author      Tsvetan Stoychev <t.stoychev@extendix.com>
 * @website     http://www.extendix.com
 * @license     http://opensource.org/licenses/osl-3.0.php Open Software Licence 3.0 (OSL-3.0)
 */

class Extendix_AdminFormChooserButton_Block_ImageChooser
    extends Mage_Adminhtml_Block_Template
{

    /** @var bool  */
    protected $_isStrtrAdded = false;

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return Varien_Data_Form_Element_Abstract
     */
    public function prepareElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $config = $this->getConfig();

        $chooseButton = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setClass('scalable btn-chooser')
            ->setLabel($config['button']['open'])
            ->setOnclick('MediabrowserUtility.openDialog(\''.$this->getUrl('*/cms_wysiwyg_images/index', array('target_element_id' => $element->getName() . '_dummy')).'\')')
            ->setDisabled($element->getReadonly());

        $dummyInput = new Varien_Data_Form_Element_Hidden();

        $dummyInput->setForm($element->getForm())
            ->setId($element->getName() . '_dummy')
            ->setName($element->getName() . '_dummy')
            ->setReadonly(true);

        $fieldInput = new Varien_Data_Form_Element_Text();

        $fieldInput->setForm($element->getForm())
            ->setId($element->getName())
            ->setName($element->getName())
            ->setClass('widget-option input-text');

        if ($element->getRequired()) {
            $fieldInput->addClass('required-entry');
        }

        if ($element->getValue()) {
            $fieldInput->setValue($element->getValue());
        }

        $strTrFunctionJs = '';

        if(!$this->_isStrtrAdded) {
            $strTrFunctionJs = '
                <script type="text/javascript">//<![CDATA[
                var extendix_strtr = function (str, from, to) {
                    var out = "", i, m, p;
                        for (i = 0, m = str.length; i < m; i++) {
                            p = from.indexOf(str.charAt(i));
                            if (p >= 0) {
                            out = out + to.charAt(p);
                        } else {
                            out += str.charAt(i);
                        }
                    }
                    return out;
                };
                //]]></script>
            ';

            $this->_isStrtrAdded = true;
        }

        $extraJsHtml = $strTrFunctionJs . '
            <script type="text/javascript">//<![CDATA[

                new Form.Element.Observer(
                  \'' . $dummyInput->getId() . '\',
                  0.2,  // 200 milliseconds
                  function(el, value) {
                    var regex = new RegExp(\'/\\\___directive\/([^\/]+)\/\');
                    var imagePathBase64 = regex.exec(value)[1];
                    var dirtyImagePath = Base64.decode(extendix_strtr(imagePathBase64, "-_,", "+/="));
                    dirtyImagePath = dirtyImagePath.replace(\'{{media url="\',\'\');
                    var relativeImagePath = dirtyImagePath.replace(\'"}}\', \'\');

                    $(\'' . $fieldInput->getId() . '\').value = relativeImagePath;
                  }
                );
            //]]></script>
        ';

        $element->setData('after_element_html', $fieldInput->getElementHtml() . $dummyInput->getElementHtml() . $chooseButton->toHtml() . $extraJsHtml);

        return $element;
    }

}