<?php

/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

/*
 * @since   1.5.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

include_once __DIR__ . '/Ps_HomeSlide.php';

class Ps_ImageSlider extends Module implements WidgetInterface
{
    protected $_html = '';
    protected $default_speed = 5000;
    protected $default_pause_on_hover = 1;
    protected $default_wrap = 1;
    protected $templateFile;
    /**
     * @var string
     */
    public $secure_key;

    public function __construct()
    {
        $this->name = 'ps_imageslider';
        $this->tab = 'front_office_features';
        $this->version = '3.2.2';
        $this->author = 'PrestaShop';
        $this->need_instance = 0;
        $this->secure_key = Tools::hash($this->name);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Image slider', [], 'Modules.Imageslider.Admin');
        $this->description = $this->trans('Add sliding images to your homepage to welcome your visitors in a visual and friendly way.', [], 'Modules.Imageslider.Admin');
        $this->ps_versions_compliancy = ['min' => '1.7.5.0', 'max' => _PS_VERSION_];

        $this->templateFile = 'module:ps_imageslider/views/templates/hook/slider.tpl';
    }

    /**
     * @see Module::install()
     */
    public function install()
    {
        /* Adds Module */
        if (
            parent::install() &&
            $this->installTab() &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('displayHome') &&
            $this->registerHook('actionShopDataDuplication')
        ) {
            $shops = Shop::getContextListShopID();
            $shop_groups_list = [];
            $res = true;

            /* Setup each shop */
            foreach ($shops as $shop_id) {
                $shop_group_id = (int) Shop::getGroupFromShop($shop_id, true);

                if (!in_array($shop_group_id, $shop_groups_list)) {
                    $shop_groups_list[] = $shop_group_id;
                }

                /* Sets up configuration */
                $res &= Configuration::updateValue('HOMESLIDER_SPEED', $this->default_speed, false, $shop_group_id, $shop_id);
                $res &= Configuration::updateValue('HOMESLIDER_PAUSE_ON_HOVER', $this->default_pause_on_hover, false, $shop_group_id, $shop_id);
                $res &= Configuration::updateValue('HOMESLIDER_WRAP', $this->default_wrap, false, $shop_group_id, $shop_id);
            }

            /* Sets up Shop Group configuration */
            if (count($shop_groups_list)) {
                foreach ($shop_groups_list as $shop_group_id) {
                    $res &= Configuration::updateValue('HOMESLIDER_SPEED', $this->default_speed, false, $shop_group_id);
                    $res &= Configuration::updateValue('HOMESLIDER_PAUSE_ON_HOVER', $this->default_pause_on_hover, false, $shop_group_id);
                    $res &= Configuration::updateValue('HOMESLIDER_WRAP', $this->default_wrap, false, $shop_group_id);
                }
            }

            /* Sets up Global configuration */
            $res &= Configuration::updateValue('HOMESLIDER_SPEED', $this->default_speed);
            $res &= Configuration::updateValue('HOMESLIDER_PAUSE_ON_HOVER', $this->default_pause_on_hover);
            $res &= Configuration::updateValue('HOMESLIDER_WRAP', $this->default_wrap);

            /* Creates tables */
            $res &= $this->createTables();

            /* Adds samples */
            if ($res) {
                $this->installSamples();
            }

            return (bool) $res;
        }

        return false;
    }

    public function installTab()
    {
        $tab = new Tab();
        $tab->class_name = 'AdminConfigureSlides';
        $tab->module = $this->name;
        $tab->active = true;
        $tab->id_parent = -1;
        $tab->name = array_fill_keys(
            Language::getIDs(false),
            $this->displayName
        );

        return $tab->add();
    }

    /**
     * Adds samples
     */
    protected function installSamples()
    {
        $languages = Language::getLanguages(false);
        for ($i = 1; $i <= 3; ++$i) {
            $slide = new Ps_HomeSlide();
            $slide->position = $i;
            $slide->active = 1;
            foreach ($languages as $language) {
                $slide->title[$language['id_lang']] = 'Sample ' . $i;
                $slide->description[$language['id_lang']] = '<h3>EXCEPTEUR OCCAECAT</h3>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin tristique in tortor et dignissim. Quisque non tempor leo. Maecenas egestas sem elit</p>';
                $slide->legend[$language['id_lang']] = 'sample-' . $i;
                $slide->url[$language['id_lang']] = 'https://www.prestashop-project.org';
                $rtlSuffix = $language['is_rtl'] ? '_rtl' : '';
                $slide->image[$language['id_lang']] = sprintf('sample-%d%s.jpg', $i, $rtlSuffix);
            }
            $slide->add();
        }
    }

    /**
     * @see Module::uninstall()
     */
    public function uninstall()
    {
        /* Deletes Module */
        if (parent::uninstall()) {
            /* Deletes tables */
            $res = $this->deleteTables();

            /* Delete hidden tab */
            $res &= $this->uninstallTab();

            /* Unsets configuration */
            $res &= Configuration::deleteByName('HOMESLIDER_SPEED');
            $res &= Configuration::deleteByName('HOMESLIDER_PAUSE_ON_HOVER');
            $res &= Configuration::deleteByName('HOMESLIDER_WRAP');

            return (bool) $res;
        }

        return false;
    }

    public function uninstallTab()
    {
        $result = true;
        $id_tab = (int) Tab::getIdFromClassName('AdminConfigureSlides');
        $tab = new Tab($id_tab);
        if (Validate::isLoadedObject($tab)) {
            $result = $tab->delete();
        }

        return $result;
    }

    /**
     * Creates tables
     */
    protected function createTables()
    {
        /* Slides */
        $res = (bool) Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'homeslider` (
                `id_homeslider_slides` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `id_shop` int(10) unsigned NOT NULL,
                PRIMARY KEY (`id_homeslider_slides`, `id_shop`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;
        ');

        /* Slides configuration */
        $res &= Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'homeslider_slides` (
              `id_homeslider_slides` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `position` int(10) unsigned NOT NULL DEFAULT \'0\',
              `active` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
              PRIMARY KEY (`id_homeslider_slides`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;
        ');

        /* Slides lang configuration */
        $res &= Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'homeslider_slides_lang` (
              `id_homeslider_slides` int(10) unsigned NOT NULL,
              `id_lang` int(10) unsigned NOT NULL,
              `title` varchar(255) NOT NULL,
              `description` text NOT NULL,
              `legend` varchar(255) NOT NULL,
              `url` varchar(255) NOT NULL,
              `image` varchar(255) NOT NULL,
              PRIMARY KEY (`id_homeslider_slides`,`id_lang`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;
        ');

        return $res;
    }

    /**
     * deletes tables
     */
    protected function deleteTables()
    {
        $slides = $this->getSlides(null, true);
        foreach ($slides as $slide) {
            $to_del = new Ps_HomeSlide($slide['id_slide']);
            $to_del->delete();
        }

        return Db::getInstance()->execute('
            DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'homeslider`, `' . _DB_PREFIX_ . 'homeslider_slides`, `' . _DB_PREFIX_ . 'homeslider_slides_lang`;
        ');
    }

    public function getContent()
    {
        $this->_html .= $this->headerHTML();

        /* Validate & process */
        if (
            Tools::isSubmit('submitSlide') ||
            Tools::isSubmit('delete_id_slide') ||
            Tools::isSubmit('submitSlider') ||
            Tools::isSubmit('changeStatus')
        ) {
            if ($this->_postValidation()) {
                $this->_postProcess();
                $this->_html .= $this->renderForm();
                $this->_html .= $this->renderList();
            } else {
                $this->_html .= $this->renderAddForm();
            }

            $this->clearCache();
        } elseif (Tools::isSubmit('addSlide') || (Tools::isSubmit('id_slide') && $this->slideExists((int) Tools::getValue('id_slide')))) {
            if (Tools::isSubmit('addSlide')) {
                $mode = 'add';
            } else {
                $mode = 'edit';
            }

            if ($mode == 'add') {
                if (Shop::getContext() != Shop::CONTEXT_GROUP && Shop::getContext() != Shop::CONTEXT_ALL) {
                    $this->_html .= $this->renderAddForm();
                } else {
                    $this->_html .= $this->getShopContextError(null, $mode);
                }
            } else {
                $associated_shop_ids = Ps_HomeSlide::getAssociatedIdsShop((int) Tools::getValue('id_slide'));
                $context_shop_id = (int) Shop::getContextShopID();

                if ($associated_shop_ids === false) {
                    $this->_html .= $this->getShopAssociationError((int) Tools::getValue('id_slide'));
                } elseif (Shop::getContext() != Shop::CONTEXT_GROUP && Shop::getContext() != Shop::CONTEXT_ALL && in_array($context_shop_id, $associated_shop_ids)) {
                    if (count($associated_shop_ids) > 1) {
                        $this->_html = $this->getSharedSlideWarning();
                    }
                    $this->_html .= $this->renderAddForm();
                } else {
                    $shops_name_list = [];
                    foreach ($associated_shop_ids as $shop_id) {
                        $associated_shop = new Shop((int) $shop_id);
                        $shops_name_list[] = $associated_shop->name;
                    }
                    $this->_html .= $this->getShopContextError($shops_name_list, $mode);
                }
            }
        } else {
            $this->_html .= $this->getWarningMultishopHtml() . $this->getCurrentShopInfoMsg() . $this->renderForm();

            if (Shop::getContext() != Shop::CONTEXT_GROUP && Shop::getContext() != Shop::CONTEXT_ALL) {
                $this->_html .= $this->renderList();
            }
        }

        return $this->_html;
    }

    protected function _postValidation()
    {
        $errors = [];

        /* Validation for Slider configuration */
        if (Tools::isSubmit('submitSlider')) {
            if (!Validate::isInt(Tools::getValue('HOMESLIDER_SPEED'))) {
                $errors[] = $this->trans('Invalid values', [], 'Modules.Imageslider.Admin');
            }
        } elseif (Tools::isSubmit('changeStatus')) {
            if (!Validate::isInt(Tools::getValue('id_slide'))) {
                $errors[] = $this->trans('Invalid slide', [], 'Modules.Imageslider.Admin');
            }
        } elseif (Tools::isSubmit('submitSlide')) {
            /* Checks state (active) */
            if (!Validate::isInt(Tools::getValue('active_slide')) || (Tools::getValue('active_slide') != 0 && Tools::getValue('active_slide') != 1)) {
                $errors[] = $this->trans('Invalid slide state.', [], 'Modules.Imageslider.Admin');
            }
            /* If edit : checks id_slide */
            if (Tools::isSubmit('id_slide')) {
                if (!Validate::isInt(Tools::getValue('id_slide')) && !$this->slideExists(Tools::getValue('id_slide'))) {
                    $errors[] = $this->trans('Invalid slide ID', [], 'Modules.Imageslider.Admin');
                }
            }
            /* Checks title/url/legend/description/image */
            $languages = Language::getLanguages(false);
            foreach ($languages as $language) {
                if (Tools::strlen(Tools::getValue('title_' . $language['id_lang'])) > 255) {
                    $errors[] = $this->trans('The title is too long.', [], 'Modules.Imageslider.Admin');
                }
                if (Tools::strlen(Tools::getValue('legend_' . $language['id_lang'])) > 255) {
                    $errors[] = $this->trans('The caption is too long.', [], 'Modules.Imageslider.Admin');
                }
                if (Tools::strlen(Tools::getValue('url_' . $language['id_lang'])) > 255) {
                    $errors[] = $this->trans('The URL is too long.', [], 'Modules.Imageslider.Admin');
                }
                if (Tools::strlen(Tools::getValue('description_' . $language['id_lang'])) > 4000) {
                    $errors[] = $this->trans('The description is too long.', [], 'Modules.Imageslider.Admin');
                }
                if (Tools::strlen(Tools::getValue('url_' . $language['id_lang'])) > 0 && !Validate::isUrl(Tools::getValue('url_' . $language['id_lang']))) {
                    $errors[] = $this->trans('The URL format is not correct.', [], 'Modules.Imageslider.Admin');
                }
                if (Tools::getValue('image_' . $language['id_lang']) != null && !Validate::isFileName(Tools::getValue('image_' . $language['id_lang']))) {
                    $errors[] = $this->trans('Invalid filename.', [], 'Modules.Imageslider.Admin');
                }
                if (Tools::getValue('image_old_' . $language['id_lang']) != null && !Validate::isFileName(Tools::getValue('image_old_' . $language['id_lang']))) {
                    $errors[] = $this->trans('Invalid filename.', [], 'Modules.Imageslider.Admin');
                }
            }

            /* Checks title/legend/description for default lang */
            $id_lang_default = (int) Configuration::get('PS_LANG_DEFAULT');
            if (!Tools::isSubmit('has_picture') && (!isset($_FILES['image_' . $id_lang_default]) || empty($_FILES['image_' . $id_lang_default]['tmp_name']))) {
                $errors[] = $this->trans('The image is not set.', [], 'Modules.Imageslider.Admin');
            }
            if (Tools::getValue('image_old_' . $id_lang_default) && !Validate::isFileName(Tools::getValue('image_old_' . $id_lang_default))) {
                $errors[] = $this->trans('The image is not set.', [], 'Modules.Imageslider.Admin');
            }
        } elseif (Tools::isSubmit('delete_id_slide') && (!Validate::isInt(Tools::getValue('delete_id_slide')) || !$this->slideExists((int) Tools::getValue('delete_id_slide')))) {
            $errors[] = $this->trans('Invalid slide ID', [], 'Modules.Imageslider.Admin');
        }

        /* Display errors if needed */
        if (count($errors)) {
            $this->_html .= $this->displayError(implode('<br />', $errors));

            return false;
        }

        /* Returns if validation is ok */

        return true;
    }

    protected function _postProcess()
    {
        $errors = [];
        $shop_context = Shop::getContext();

        /* Processes Slider */
        if (Tools::isSubmit('submitSlider')) {
            $shop_groups_list = [];
            $shops = Shop::getContextListShopID();
            $res = true;

            foreach ($shops as $shop_id) {
                $shop_group_id = (int) Shop::getGroupFromShop($shop_id, true);

                if (!in_array($shop_group_id, $shop_groups_list)) {
                    $shop_groups_list[] = $shop_group_id;
                }

                $res &= Configuration::updateValue('HOMESLIDER_SPEED', (int) Tools::getValue('HOMESLIDER_SPEED'), false, $shop_group_id, $shop_id);
                $res &= Configuration::updateValue('HOMESLIDER_PAUSE_ON_HOVER', (int) Tools::getValue('HOMESLIDER_PAUSE_ON_HOVER'), false, $shop_group_id, $shop_id);
                $res &= Configuration::updateValue('HOMESLIDER_WRAP', (int) Tools::getValue('HOMESLIDER_WRAP'), false, $shop_group_id, $shop_id);
            }

            /* Update global shop context if needed*/
            switch ($shop_context) {
                case Shop::CONTEXT_ALL:
                    $res &= Configuration::updateValue('HOMESLIDER_SPEED', (int) Tools::getValue('HOMESLIDER_SPEED'));
                    $res &= Configuration::updateValue('HOMESLIDER_PAUSE_ON_HOVER', (int) Tools::getValue('HOMESLIDER_PAUSE_ON_HOVER'));
                    $res &= Configuration::updateValue('HOMESLIDER_WRAP', (int) Tools::getValue('HOMESLIDER_WRAP'));
                    if (count($shop_groups_list)) {
                        foreach ($shop_groups_list as $shop_group_id) {
                            $res &= Configuration::updateValue('HOMESLIDER_SPEED', (int) Tools::getValue('HOMESLIDER_SPEED'), false, $shop_group_id);
                            $res &= Configuration::updateValue('HOMESLIDER_PAUSE_ON_HOVER', (int) Tools::getValue('HOMESLIDER_PAUSE_ON_HOVER'), false, $shop_group_id);
                            $res &= Configuration::updateValue('HOMESLIDER_WRAP', (int) Tools::getValue('HOMESLIDER_WRAP'), false, $shop_group_id);
                        }
                    }
                    break;
                case Shop::CONTEXT_GROUP:
                    if (count($shop_groups_list)) {
                        foreach ($shop_groups_list as $shop_group_id) {
                            $res &= Configuration::updateValue('HOMESLIDER_SPEED', (int) Tools::getValue('HOMESLIDER_SPEED'), false, $shop_group_id);
                            $res &= Configuration::updateValue('HOMESLIDER_PAUSE_ON_HOVER', (int) Tools::getValue('HOMESLIDER_PAUSE_ON_HOVER'), false, $shop_group_id);
                            $res &= Configuration::updateValue('HOMESLIDER_WRAP', (int) Tools::getValue('HOMESLIDER_WRAP'), false, $shop_group_id);
                        }
                    }
                    break;
            }

            $this->clearCache();

            if (!$res) {
                $errors[] = $this->displayError($this->trans('The configuration could not be updated.', [], 'Modules.Imageslider.Admin'));
            } else {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true) . '&conf=6&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name);
            }
        } elseif (Tools::isSubmit('changeStatus') && Tools::isSubmit('id_slide')) {
            $slide = new Ps_HomeSlide((int) Tools::getValue('id_slide'));
            if ($slide->active == 0) {
                $slide->active = 1;
            } else {
                $slide->active = 0;
            }
            $res = $slide->update();
            $this->clearCache();
            $this->_html .= ($res ? $this->displayConfirmation($this->trans('Configuration updated', [], 'Admin.Notifications.Success')) : $this->displayError($this->getTranslator()->trans('The configuration could not be updated.', [], 'Modules.Imageslider.Admin')));
        } elseif (Tools::isSubmit('submitSlide')) {
            /* Sets ID if needed */
            if (Tools::getValue('id_slide')) {
                $slide = new Ps_HomeSlide((int) Tools::getValue('id_slide'));
                if (!Validate::isLoadedObject($slide)) {
                    $this->_html .= $this->displayError($this->trans('Invalid slide ID', [], 'Modules.Imageslider.Admin'));

                    return false;
                }
            } else {
                $slide = new Ps_HomeSlide();
                /* Sets position */
                $slide->position = (int) $this->getNextPosition();
            }
            /* Sets active */
            $slide->active = (int) Tools::getValue('active_slide');

            /* Sets each langue fields */
            $languages = Language::getLanguages(false);

            foreach ($languages as $language) {
                $slide->title[$language['id_lang']] = Tools::getValue('title_' . $language['id_lang']);
                $slide->url[$language['id_lang']] = Tools::getValue('url_' . $language['id_lang']);
                $slide->legend[$language['id_lang']] = Tools::getValue('legend_' . $language['id_lang']);
                $slide->description[$language['id_lang']] = Tools::getValue('description_' . $language['id_lang']);

                /* Uploads image and sets slide */
                $type = '';
                $imagesize = 0;

                if (
                    isset($_FILES['image_' . $language['id_lang']]) &&
                    !empty($_FILES['image_' . $language['id_lang']]['tmp_name'])
                ) {
                    $type = Tools::strtolower(Tools::substr(strrchr($_FILES['image_' . $language['id_lang']]['name'], '.'), 1));
                    $imagesize = @getimagesize($_FILES['image_' . $language['id_lang']]['tmp_name']);
                }

                if (
                    !empty($type) &&
                    !empty($imagesize) &&
                    in_array(
                        Tools::strtolower(Tools::substr(strrchr($imagesize['mime'], '/'), 1)),
                        [
                            'jpg',
                            'gif',
                            'jpeg',
                            'png',
                        ]
                    ) &&
                    in_array($type, ['jpg', 'gif', 'jpeg', 'png'])
                ) {
                    $temp_name = tempnam(_PS_TMP_IMG_DIR_, 'PS');
                    $salt = sha1(microtime());
                    $file_name = Tools::str2url($_FILES['image_' . $language['id_lang']]['name']) . $type;
                    if ($error = ImageManager::validateUpload($_FILES['image_' . $language['id_lang']])) {
                        $errors[] = $error;
                    } elseif (!$temp_name || !move_uploaded_file($_FILES['image_' . $language['id_lang']]['tmp_name'], $temp_name)) {
                        return false;
                    } elseif (!ImageManager::resize($temp_name, __DIR__ . '/images/' . $salt . '_' . $file_name, null, null, $type)) {
                        $errors[] = $this->displayError($this->trans('An error occurred during the image upload process.', [], 'Admin.Notifications.Error'));
                    }
                    if (file_exists($temp_name)) {
                        @unlink($temp_name);
                    }
                    $slide->image[$language['id_lang']] = $salt . '_' . $file_name;
                } elseif (Tools::getValue('image_old_' . $language['id_lang']) != '') {
                    $slide->image[$language['id_lang']] = Tools::getValue('image_old_' . $language['id_lang']);
                }
            }

            /* Processes if no errors  */
            if (!$errors) {
                /* Adds */
                if (!Tools::getValue('id_slide')) {
                    if (!$slide->add()) {
                        $errors[] = $this->displayError($this->trans('The slide could not be added.', [], 'Modules.Imageslider.Admin'));
                    }
                } elseif (!$slide->update()) {
                    $errors[] = $this->displayError($this->trans('The slide could not be updated.', [], 'Modules.Imageslider.Admin'));
                }
                $this->clearCache();
            }
        } elseif (Tools::isSubmit('delete_id_slide')) {
            $slide = new Ps_HomeSlide((int) Tools::getValue('delete_id_slide'));
            $res = $slide->delete();
            $this->clearCache();
            if (!$res) {
                $this->_html .= $this->displayError('Could not delete.');
            } else {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true) . '&conf=1&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name);
            }
        }

        /* Display errors if needed */
        if (count($errors)) {
            $this->_html .= $this->displayError(implode('<br />', $errors));
        } elseif (Tools::isSubmit('submitSlide') && Tools::getValue('id_slide')) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true) . '&conf=4&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name);
        } elseif (Tools::isSubmit('submitSlide')) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true) . '&conf=3&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name);
        }
    }

    public function hookdisplayHeader($params)
    {
        $this->context->controller->registerStylesheet('modules-homeslider', 'modules/' . $this->name . '/css/homeslider.css', ['media' => 'all', 'priority' => 150]);
        $this->context->controller->registerJavascript('modules-responsiveslides', 'modules/' . $this->name . '/js/responsiveslides.min.js', ['position' => 'bottom', 'priority' => 150]);
        $this->context->controller->registerJavascript('modules-homeslider', 'modules/' . $this->name . '/js/homeslider.js', ['position' => 'bottom', 'priority' => 150]);
    }

    public function renderWidget($hookName = null, array $configuration = [])
    {
        if (!$this->isCached($this->templateFile, $this->getCacheId())) {
            $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));
        }

        return $this->fetch($this->templateFile, $this->getCacheId());
    }

    public function getWidgetVariables($hookName = null, array $configuration = [])
    {
        $slides = $this->getSlides(true);
        if (is_array($slides)) {
            foreach ($slides as &$slide) {
                $slide['sizes'] = @getimagesize((__DIR__ . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $slide['image']));
                if (isset($slide['sizes'][3]) && $slide['sizes'][3]) {
                    $slide['size'] = $slide['sizes'][3];
                }
            }
        }

        $config = $this->getConfigFieldsValues();

        return [
            'homeslider' => [
                'speed' => $config['HOMESLIDER_SPEED'],
                'pause' => $config['HOMESLIDER_PAUSE_ON_HOVER'] ? 'hover' : '',
                'wrap' => $config['HOMESLIDER_WRAP'] ? 'true' : 'false',
                'slides' => $slides,
            ],
        ];
    }

    protected function validateUrl($link)
    {
        // Empty or anchor link.
        if (empty($link) || 0 === strpos($link, '#')) {
            return $link;
        }

        $host = parse_url($link, PHP_URL_HOST);
        // links starting with http://, https:// or // have $host determined, the rest needs more validation
        if (empty($host)) {
            if (preg_match('/^(?!\-|index\.php)(?:(?:[a-z\d][a-z\d\-]{0,61})?[a-z\d]\.){1,126}(?!\d+)[a-z\d]{1,63}/i', $link)) {
                // handle strings considered to be domain names without protocol eg. 'prestashop.com', excluding 'index.php'
                // ref. https://stackoverflow.com/a/16491074/6389945
                $link = '//' . $link;
            } else {
                // consider other links shop internal and add shop domain in front
                $link = $this->context->link->getBaseLink() . ltrim($link, '/');
            }
        }

        return $link;
    }

    public function clearCache()
    {
        $this->_clearCache($this->templateFile);
    }

    public function hookActionShopDataDuplication($params)
    {
        Db::getInstance()->execute(
            'INSERT IGNORE INTO ' . _DB_PREFIX_ . 'homeslider (id_homeslider_slides, id_shop)
            SELECT id_homeslider_slides, ' . (int) $params['new_id_shop'] . '
            FROM ' . _DB_PREFIX_ . 'homeslider
            WHERE id_shop = ' . (int) $params['old_id_shop']
        );
        $this->clearCache();
    }

    public function headerHTML()
    {
        // Run only on module configuration page
        if (Tools::getValue('controller') != 'AdminModules' || Tools::getValue('configure') !== $this->name) {
            return;
        }

        // Add sortable library
        $this->context->controller->addJS($this->_path . 'js/Sortable.min.js?v=' . $this->version);

        // Add sorting scripts
        $html = '
        <script type="text/javascript">
            $(function () {
                var slideList = $("#slides");
            
                // Check if the list exists, so we dont run it on edit page
                if (!slideList.length) {
                return;
                }
            
                new Sortable(slideList[0], {
                animation: 150,
                onUpdate: function (event) {
                    var slideIdList = this.toArray();
                    var ajaxCallParameters = {
                    ajax: true,
                    action: "updateSlidesPosition",
                    slides: slideIdList
                    };
                    $.ajax({
                    type: "POST",
                    cache: false,
                    url: "' . $this->context->link->getAdminLink('AdminConfigureSlides') . '",
                    data: ajaxCallParameters
                    });
                }
                });
            
                slideList.hover(
                function () {
                    $(this).css("cursor", "move");
                },
                function () {
                    $(this).css("cursor", "auto");
                }
                );
            });
        </script>';

        return $html;
    }

    public function getNextPosition()
    {
        $row = Db::getInstance((bool) _PS_USE_SQL_SLAVE_)->getRow(
            'SELECT MAX(hss.`position`) AS `next_position`
            FROM `' . _DB_PREFIX_ . 'homeslider_slides` hss, `' . _DB_PREFIX_ . 'homeslider` hs
            WHERE hss.`id_homeslider_slides` = hs.`id_homeslider_slides` AND hs.`id_shop` = ' . (int) $this->context->shop->id
        );

        return ++$row['next_position'];
    }

    /**
     * Get slides
     *
     * @param bool $active
     * @param bool $forceShowAll Include all slides, even those without image for a given language
     *
     * @return array
     */
    public function getSlides($active = null, $forceShowAll = false)
    {
        $this->context = Context::getContext();
        $id_shop = $this->context->shop->id;
        $id_lang = $this->context->language->id;

        $slides = Db::getInstance((bool) _PS_USE_SQL_SLAVE_)->executeS(
            'SELECT hs.`id_homeslider_slides` as id_slide, hss.`position`, hss.`active`, hssl.`title`,
            hssl.`url`, hssl.`legend`, hssl.`description`, hssl.`image`
            FROM ' . _DB_PREFIX_ . 'homeslider hs
            LEFT JOIN ' . _DB_PREFIX_ . 'homeslider_slides hss ON (hs.id_homeslider_slides = hss.id_homeslider_slides)
            LEFT JOIN ' . _DB_PREFIX_ . 'homeslider_slides_lang hssl ON (hss.id_homeslider_slides = hssl.id_homeslider_slides)
            WHERE id_shop = ' . (int) $id_shop . '
            AND hssl.id_lang = ' . (int) $id_lang .
            ($forceShowAll ? '' : ' AND hssl.`image` <> ""') .
            ($active ? ' AND hss.`active` = 1' : ' ') . '
            ORDER BY hss.position'
        );

        foreach ($slides as &$slide) {
            $slide['image_url'] = $this->context->link->getMediaLink(_MODULE_DIR_ . 'ps_imageslider/images/' . $slide['image']);
            $slide['url'] = $this->validateUrl($slide['url']);
        }

        return $slides;
    }

    public function getAllImagesBySlidesId($id_slides, $active = null, $id_shop = null)
    {
        $this->context = Context::getContext();
        $images = [];

        if (!isset($id_shop)) {
            $id_shop = $this->context->shop->id;
        }

        $results = Db::getInstance((bool) _PS_USE_SQL_SLAVE_)->executeS(
            'SELECT hssl.`image`, hssl.`id_lang`
            FROM ' . _DB_PREFIX_ . 'homeslider hs
            LEFT JOIN ' . _DB_PREFIX_ . 'homeslider_slides hss ON (hs.id_homeslider_slides = hss.id_homeslider_slides)
            LEFT JOIN ' . _DB_PREFIX_ . 'homeslider_slides_lang hssl ON (hss.id_homeslider_slides = hssl.id_homeslider_slides)
            WHERE hs.`id_homeslider_slides` = ' . (int) $id_slides . ' AND hs.`id_shop` = ' . (int) $id_shop .
            ($active ? ' AND hss.`active` = 1' : ' ')
        );

        foreach ($results as $result) {
            $images[$result['id_lang']] = $result['image'];
        }

        return $images;
    }

    public function displayStatus($id_slide, $active)
    {
        $title = ((int) $active == 0 ? $this->trans('Disabled', [], 'Admin.Global') : $this->trans('Enabled', [], 'Admin.Global'));
        $icon = ((int) $active == 0 ? 'icon-remove' : 'icon-check');
        $class = ((int) $active == 0 ? 'btn-danger' : 'btn-success');
        $html = '<a class="btn ' . $class . '" href="' . $this->context->link->getAdminLink('AdminModules', true, [], [
            'configure' => $this->name,
            'changeStatus' => '1',
            'id_slide' => (int) $id_slide,
        ]) . '" title="' . $title . '"><i class="' . $icon . '"></i> ' . $title . '</a>';

        return $html;
    }

    public function slideExists($id_slide)
    {
        $req = 'SELECT hs.`id_homeslider_slides` as id_slide
                FROM `' . _DB_PREFIX_ . 'homeslider` hs
                WHERE hs.`id_homeslider_slides` = ' . (int) $id_slide;
        $row = Db::getInstance((bool) _PS_USE_SQL_SLAVE_)->getRow($req);

        return $row;
    }

    public function renderList()
    {
        $slides = $this->getSlides(null, true);
        foreach ($slides as $key => $slide) {
            $slides[$key]['status'] = $this->displayStatus($slide['id_slide'], $slide['active']);
            $associated_shop_ids = Ps_HomeSlide::getAssociatedIdsShop((int) $slide['id_slide']);
            if ($associated_shop_ids && count($associated_shop_ids) > 1) {
                $slides[$key]['is_shared'] = true;
            } else {
                $slides[$key]['is_shared'] = false;
            }
        }

        $this->context->smarty->assign(
            [
                'link' => $this->context->link,
                'slides' => $slides,
                'image_baseurl' => $this->_path . 'images/',
            ]
        );

        return $this->display(__FILE__, 'list.tpl');
    }

    public function renderAddForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Slide information', [], 'Modules.Imageslider.Admin'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'file_lang',
                        'label' => $this->trans('Image', [], 'Admin.Global'),
                        'name' => 'image',
                        'required' => true,
                        'lang' => true,
                        'desc' => $this->trans('Maximum image size: %s.', [ini_get('upload_max_filesize')], 'Admin.Global'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Title', [], 'Admin.Global'),
                        'name' => 'title',
                        'lang' => true,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Target URL', [], 'Modules.Imageslider.Admin'),
                        'name' => 'url',
                        'lang' => true,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Caption', [], 'Modules.Imageslider.Admin'),
                        'name' => 'legend',
                        'lang' => true,
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->trans('Description', [], 'Admin.Global'),
                        'name' => 'description',
                        'autoload_rte' => true,
                        'lang' => true,
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Enabled', [], 'Admin.Global'),
                        'name' => 'active_slide',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->trans('Yes', [], 'Admin.Global'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->trans('No', [], 'Admin.Global'),
                            ],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];

        if (Tools::isSubmit('id_slide') && $this->slideExists((int) Tools::getValue('id_slide'))) {
            $slide = new Ps_HomeSlide((int) Tools::getValue('id_slide'));
            $fields_form['form']['input'][] = ['type' => 'hidden', 'name' => 'id_slide'];
            $fields_form['form']['images'] = $slide->image;

            $has_picture = true;

            foreach (Language::getLanguages(false) as $lang) {
                if (!isset($slide->image[$lang['id_lang']])) {
                    $has_picture &= false;
                }
            }

            if ($has_picture) {
                $fields_form['form']['input'][] = ['type' => 'hidden', 'name' => 'has_picture'];
            }
        }

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->module = $this;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitSlide';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $language = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->tpl_vars = [
            'base_url' => $this->context->shop->getBaseURL(),
            'language' => [
                'id_lang' => $language->id,
                'iso_code' => $language->iso_code,
            ],
            'fields_value' => $this->getAddFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
            'image_baseurl' => $this->_path . 'images/',
        ];

        $helper->override_folder = '/';

        $languages = Language::getLanguages(false);

        if (count($languages) > 1) {
            return $this->getMultiLanguageInfoMsg() . $helper->generateForm([$fields_form]);
        } else {
            return $helper->generateForm([$fields_form]);
        }
    }

    public function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Settings', [], 'Admin.Global'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->trans('Speed', [], 'Modules.Imageslider.Admin'),
                        'name' => 'HOMESLIDER_SPEED',
                        'suffix' => 'milliseconds',
                        'class' => 'fixed-width-sm',
                        'desc' => $this->trans('The duration of the transition between two slides.', [], 'Modules.Imageslider.Admin'),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Pause on hover', [], 'Modules.Imageslider.Admin'),
                        'name' => 'HOMESLIDER_PAUSE_ON_HOVER',
                        'desc' => $this->trans('Stop sliding when the mouse cursor is over the slideshow.', [], 'Modules.Imageslider.Admin'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->trans('Yes', [], 'Admin.Global'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->trans('No', [], 'Admin.Global'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Loop forever', [], 'Modules.Imageslider.Admin'),
                        'name' => 'HOMESLIDER_WRAP',
                        'desc' => $this->trans('Loop or stop after the last slide.', [], 'Modules.Imageslider.Admin'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->trans('Yes', [], 'Admin.Global'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->trans('No', [], 'Admin.Global'),
                            ],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitSlider';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fields_form]);
    }

    public function getConfigFieldsValues()
    {
        $id_shop_group = Shop::getContextShopGroupID();
        $id_shop = Shop::getContextShopID();

        return [
            'HOMESLIDER_SPEED' => Tools::getValue('HOMESLIDER_SPEED', Configuration::get('HOMESLIDER_SPEED', null, $id_shop_group, $id_shop)),
            'HOMESLIDER_PAUSE_ON_HOVER' => Tools::getValue('HOMESLIDER_PAUSE_ON_HOVER', Configuration::get('HOMESLIDER_PAUSE_ON_HOVER', null, $id_shop_group, $id_shop)),
            'HOMESLIDER_WRAP' => Tools::getValue('HOMESLIDER_WRAP', Configuration::get('HOMESLIDER_WRAP', null, $id_shop_group, $id_shop)),
        ];
    }

    public function getAddFieldsValues()
    {
        $fields = [];

        if (Tools::isSubmit('id_slide') && $this->slideExists((int) Tools::getValue('id_slide'))) {
            $slide = new Ps_HomeSlide((int) Tools::getValue('id_slide'));
            $fields['id_slide'] = (int) Tools::getValue('id_slide', $slide->id);
        } else {
            $slide = new Ps_HomeSlide();
        }

        $fields['active_slide'] = Tools::getValue('active_slide', $slide->active);
        $fields['has_picture'] = true;

        $languages = Language::getLanguages(false);

        foreach ($languages as $lang) {
            $fields['image'][$lang['id_lang']] = Tools::getValue('image_' . (int) $lang['id_lang']);
            $fields['title'][$lang['id_lang']] = Tools::getValue(
                'title_' . (int) $lang['id_lang'],
                isset($slide->title[$lang['id_lang']]) ? $slide->title[$lang['id_lang']] : ''
            );
            $fields['url'][$lang['id_lang']] = Tools::getValue(
                'url_' . (int) $lang['id_lang'],
                isset($slide->url[$lang['id_lang']]) ? $slide->url[$lang['id_lang']] : ''
            );
            $fields['legend'][$lang['id_lang']] = Tools::getValue(
                'legend_' . (int) $lang['id_lang'],
                isset($slide->legend[$lang['id_lang']]) ? $slide->legend[$lang['id_lang']] : ''
            );
            $fields['description'][$lang['id_lang']] = Tools::getValue(
                'description_' . (int) $lang['id_lang'],
                isset($slide->description[$lang['id_lang']]) ? $slide->description[$lang['id_lang']] : ''
            );
        }

        return $fields;
    }

    protected function getMultiLanguageInfoMsg()
    {
        return '<p class="alert alert-warning">' .
            $this->trans('Since multiple languages are activated on your shop, please mind to upload your image for each one of them', [], 'Modules.Imageslider.Admin') .
            '</p>';
    }

    protected function getWarningMultishopHtml()
    {
        if (Shop::getContext() == Shop::CONTEXT_GROUP || Shop::getContext() == Shop::CONTEXT_ALL) {
            return '<p class="alert alert-warning">' .
                $this->trans('You cannot manage slides items from a "All Shops" or a "Group Shop" context, select directly the shop you want to edit', [], 'Modules.Imageslider.Admin') .
                '</p>';
        } else {
            return '';
        }
    }

    protected function getShopContextError($shop_contextualized_name, $mode)
    {
        if (is_array($shop_contextualized_name)) {
            $shop_contextualized_name = implode('<br/>', $shop_contextualized_name);
        }

        if ($mode == 'edit') {
            return '<p class="alert alert-danger">' .
                $this->trans('You can only edit this slide from the shop(s) context: %s', [$shop_contextualized_name], 'Modules.Imageslider.Admin') .
                '</p>';
        } else {
            return '<p class="alert alert-danger">' .
                $this->trans('You cannot add slides from a "All Shops" or a "Group Shop" context', [], 'Modules.Imageslider.Admin') .
                '</p>';
        }
    }

    protected function getShopAssociationError($id_slide)
    {
        return '<p class="alert alert-danger">' .
            $this->trans('Unable to get slide shop association information (id_slide: %d)', [(int) $id_slide], 'Modules.Imageslider.Admin') .
            '</p>';
    }

    protected function getCurrentShopInfoMsg()
    {
        $shop_info = null;

        if (Shop::isFeatureActive()) {
            if (Shop::getContext() == Shop::CONTEXT_SHOP) {
                $shop_info = $this->trans('The modifications will be applied to shop: %s', [$this->context->shop->name], 'Modules.Imageslider.Admin');
            } elseif (Shop::getContext() == Shop::CONTEXT_GROUP) {
                $shop_info = $this->trans('The modifications will be applied to this group: %s', [Shop::getContextShopGroup()->name], 'Modules.Imageslider.Admin');
            } else {
                $shop_info = $this->trans('The modifications will be applied to all shops and shop groups', [], 'Modules.Imageslider.Admin');
            }

            return '<div class="alert alert-info">' . $shop_info . '</div>';
        } else {
            return '';
        }
    }

    protected function getSharedSlideWarning()
    {
        return '<p class="alert alert-warning">' .
            $this->trans('This slide is shared with other shops! All shops associated to this slide will apply modifications made here', [], 'Modules.Imageslider.Admin') .
            '</p>';
    }
}
