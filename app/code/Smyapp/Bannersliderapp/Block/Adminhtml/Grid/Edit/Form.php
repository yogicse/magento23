<?php
namespace Smyapp\Bannersliderapp\Block\Adminhtml\Grid\Edit;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    protected $_systemStore;
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollection,
        \Smyapp\Bannersliderapp\Model\Status $options,
        array $data = []
    ) {
        $this->_options       = $options;
        $this->_wysiwygConfig = $wysiwygConfig;
        $this->_categoryCollection = $categoryCollection;
        parent::__construct($context, $registry, $formFactory, $data);
    }
    protected function _prepareForm()
    {
        $dateFormat = $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);
        $model      = $this->_coreRegistry->registry('row_data');
        $form       = $this->_formFactory->create(
            ['data' => [
                'id'      => 'edit_form',
                'enctype' => 'multipart/form-data',
                'action'  => $this->getData('action'),
                'method'  => 'post',
            ],
            ]
        );
        $form->setHtmlIdPrefix('Smyappslider_');
        if ($model->getBannerId()) {
            $fieldset = $form->addFieldset(
                'base_fieldset',
                ['legend' => __('Edit Banner'), 'class' => 'fieldset-wide']
            );
            $fieldset->addField('banner_id', 'hidden', ['name' => 'banner_id']);
        } else {
            $fieldset = $form->addFieldset(
                'base_fieldset',
                ['legend' => __('Add Banner'), 'class' => 'fieldset-wide']
            );
        }
        $fieldset->addField(
            'name',
            'text',
            [
                'name'     => 'name',
                'label'    => __('Title'),
                'required' => true,
            ]
        );
        if ($this->getRequest()->getParam('id')) {
            $fieldset->addField(
            'thumbnail',
            'image',
            [
                'name' => 'thumbnail',
                'title' => __('Image'),
                'label' => __('Image'),
                'required' => true,
                'class' => 'required-entry',
                'index' => 'image',
                'renderer'  => '\Smyapp\Bannersliderapp\Block\Adminhtml\Bannersliderapp\Grid\Renderer\LogoImage',

            ]
            );    
        } else {
            $fieldset->addField(
            'thumbnail',
            'image',
            [
                'name' => 'thumbnail',
                'title' => __('Image'),
                'label' => __('Image'),
                'required' => true,
                'class' => 'required-entry',
                'index' => 'image',
                'renderer'  => '\Smyapp\Bannersliderapp\Block\Adminhtml\Bannersliderapp\Grid\Renderer\LogoImage',

            ]
            )->setAfterElementHtml("<script type=\"text/javascript\">var d = document.getElementById(\"Smyappslider_thumbnail\");d.className += \" required-entry\";</script>");
        }
        
        $wysiwygConfig = $this->_wysiwygConfig->getConfig(['tab_id' => $this->getTabId()]);
        $fieldset->addField(
            'image_alt',
            'editor',
            [
                'name'   => 'image_alt',
                'label'  => __('Description'),
                'class'  => 'required-entry',
                'style'  => 'height:10em;',
                'config' => $wysiwygConfig,
            ]
        );
        $fieldset->addField(
            'order_banner',
            'text',
            [
                'name'  => 'order_banner',
                'label' => __('Position'),
            ]
        );
        $fieldset->addField(
            'url_type',
            'select',
            [
                'name'               => 'url_type',
                'label'              => __('Link To'),
                'values'             => [
                                            'Category' => 'Category',
                                            'Product' => 'Product'
                                        ],
                'after_element_html' => '<small>Add Catagory link section</small>',
            ]
        );
        $fieldset->addField(
            'check_type',
            'select',
            [
                'name'   => 'check_type',
                'label'  => __('Display on page'),
                'values' => [
                                'home_view' => 'Home view',
                                'category_view' => 'Category view'
                            ],
            ]
        );
        $fieldset->addField(
            'product_id',
            'text',
            [
                'name'   => 'product_id',
                'label'  => __('Product id to display'),
            ]
        );
        $optionArray = $this->getCategoryCollection();
        $fieldset->addField(
            'category_id',
            'select',
            [
                    'name' => 'category_id',
                    'class'  => 'category_display_id',
                    'label' => __('Categories'),
                    'title' => __('Categories'),
                    'values' => $optionArray,
                    'disabled' => false

            ]
        );
        $fieldset->addField(
            'status',
            'select',
            [
                'name'   => 'status',
                'label'  => __('Banner Status'),
                'values' => [
                                'enable' => 'Enable',
                                'disable' => 'Disable'],
            ]
        );
        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
    public function getCategoryCollection()
    {
        $collection = $this->_categoryCollection->create()
            ->addAttributeToSelect('*')
            ->setStore($this->_storeManager->getStore())
            //->addAttributeToFilter('attribute_code', '1')
            ->addAttributeToFilter('is_active','1');

        $result = array();
        foreach ($collection as $subKey => $subValue) {
            $result[$subValue->getId()] = $subValue->getName();
        }

       return $result;
    }
}
