<?php
/**
 * @author      Tsvetan Stoychev <t.stoychev@extendix.com>
 * @website     http://www.extendix.com
 * @license     http://opensource.org/licenses/osl-3.0.php Open Software Licence 3.0 (OSL-3.0)
 */

class Extendix_AdminFormChooserButton_Block_ImageChooser
    extends Mage_Adminhtml_Block_Template
{

    const DUMMY_INPUT_SUFFIX   = '_dummy';
    const IMAGE_PREVIEW_SUFFIX = '_image';

    /** @var null|Varien_Data_Form_Element_Text */
    protected $_fieldInput;

    /** @var null|Varien_Data_Form_Element_Hidden */
    protected $_dummyFieldInput;

    /** @var null|Mage_Adminhtml_Block_Widget_Button */
    protected $_imageChooserButton;

    /** @var null|Mage_Adminhtml_Block_Widget_Button */
    protected $_imageRemoveButton;

    /** @var bool  */
    protected $_isStrtrAdded = false;

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return Varien_Data_Form_Element_Abstract
     */
    public function prepareElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->_init($element);

        $strTrFunctionJs = '';

        if (!$this->_isStrtrAdded) {
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
                  \'' . $this->_dummyFieldInput->getHtmlId() . '\',
                  0.2,  // 200 milliseconds
                  function(el, value) {
                    var regex = new RegExp(\'/\\\___directive\/([^\/]+)\/\');
                    var imagePathBase64 = regex.exec(value)[1];
                    var dirtyImagePath = Base64.decode(extendix_strtr(imagePathBase64, "-_,", "+/="));
                    dirtyImagePath = dirtyImagePath.replace(\'{{media url="\',\'\');
                    var relativeImagePath = dirtyImagePath.replace(\'"}}\', \'\');

                    $(\'' . $this->_fieldInput->getHtmlId() . '\').value = relativeImagePath;

                    //Disable Insert Button
                    $(\'' . $this->_imageChooserButton->getHtmlId() . '\').writeAttribute(\'disabled\', true);
                    $(\'' . $this->_imageChooserButton->getHtmlId() . '\').addClassName(\'disabled\');

                    //Enable Remove Button
                    $(\'' . $this->_imageRemoveButton->getHtmlId() . '\').writeAttribute(\'disabled\', false);
                    $(\'' . $this->_imageRemoveButton->getHtmlId() . '\').removeClassName(\'disabled\');

                    //Display new image
                    $(\'' . $element->getHtmlId() . self::IMAGE_PREVIEW_SUFFIX . '\').parentNode.href = \'/media/\' + relativeImagePath;
                    $(\'' . $element->getHtmlId() . self::IMAGE_PREVIEW_SUFFIX . '\').src = \'/media/\' + relativeImagePath;
                    $(\'' . $element->getHtmlId() . self::IMAGE_PREVIEW_SUFFIX . '\').title = relativeImagePath;
                    $(\'' . $element->getHtmlId() . self::IMAGE_PREVIEW_SUFFIX . '\').alt = relativeImagePath;
                    $(\'' . $element->getHtmlId() . self::IMAGE_PREVIEW_SUFFIX . '\').parentNode.removeClassName(\'no-display\');
                  }
                );
            //]]></script>
        ';

        $element->setData('after_element_html', $this->_fieldInput->getElementHtml() . $this->_dummyFieldInput->getElementHtml() . $this->_getPreviewHtml($element) . $this->_imageChooserButton->toHtml() . $this->_imageRemoveButton->toHtml() . $extraJsHtml);

        return $element;
    }

    /**
     * We need to create some UI elements at the beginning in specific order
     * because we need a reference to those elements a bit later
     *
     * @param Varien_Data_Form_Element_Abstract $element
     */
    protected function _init(Varien_Data_Form_Element_Abstract $element)
    {
        $this->_dummyFieldInput    = $this->_getDummyFieldInput($element);
        $this->_fieldInput         = $this->_getFieldInput($element);
        $this->_imageChooserButton = $this->_getChooserButton();
        $this->_imageRemoveButton  = $this->_getRemoveButton();
    }

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getPreviewHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $value = $element->getValue();
        $url = !empty($value) ? Mage::getBaseUrl('media') . $value : '';

        $class = empty($url) ? ' class="no-display"' : '';

        // Add image preview.
        $previewHtml = '<a href="' . $url . '"'
            . $class
            . ' onclick="imagePreview(\'' . $element->getHtmlId() . self::IMAGE_PREVIEW_SUFFIX . '\'); return false;">'
            . '<img src="' . $url . '" id="' . $element->getHtmlId() . self::IMAGE_PREVIEW_SUFFIX . '" title="' . $element->getValue() . '"'
            . ' alt="' . $element->getValue() . '" height="40" class="small-image-preview v-middle"'
            . ' style="margin-top:7px; border:1px solid grey" />'
            . '</a> ';

        return $previewHtml;
    }

    /**
     * @return Mage_Adminhtml_Block_Widget_Button
     */
    protected function _getRemoveButton()
    {
        /** @var Mage_Adminhtml_Block_Widget_Button $removeButton */
        $removeButton = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setClass('delete')
            ->setLabel($this->__('Remove Image'))
            ->setOnclick('document.getElementById(\''. $this->_fieldInput->getHtmlId() .'\').value=\'\';if(document.getElementById(\''. $this->_fieldInput->getHtmlId() . self::IMAGE_PREVIEW_SUFFIX . '\'))$(\''. $this->_fieldInput->getHtmlId() . self::IMAGE_PREVIEW_SUFFIX . '\').parentNode.addClassName(\'no-display\');$(\'' . $this->_imageChooserButton->getHtmlId() . '\').writeAttribute(\'disabled\', false);$(\'' . $this->_imageChooserButton->getHtmlId() . '\').removeClassName(\'disabled\');$(this).addClassName(\'disabled\');$(this).writeAttribute(\'disabled\', true)')
            ->setDisabled($this->_fieldInput->getReadonly())
            ->setStyle('margin-left:10px;margin-top:7px');

        // Check if there is a value. If no value then we want the remove button to be disabled
        if (!$this->_fieldInput->getValue()) {
            $removeButton->setDisabled(true);
        }

        return $removeButton;
    }

    /**
     * @return Mage_Adminhtml_Block_Widget_Button
     */
    protected function _getChooserButton()
    {
        $config = $this->getConfig();

        /** @var Mage_Adminhtml_Block_Widget_Button $chooseButton */
        $chooseButton = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setClass('scalable add-image plugin')
            ->setLabel($config['button']['open'])
            ->setOnclick('MediabrowserUtility.openDialog(\''.$this->getUrl('*/cms_wysiwyg_images/index', array('target_element_id' => $this->_dummyFieldInput->getHtmlId())) . '\')')
            ->setStyle('display:inline;margin-top:7px');

        // Check if there is a value. If yes value then we want the chooser button to be disabled
        if ($this->_fieldInput->getValue()) {
            $chooseButton->setDisabled(true);
        }

        return $chooseButton;
    }

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return Varien_Data_Form_Element_Hidden
     */
    protected function _getDummyFieldInput(Varien_Data_Form_Element_Abstract $element)
    {
        $dummyInput = new Varien_Data_Form_Element_Hidden();

        $dummyInput->setForm($element->getForm())
            ->setId($this->_getDummyInputId($element))
            ->setName($this->_getDummyInputName($element))
            ->setReadonly(true);

        return $dummyInput;
    }

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return Varien_Data_Form_Element_Text
     */
    protected function _getFieldInput(Varien_Data_Form_Element_Abstract $element)
    {
        $fieldInput = new Varien_Data_Form_Element_Text();

        $fieldInput->setForm($element->getForm())
            ->setId($element->getId())
            ->setName($element->getName())
            ->setClass('widget-option input-text');

        if ($element->getRequired()) {
            $fieldInput->addClass('required-entry');
        }

        if ($element->getValue()) {
            $fieldInput->setValue($element->getValue());
        }

        return $fieldInput;
    }

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getDummyInputId(Varien_Data_Form_Element_Abstract $element)
    {
        return $element->getId() . self::DUMMY_INPUT_SUFFIX;
    }

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getDummyInputName(Varien_Data_Form_Element_Abstract $element)
    {
        return $element->getName() . self::DUMMY_INPUT_SUFFIX;
    }

}