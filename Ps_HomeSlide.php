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
class Ps_HomeSlide extends ObjectModel
{
    public $title;
    public $description;
    public $url;
    public $legend;
    public $image;
    public $poster;
    public $active;
    public $position;
    public $id_shop;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'homeslider_slides',
        'primary' => 'id_homeslider_slides',
        'multilang' => true,
        'fields' => [
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
            'position' => ['type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true],

            // Lang fields
            'description' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => 4000],
            'title' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => 255],
            'legend' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => 255],
            'url' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isUrl', 'size' => 255],
            'image' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => 255],
            'poster' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => 255],
        ],
    ];

    public function add($autodate = true, $null_values = false)
    {
        $context = Context::getContext();
        $id_shop = $context->shop->id;

        $res = parent::add($autodate, $null_values);
        $res &= Db::getInstance()->execute('
			INSERT INTO `' . _DB_PREFIX_ . 'homeslider` (`id_shop`, `id_homeslider_slides`)
			VALUES(' . (int) $id_shop . ', ' . (int) $this->id . ')'
        );

        return $res;
    }

    public function delete()
    {
        $res = true;

        $images = $this->image;
        foreach ($images as $image) {
            if (preg_match('/sample/', $image) === 0) {
                if ($image && file_exists(__DIR__ . '/images/' . $image)) {
                    $res &= @unlink(__DIR__ . '/images/' . $image);
                }
            }
        }

        $posters = $this->poster;
        foreach ($posters as $poster) {
            if ($poster && file_exists(__DIR__ . '/images/' . $poster)) {
                $res &= @unlink(__DIR__ . '/images/' . $poster);
            }
        }

        $res &= $this->reOrderPositions();

        $res &= Db::getInstance()->execute('
			DELETE FROM `' . _DB_PREFIX_ . 'homeslider`
			WHERE `id_homeslider_slides` = ' . (int) $this->id
        );

        $res &= parent::delete();

        return $res;
    }

    public function reOrderPositions()
    {
        $id_slide = $this->id;
        $context = Context::getContext();
        $id_shop = $context->shop->id;

        $max = Db::getInstance((bool) _PS_USE_SQL_SLAVE_)->executeS('
			SELECT MAX(hss.`position`) as position
			FROM `' . _DB_PREFIX_ . 'homeslider_slides` hss, `' . _DB_PREFIX_ . 'homeslider` hs
			WHERE hss.`id_homeslider_slides` = hs.`id_homeslider_slides` AND hs.`id_shop` = ' . (int) $id_shop
        );

        if ((int) $max == (int) $id_slide) {
            return true;
        }

        $rows = Db::getInstance((bool) _PS_USE_SQL_SLAVE_)->executeS('
			SELECT hss.`position` as position, hss.`id_homeslider_slides` as id_slide
			FROM `' . _DB_PREFIX_ . 'homeslider_slides` hss
			LEFT JOIN `' . _DB_PREFIX_ . 'homeslider` hs ON (hss.`id_homeslider_slides` = hs.`id_homeslider_slides`)
			WHERE hs.`id_shop` = ' . (int) $id_shop . ' AND hss.`position` > ' . (int) $this->position
        );

        foreach ($rows as $row) {
            $current_slide = new Ps_HomeSlide($row['id_slide']);
            --$current_slide->position;
            $current_slide->update();
            unset($current_slide);
        }

        return true;
    }

    public static function getAssociatedIdsShop($id_slide)
    {
        $result = Db::getInstance((bool) _PS_USE_SQL_SLAVE_)->executeS('
			SELECT hs.`id_shop`
			FROM `' . _DB_PREFIX_ . 'homeslider` hs
			WHERE hs.`id_homeslider_slides` = ' . (int) $id_slide
        );

        if (!is_array($result)) {
            return false;
        }

        $return = [];

        foreach ($result as $id_shop) {
            $return[] = (int) $id_shop['id_shop'];
        }

        return $return;
    }
}
