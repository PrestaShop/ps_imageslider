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
class AdminConfigureSlidesController extends ModuleAdminController
{
    public function ajaxProcessUpdateSlidesPosition()
    {
        if (empty(Tools::getValue('action')) || Tools::getValue('action') != 'updateSlidesPosition' || empty(Tools::getValue('slides'))) {
            if (ob_get_contents()) {
	            ob_end_clean();
	        }
			
            header('Content-Type: application/json');
            $this->ajaxRender(json_encode(['error' => true]));
            exit;
        }

        // Get slides and update their position
        $slides = Tools::getValue('slides');
        foreach ($slides as $position => $id_slide) {
            Db::getInstance()->execute('
					UPDATE `' . _DB_PREFIX_ . 'homeslider_slides` SET `position` = ' . (int) $position . '
					WHERE `id_homeslider_slides` = ' . (int) $id_slide
                );
        }

        // Wipe module cache
        $this->module->clearCache();

        if (ob_get_contents()) {
			ob_end_clean();
		}
		
        header('Content-Type: application/json');
        $this->ajaxRender(json_encode(['success' => true]));
        exit;
    }
}
