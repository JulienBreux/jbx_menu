<?php
class Menu extends ObjectModel
{
    public $id;
	public $title = null;
	public $link = null;
	public $id_parent;
	public $id_item;
	public $type;
	public $level = 0;
	public $ignore = '';
	public $logged = 0;
	public $css = '';
    public $new_window = 0;
	public $active = 1;
	public $position;
	public $date_add;
	public $date_upd;
	protected $table = 'menu';
	protected $identifier = 'id_menu';
	protected $fieldsSizeLang = array('title' => 128, 'link' => 128);
	protected $fieldsValidateLang = array('title' => 'isGenericName', 'link' => 'isCleanHtml');

    public function getFields() {
        parent::validateFields();
 
        if (isset($this->id)) {
            $fields['id_menu'] = intval($this->id);
        }
        $fields['id_parent'] = intval($this->id_parent);
        $fields['id_item'] = intval($this->id_item);
        $fields['type'] = pSQL($this->type);
        $fields['level'] = intval($this->level);
        $fields['position'] = intVal($this->position);
        if (!is_null($this->ignore)) {
            $fields['ignore'] = pSQL($this->ignore);
        }
        $fields['logged'] = intval($this->logged);
        $fields['css'] = pSQL($this->css);
        $fields['new_window'] = pSQL($this->new_window);
        $fields['active'] = intval($this->active);
        $fields['date_add'] = pSQL($this->date_add);
        $fields['date_upd'] = pSQL($this->date_upd);

        return $fields;
    }

    public function getTranslationsFieldsChild() {
        parent::validateFieldsLang();
        return parent::getTranslationsFields(array('title', 'link'));
    }

    public function save($nullValues = false, $autodate = true) {
        return parent::save(true, true);
    }

    public function add($autodate = true, $nullValues = false) {
        $position = Db::getInstance()->getRow('SELECT (`position` + 1) as `new_position` FROM `' . _DB_PREFIX_ . 'menu` WHERE id_parent = "' . $this->id_parent . '" ORDER BY `position` DESC');
        $this->position = intval(isset($position['new_position']) ? $position['new_position'] : 1);
        return parent::add($autodate, $nullValues);
    }

    public function delete($childrens = null) {
    // Delete main
        if (is_null($childrens)) {
            $childrens = self:: getItems(1, false, $this->id);
            Db::getInstance()->Execute('DELETE FROM `'.pSQL(_DB_PREFIX_.$this->table).'` WHERE `'.pSQL($this->identifier).'` = '.intval($this->id));
            Db::getInstance()->Execute('DELETE FROM `'.pSQL(_DB_PREFIX_.$this->table).'_lang` WHERE `'.pSQL($this->identifier).'` = '.intval($this->id));
            Menu::remakePosition(intval($this->id_parent));
        }
        // Delete childrens
        foreach($childrens as $item) {
            Db::getInstance()->Execute('DELETE FROM `'.pSQL(_DB_PREFIX_.$this->table).'` WHERE `'.pSQL($this->identifier).'` = '.intval($item['id_menu']));
            Db::getInstance()->Execute('DELETE FROM `'.pSQL(_DB_PREFIX_.$this->table).'_lang` WHERE `'.pSQL($this->identifier).'` = '.intval($item['id_menu']));
            Menu::remakePosition($item['id_parent']);
            $this->delete($item['childrens']);
        }
    }

    /** END OF OBJECT MODEL **/
    public static function remakePosition($id_parent) {
        $results = Db::getInstance()->ExecuteS('
            SELECT `id_menu` FROM `' . _DB_PREFIX_ . 'menu` WHERE `id_parent` = "' . $id_parent . '" ORDER BY `position` ASC
        ');
        $position = 0;
        foreach($results as $result) {
            $position++;
            Db::getInstance()->Execute('UPDATE `' . _DB_PREFIX_ . 'menu` SET `position` = "' . $position .
            '" WHERE `id_parent` = "' . $id_parent . '" AND `id_menu` = "' . $result['id_menu'] . '"');
        }
    }

    public static function setUpPosition($id_menu) {
        $menu = new Menu(intval($id_menu));
        if (is_null($menu->id)) {
            return false;
        }

        // Get Min Position
        $minItems = 1;

        // Get Position Item
        $result = Db::getInstance()->ExecuteS('SELECT `position` FROM `' . _DB_PREFIX_ . 'menu` WHERE id_menu = "' . $menu->id . '" AND `id_parent` = "' . $menu->id_parent . '"');
        $position = intVal($result[0]['position']);

        // New Position
        $position--;

        if ($position >= $minItems) {
            // Set Old Position
            Db::getInstance()->Execute('UPDATE `' . _DB_PREFIX_ . 'menu` SET `position` = "' . ($position + 1) . '" WHERE `position` = "' . $position . '" AND `id_parent` = "' . $menu->id_parent . '"');
            // Set New Position
            Db::getInstance()->Execute('UPDATE `' . _DB_PREFIX_ . 'menu` SET `position` = "' . $position . '" WHERE `id_menu` = "' . $menu->id . '" AND `id_parent` = "' . $menu->id_parent . '"');
            return true;
        }

        return false;
    }

    public static function setDownPosition($id_menu) {
        $menu = new Menu(intval($id_menu));
        if (is_null($menu->id)) {
            return false;
        }

        // Get Max Position
        $result = Db::getInstance()->ExecuteS('SELECT COUNT(`id_menu`) as `maxItems` FROM `' . _DB_PREFIX_ . 'menu` WHERE `id_parent` = "' . $menu->id_parent . '"');
        $maxItems = intVal($result[0]['maxItems']);

        // Get Position Item
        $result = Db::getInstance()->ExecuteS('SELECT `position` FROM `' . _DB_PREFIX_ . 'menu` WHERE `id_menu` = "' . $menu->id . '" AND `id_parent` = "' . $menu->id_parent . '"');
        $position = intVal($result[0]['position']);

        // New Position
        $position++;

        if($position <= $maxItems) {
            // Set Old Position
            Db::getInstance()->Execute('UPDATE `' . _DB_PREFIX_ . 'menu` SET `position` = "' . ($position - 1) . '" WHERE `position` = "' . $position . '" AND `id_parent` = "' . $menu->id_parent . '"');
            // Set New Position
            Db::getInstance()->Execute('UPDATE `' . _DB_PREFIX_ . 'menu` SET `position` = "' . $position . '" WHERE `id_menu` = "' . $menu->id . '" AND `id_parent` = "' . $menu->id_parent . '"');
            return true;
        }
        return false;
    }

    public static function getTitle($id_menu, $id_lang) {
        $menu = new Menu($id_menu, $id_lang);
        $title = $menu->title;
        if (trim($title) == '') {
            // Spec. CMS
            if($menu->type == 'cms') {
                $object = new CMS($menu->id_item, $id_lang);
                $title = $object->meta_title;
            }
            else if ($menu->type != 'link' && $menu->type != 'manufacturers' && $menu->type != 'suppliers') {
                $objectName = ucfirst($menu->type);
                $object = new $objectName($menu->id_item, $id_lang);
                $title = $object->name;
                if (is_array($title)) {
                    $title = $object->name[$id_lang];
                }
            }
        }
        if(count(explode('.', $title)) > 1) {
            $title = str_replace('.', '', strstr($title, '.'));
        }
        return $title;
    }

    public static function getItemsForView($id_lang, $id_parent = 0) {
        if (self::isCached($id_lang)) {
            return self::getCache($id_lang);
        }

        $results = Db::getInstance()->ExecuteS('
            SELECT a.`id_menu`, a.`id_parent`, a.`id_item`, a.`type`, a.`level`, a.`ignore`, a.`logged`, a.`new_window`, a.`css`,  b.`title`, b.`link`
            FROM `' . _DB_PREFIX_ . 'menu` a
            LEFT JOIN `' . _DB_PREFIX_ . 'menu_lang` b ON (a.`id_menu` = b.`id_menu` AND b.`id_lang` = "' . $id_lang . '")
            WHERE a.`id_parent` = "' . $id_parent . '" AND a.`active` = 1
            ORDER BY a.`position` ASC
        ');
        foreach($results as $k=>$result) {
            if (is_array($result) && count($result)) {
                $childrens = self::getItemsForView($id_lang, $result['id_menu']);
            }
            else {
                $childrens = array();
            }
            // BEGIN - TITLE
            $link = $result['link'];
            if ($result['type'] == 'cms') {
                $object = new CMS($result['id_item'], $id_lang);
                $title = $object->meta_title;
                $cms = CMS::getLinks($id_lang, array($result['id_item']));
                if(count($cms)) {
                    $link = $cms[0]['link'];
                }
                else {
                    $link = '#';
                }
            }
            else if ($result['type'] != 'link' && $result['type'] != 'manufacturers' && $result['type'] != 'suppliers') {
                if ($result['type'] == 'product') {
                    $objectName = ucfirst($result['type']);
                    $object = new $objectName($result['id_item'], true, $id_lang);
                }
                else {
                    $objectName = ucfirst($result['type']);
                    $object = new $objectName($result['id_item'], $id_lang);
                }
                $title = $object->name;
                switch ($result['type']) {
                    case 'category':
                        $link = $object->getLink();
                        if (Configuration::get('MENU_CATEGORIES_NUM') == '2' && $result['id_item'] != '1') {
                            $results[$k]['numProducts'] = self::getNumProductsByCategory($result['id_item']);
                        }
                        $categories = self::getCategories(
                            $result['id_item'],
                            $id_lang,
                            explode(',', $result['ignore']),
                            intval($result['level'])
                        );
                        if (isset($categories[0]) && isset($categories[0]['childrens'])) {
                            $childrens = array_merge($childrens, $categories[0]['childrens']);
                        }
                        break;
                    case 'product':
                        $link = $object->getLink();
                        break;
                    case 'manufacturer':
                        if (intval(Configuration::get('PS_REWRITING_SETTINGS'))) {
                            $manufacturer->link_rewrite = Tools::link_rewrite($title, false);
                        }
                        else {
                            $manufacturer->link_rewrite = 0;
                        }
                        $_link = new Link;
                        $link = $_link->getManufacturerLink($result['id_item'], $object->link_rewrite);
                        break;
                    case 'supplier':
                        $_link = new Link;
                        $link = $_link->getSupplierLink($result['id_item'], $object->link_rewrite);
                        break;
                }
            }
            // Manufacturers
            if ($result['type'] == 'manufacturers') {
                $link = 'manufacturer.php';
                $childrens = self::getManufacturers();
            }
            // Suppliers
            if ($result['type'] == 'suppliers') {
                $link = 'supplier.php';
                $childrens = self::getSuppliers();
            }
            $results[$k]['link'] = $link;
            if (trim($result['title']) == '') {
                $results[$k]['title'] = $title;
            }
            if(count(explode('.', $results[$k]['title'])) > 1) {
                $results[$k]['title'] = str_replace('.', '', strstr($results[$k]['title'], '.'));
            }
            $results[$k]['id'] = $result['id_item'];
            unset(
                $results[$k]['id_parent'],
                $results[$k]['id_item'],
                $results[$k]['level'],
                $results[$k]['ignore']
            );
            // END - TITLE
            $results[$k]['childrens'] = $childrens;
        }
		if ($id_parent == 0) {
			self::setCache($results, $id_lang);
		}
        return $results;
    }

    public static function getItems($id_lang, $active = true, $id_parent = 0) {
        $results = Db::getInstance()->ExecuteS('
        SELECT a.`id_menu`, a.`id_parent`, a.`id_item`, a.`type`, a.`level`, a.`ignore`, b.`title`, b.`link`, a.`logged`, a.`css`, a.`active`, a.`position`, a.`date_add`, a.`date_upd`
        FROM `' . _DB_PREFIX_ . 'menu` a
        LEFT JOIN `' . _DB_PREFIX_ . 'menu_lang` b ON (a.`id_menu` = b.`id_menu` AND b.`id_lang` = "' . $id_lang . '")
        WHERE a.`id_parent` = "' . $id_parent . '" ' . ($active ? 'AND a.`active` = 1' : '') . '
        ORDER BY a.`position` ASC
        ');
        foreach ($results as $k=>$result) {
            if (is_array($result) && count($result)) {
                $results[$k]['childrens'] = self::getItems($id_lang, $active, $result['id_menu']);
            }
        }
        return $results;
    }

    public static function getNumProductsByCategory($id_category) {
        $num = Db::getInstance()->getRow('
        SELECT count(`id_product`) as num
        FROM `' . _DB_PREFIX_ . 'category_product`
        WHERE `id_category` = ' . intval($id_category) . '
        GROUP BY `id_category`');
        $num_cat = (isset($num['num'])) ? intval($num['num']) : 0;
        return $num_cat;
    }

    public static function getCategories($id_category, $id_lang, $ignore = array(), $maxLevel = 0, $currLevel = 0) {
        $results = array();
        $currLevel++;
        $categorie = new Category($id_category, $id_lang);

        if (is_null($categorie->id)) {
            return $results;
        }

        if (count(explode('.', $categorie->name)) > 1) {
            $title = str_replace('.', '', strstr($categorie->name, '.'));
        }
        else {
            $title = $categorie->name;
        }
        $link = $categorie->getLink();

        $childrens = array();
        $_childrens = Category::getChildren($id_category, $id_lang);
        if (count($_childrens)) {
            foreach($_childrens as $children) {
            $id_category = $children['id_category'];
            $children = self::getCategories($id_category, $id_lang, $ignore, $maxLevel, $currLevel);
                if (!in_array($id_category, $ignore)) {
                    if (isset($children[0])) {
                        $childrens[] = $children[0];
                    }
                }
            }
        }
        if (!in_array($categorie->id, $ignore) && !($currLevel > $maxLevel && $maxLevel != 0)) {
            if(count(explode('.', $title)) > 1) {
                $title = str_replace('.', '', strstr($title, '.'));
            }
            $results[] = array(
                'id' => $categorie->id,
                'id_menu' => '',
                'type' => 'category',
                'title' => $title,
                'logged' => null,
                'css'	=> '',
                'new_window' => false,
                'level' => $currLevel,
                'numProducts' => Configuration::get('MENU_CATEGORIES_NUM') ? self::getNumProductsByCategory($categorie->id) : 0,
                'link' => $link,
                'childrens' => $childrens,
            );
        }
        return $results;
    }

    public static function getManufacturers() {
        global $cookie;
        $results = array();
        $manufacturers = Manufacturer::getManufacturers(false, $cookie->id_lang);
        foreach($manufacturers as $_manufacturer) {
            $manufacturer = new Manufacturer(intVal($_manufacturer['id_manufacturer']));
            $title = $manufacturer->name;
            if (intval(Configuration::get('PS_REWRITING_SETTINGS'))) {
                $manufacturer->link_rewrite = Tools::link_rewrite($title, false);
            }
            else {
                $manufacturer->link_rewrite = 0;
            }
            $_link = new Link;
            $link = $_link->getManufacturerLink($manufacturer->id, $manufacturer->link_rewrite);
            $results[] = array(
                'id' => $manufacturer->id,
                'type' => 'manufacturer',
                'title' => $title,
                'link' => $link,
                'childrens' => array(),
            );
        }
        return $results;
    }

    public static function getSuppliers() {
        global $cookie;
        $results = array();
        $suppliers = Supplier::getSuppliers(false, $cookie->id_lang);
        foreach($suppliers as $_supplier) {
            $supplier = new Supplier(intVal($_supplier['id_supplier']));
            $title = $supplier->name;
            if (intval(Configuration::get('PS_REWRITING_SETTINGS'))) {
                $supplier->link_rewrite = Tools::link_rewrite($title, false);
            }
            else {
                $supplier->link_rewrite = 0;
            }
            $_link = new Link;
            $link = $_link->getSupplierLink($supplier->id, $supplier->link_rewrite);
            $results[] = array(
                'id' => $supplier->id,
                'type' => 'supplier',
                'title' => $title,
                'link' => $link,
                'childrens' => array(),
            );
        }
        return $results;
    }

	public static function createCssCache()
	{
		$content  = '';
		$content .= '.sf-contener, .sf-menu {width:' . Configuration::get('MENU_WIDTH') . 'px;}' . "\n";
		$content .= '.sf-menu {line-height:' . Configuration::get('MENU_HEIGHT') . '}' . "\n";
		$content .= '.sf-menu li:hover ul, .sf-menu li.sfHover ul {z-index:' . Configuration::get('MENU_INDEX') . ';}' . "\n";
		$content .= '.sf-menu a {font-size:' . Configuration::get('MENU_TEXT_SIZE') . 'px}'."\n";
		$content .= '.sf-menu span {vertical-align: ' . Configuration::get('MENU_TEXT_VERTICAL') . 'px}'."\n";
		$content .= '.sf-menu li li, .sf-menu li li li {background:#' . Configuration::get('MENU_ITEM_COLOR') . ';}'."\n";
		$content .= '.sf-menu ul li:hover, .sf-menu ul li.sfHover, .sf-menu ul li a:focus, .sf-menu ul li a:hover, .sf-menu ul li a:active {background:#' . Configuration::get('MENU_ITEM_HOVER_COLOR') . ';}'."\n";

		// ITEM SIZE
		$content .= '.sf-menu ul {width: ' . Configuration::get('MENU_ITEM_SIZE') . 'em;}' . "\n";
		$content .= 'ul.sf-menu li li:hover ul, ul.sf-menu li li.sfHover ul {left: ' . Configuration::get('MENU_ITEM_SIZE') . 'em;}' . "\n";
		$content .= 'ul.sf-menu li li:hover ul, ul.sf-menu li li.sfHover ul {left: ' . Configuration::get('MENU_ITEM_SIZE') . 'em;}' . "\n";
		$content .= 'ul.sf-menu li li li:hover ul, ul.sf-menu li li li.sfHover ul {left: ' . Configuration::get('MENU_ITEM_SIZE') . 'em;}' . "\n";

		// TEXT
		$styles = 'color:#' . Configuration::get('MENU_TEXT_COLOR') . ';';
		if (Configuration::get('MENU_TEXT_BOLD')) {
		  $styles .= 'font-weight:bold;';
		}
		if (Configuration::get('MENU_TEXT_ITALIC')) {
		  $styles .= 'font-style:italic;';
		}
		if (Configuration::get('MENU_TEXT_UNDERLINE')) {
		  $styles .= 'text-decoration:underline;';
		}
		$content .= '.sf-menu a, .sf-menu a:visited  {' . $styles . '}' . "\n";

		// TEXT OVER
		$styles = 'color:#' . Configuration::get('MENU_TEXT_OVER_COLOR') . ';';
		if (Configuration::get('MENU_TEXT_OVER_BOLD')) {
		  $styles .= 'font-weight:bold;';
		}
		if (Configuration::get('MENU_TEXT_OVER_ITALIC')) {
		  $styles .= 'font-style:italic;';
		}
		if (Configuration::get('MENU_TEXT_OVER_UNDERLINE')) {
		  $styles .= 'text-decoration:underline;';
		}
		$content .= '.sf-menu a:hover  {' . $styles . '}' . "\n";

		file_put_contents(self::getModulePath('cache') . 'menu.css', $content);
	}

    public static function setCache($data, $id_lang)
	{
		if (self::cacheIsWritable()) {
			file_put_contents(self::getModulePath('cache') . 'menu.' . $id_lang, serialize($data));
			Configuration::updateValue('MENU_CACHE_LATEST', array($id_lang => time()));
		}
    }

    public static function getCache($id_lang)
	{
		$data = file_get_contents(self::getModulePath('cache') . 'menu.' . $id_lang);
		return unserialize($data);
    }

    public static function isCached($id_lang)
	{
		if (Configuration::get('MENU_CACHE_ENABLE') == 0 || !self::cacheIsWritable()) {
			return false;
		}
		$now = (int) time();
		$refresh = (int) Configuration::get('MENU_CACHE_REFRESH');
		$latest = (int) Configuration::get('MENU_CACHE_LATEST', $id_lang);
		//exit(date('H:i:s', $now) . ' <= ' . date('H:i:s', $latest + $refresh));
		//exit(var_dump($now <= ($latest + $refresh)));
        return $now <= ($latest + $refresh);
    }

	public static function cacheIsWritable()
	{
		return self::is_writable(self::getModulePath('cache'));
	}

	public static function forceCache()
	{
		$languages = Language::getLanguages();
		foreach ($languages as $language) {
			Configuration::updateValue('MENU_CACHE_LATEST', array($language['id_lang'] => 0));
		}
	}

    public static function haveGd() {
        return in_array('gd', get_loaded_extensions());
    }

    public static function colorize(&$i, $color, $light = 0) {
        imagetruecolortopalette($i, true, 256);

        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));

        $colors = imageColorsTotal($i);
        if ((($r < -255) || ($r > 255)) || (($g < -255) || ($g > 255)) || (($b < -255) || ($b > 255))) {
            return;
        }

        $rR = $rG = $rB = 1/3;

        for ($line = 0; $line < $colors; $line++) {
            $indexColor = imagecolorsforindex($i, $line);
            $color = min(255, abs($indexColor['red'] * $rR + $indexColor['green'] * $rG + $indexColor['blue'] * $rB) + $light);

            $_r = min(255, $color + $r);
            $_g = min(255, $color + $g);
            $_b = min(255, $color + $b);

            imagecolorset($i, $line, $_r, $_g, $_b);
        }
    }

	public static function getModulePath($dir = null)
	{
		return _PS_MODULE_DIR_ . 'jbx_menu' . DIRECTORY_SEPARATOR .
			(!is_null($dir) ? $dir . DIRECTORY_SEPARATOR : '');
	}

	public static function is_writable($path)
	{
		// will work in despite of Windows ACLs bug
		// NOTE: use a trailing slash for folders!!!
		// see http://bugs.php.net/bug.php?id=27609
		// see http://bugs.php.net/bug.php?id=30931

    	if ($path{strlen($path)-1}=='/') // recursively return a temporary file path
        	return self::is_writable($path.uniqid(mt_rand()).'.tmp');
    	else if (is_dir($path))
        	return self::is_writable($path.'/'.uniqid(mt_rand()).'.tmp');
    	// check tmp file for read/write capabilities
    	$rm = file_exists($path);
    	$f = @fopen($path, 'a');
    	if ($f===false)
        	return false;
    	fclose($f);
    	if (!$rm)
        	unlink($path);
    	return true;
	}
}