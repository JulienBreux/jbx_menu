<?php
if (!class_exists('menu', false)) {
	include _PS_MODULE_DIR_ . 'jbx_menu' . DIRECTORY_SEPARATOR . 'menu.class.php';
}
class jbx_menu extends Module
{
	private $_tabClass = 'AdminModuleMenu';
	private $_config = array(
		'MENU_CATEGORIES_ZERO' => 1,
		'MENU_CATEGORIES_NUM' => 1,
		'MENU_LEVEL' => 0,
		'MENU_WIDTH' => 974,
		'MENU_INDEX' => 1000,
		'MENU_HEIGHT' => 1.2,
		'MENU_SEARCH' => 1,
		'MENU_LANG' => 0,
		'MENU_BUTTON' => 1,
		'MENU_COMPLETION' => 1,
		'MENU_ICONS' => 1,
		'MENU_HOOK' => 'top',
		'MENU_MENU_COLOR' => '000000',
		'MENU_MENU_LIGHT' => 100,
		'MENU_ITEM_COLOR' => 'EBEBED',
		'MENU_ITEM_HOVER_COLOR' => 'D0D3D8',
		'MENU_ITEM_SIZE' => 13,
		'MENU_TEXT_COLOR' => 'ededed',
		'MENU_TEXT_OVER_COLOR' => '374853',
		'MENU_TEXT_BOLD' => 0,
		'MENU_TEXT_SIZE' => 12,
		'MENU_TEXT_VERTICAL' => 7,
		'MENU_TEXT_OVER_BOLD' => 0,
		'MENU_TEXT_ITALIC' => 0,
		'MENU_TEXT_OVER_ITALIC' => 0,
		'MENU_TEXT_UNDERLINE' => 0,
		'MENU_TEXT_OVER_UNDERLINE' => 0,
		'MENU_CACHE_ENABLE' => 0,
		'MENU_CACHE_LATEST' => 1,
		'MENU_CACHE_REFRESH' => 120,
		'MENU_STYLES_ENABLE' => 1,
		'MENU_ALLOW_OPTIONS' => true,
	);

	public function __construct()
	{
		$this->name = 'jbx_menu';
		$this->tab = 'front_office_features';
		$this->version = '2.8.6';
		$this->author = 'Julien Breux';

		parent::__construct();

		$this->page = basename(__FILE__, '.php');
		$this->displayName = $this->l('Menu');
		$this->description = $this->l('Add a new menu in your shop.');
	}

	public function install()
	{
		if(!parent::install() ||
			!$this->registerHook('header') ||
			!$this->registerHook('top') ||
			!$this->_installConfiguration() ||
			!$this->_installTab() ||
			!$this->_installDatabase()) {
			return false;
		}
		return true;
	}

	public function uninstall()
	{
		if(!parent::uninstall() ||
			!$this->_uninstallConfiguration() ||
			!$this->_uninstallTab() ||
			!$this->_uninstallDatabase()) {
			return false;
		}
		return true;
	}

	public function hookheader($params)
	{
		global $smarty, $cookie;
		$vars = array(
			'search_ssl' => (int)(isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off'),
			'path' => $this->_path,
			'searchable_active' => Configuration::get('MENU_SEARCH'),
			'searchable_button' => Configuration::get('MENU_BUTTON'),
			'searchable_autocomplete' => Configuration::get('MENU_COMPLETION'),
			'id_lang' => $cookie->id_lang,
			'id' => $this->_getId(),
			'category_id' => $this->_getCategoryId($cookie->id_lang),
			'categories_num' => intVal(Configuration::get('MENU_CATEGORIES_NUM')),
			'categories_zero' => Configuration::get('MENU_CATEGORIES_ZERO') ? true : false,
			//'tags_num' => intVal(Configuration::get('MENU_TAGS_NUM')),
			'icons' => Configuration::get('MENU_ICONS'),
			'icons_path' => dirname(__FILE__) . '/gfx/icons/',
			'icons_url' => $this->_path . 'gfx/icons/',
			'items' => Menu::getItemsForView($cookie->id_lang),
			'logged' => isset($cookie->id_customer) && $cookie->isLogged() ? true : false,
			//'theme' => Configuration::get('MENU_THEME'),
			'hook' => isset($parameters['preview']) ? 'menu' : Configuration::get('MENU_HOOK'),
		);

		// CSS Files
		if (Configuration::get('MENU_STYLES_ENABLE'))
			Tools::addCss(array(
				$this->_path . 'css/superfish-modified.css'		=> 'screen',
				$this->_path . 'cache/menu.css'					=> 'screen',
				__PS_BASE_URI__ . 'css/jquery.autocomplete.css'	=> 'screen',
			));

		// JS Files
		Tools::addJS(array(
			$this->_path . 'js/hoverIntent.js',
			$this->_path . 'js/superfish-modified.js',
			__PS_BASE_URI__ . 'js/jquery/jquery.autocomplete.js'
		));

		$smarty->assign('menu', $vars);
		return $this->display(__FILE__, 'header.tpl');
	}

	public function hooktop($parameters)
	{
		global $smarty;

		if (file_exists(_PS_THEME_DIR_.'modules/jbx_menu/menu_tree.tpl')) {
			$smarty->assign('menu_tpl_tree', _PS_THEME_DIR_ . 'modules/jbx_menu/menu_tree.tpl');
		}
		else {
			$smarty->assign('menu_tpl_tree', _PS_MODULE_DIR_ . 'jbx_menu/menu_tree.tpl');
		}

		$menu = $this->display(__FILE__, 'menu.tpl');

		if (Configuration::get('MENU_HOOK') == 'top') {
			return $menu;
		}
		else {
			$smarty->assign('HOOK_MENU', $menu);
		}
	}

	public function getContent()
	{
		global $cookie;
		$tab = 'AdminModuleMenu';
		$token = Tools::getAdminToken($tab.(int)(Tab::getIdFromClassName($tab)).(int)($cookie->id_employee));
		Tools::redirectAdmin('index.php?tab=' . $tab . '&token=' . $token);
	}

	private function _getId()
	{
		$id_category = Tools::getValue('id_category', 0);
		$id_product = Tools::getValue('id_product', 0);
		$id_cms = Tools::getValue('id_cms', 0);
		$id_manufacturer = Tools::getValue('id_manufacturer', 0);
		$id_supplier = Tools::getValue('id_supplier', 0);
		$id = $id_category + $id_product + $id_cms + $id_manufacturer + $id_supplier;
		return $id;
	}

	private function _getCategoryId($id_lang)
	{
		$category = false;
		if (isset($_SERVER['HTTP_REFERER']) AND preg_match('!^(.*)\/([0-9]+)\-(.*[^\.])|(.*)id_category=([0-9]+)(.*)$!', $_SERVER['HTTP_REFERER'], $regs) AND !strstr($_SERVER['HTTP_REFERER'], '.html'))
		{
			if (isset($regs[2]) AND is_numeric($regs[2]))
			{
				if (Product::idIsOnCategoryId((int)($this->_getId()), array('0' => array('id_category' => (int)($regs[2])))))
					$category = new Category((int)($regs[2]), $id_lang);
			}
			elseif (isset($regs[5]) AND is_numeric($regs[5]))
			{
				if (Product::idIsOnCategoryId((int)($this->_getId()), array('0' => array('id_category' => (int)($regs[5])))))
					$category = new Category((int)($regs[5]), $id_lang);
			}
		}
		
		if ($category)
			return $category->id;
	}

	private function _installConfiguration()
	{
		foreach ($this->_config as $key => $value) {
			Configuration::updateValue($key, $value);
		}
		return true;
	}

	private function _uninstallConfiguration()
	{
		foreach ($this->_config as $key => $value) {
			Configuration::deleteByName($key);
		}
		return true;
	}

	private function _installTab()
	{
		@copy(_PS_MODULE_DIR_ . $this->name . '/logo.gif', _PS_IMG_DIR_ . 't/' . $this->_tabClass . '.gif');
		$tab = new Tab();
		foreach (Language::getLanguages() as $language) {
			$tab->name[$language['id_lang']] = 'Menu';
		}
		$tab->class_name = $this->_tabClass;
		$tab->module = $this->name;
		$tab->id_parent = 7;
		$tab->save();

        $fields = array(
            'id_profile' => 1,
            'id_tab' => (int) $tab->id,
            'view' => 1,
            'add' => 1,
            'edit' => 1,
            'delete' => 1
        );
        Db::getInstance()->autoExecute(_DB_PREFIX_.'access', $fields, 'INSERT');
		return true;
	}

	private function _uninstallTab()
	{
		$idTab = Tab::getIdFromClassName($this->_tabClass);
		if ($idTab != 0) {
			@unlink(_PS_IMG_DIR_ . 't/' . $this->_tabClass . '.gif');
			$tab = new Tab($idTab);
			$tab->delete();
			return true;
		}
		return false;
    }

	private function _installDatabase()
	{
		$languages = Language::getLanguages();
		$database = Db::getInstance();
		$charset = 'utf8';
		$engine = 'InnoDB';
		if (defined('_MYSQL_ENGINE_')) {
			$engine = _MYSQL_ENGINE_;
		}
		// Add menu
		$database->Execute(
			'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'menu` (
			`id_menu` int(10) unsigned NOT NULL auto_increment,
			`id_parent` int(10) unsigned NOT NULL,
			`id_item` int(10) unsigned NOT NULL,
			`type` varchar(16) NOT NULL,
			`level` tinyint(3) unsigned NOT NULL,
			`ignore` varchar(128) default NULL,
			`logged` tinyint(1) NOT NULL default \'0\',
			`css` varchar(32) NULL,
			`new_window` tinyint(1) NOT NULL DEFAULT \'0\',
			`active` tinyint(1) unsigned NOT NULL,
			`position` tinyint(2) unsigned NOT NULL,
			`date_add` datetime NOT NULL,
			`date_upd` datetime NOT NULL,
			PRIMARY KEY  (`id_menu`)
			) ENGINE=' . $engine . ' DEFAULT CHARSET=' . $charset . ';'
		);
		$num = (int) $database->getValue('SELECT count(`id_menu`) FROM `' . _DB_PREFIX_ . 'menu`');
		if (!$num) {
			$database->Execute(
				"INSERT INTO `" . _DB_PREFIX_ . "menu` (`id_menu`, `id_parent`, `id_item`, `type`, `level`, `ignore`, `active`, `position`, `date_add`, `date_upd`) VALUES(1, 0, 0, 'link', 0, NULL, 1, 1, NOW(), NOW());"
			);
		}
		// Add menu_lang
		$database->Execute(
			'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'menu_lang` (
			`id_menu` int(10) unsigned NOT NULL,
			`id_lang` tinyint(2) unsigned NOT NULL,
			`title` varchar(128) NOT NULL,
			`link` varchar(128) NOT NULL,
			PRIMARY KEY  (`id_menu`,`id_lang`)
			) ENGINE=' . $engine . ' DEFAULT CHARSET=' . $charset . ';'
		);
		if (!$num) {
			foreach ($languages as $language) {
				$database->Execute(
					"INSERT INTO `" . _DB_PREFIX_ . "menu_lang` (`id_menu`, `id_lang`, `title`, `link`) VALUES(1, {$language['id_lang']}, 'Julien Breux', 'http://www.julien-breux.com');"
				);
			}
		}
		return true;
    }

	private function _uninstallDatabase()
	{
		// Remove menu tables
		if (!file_exists(Menu::getModulePath() . 'NO_REMOVE')) {
			Db::getInstance()->ExecuteS('DROP TABLE `' . _DB_PREFIX_ . 'menu`');
			Db::getInstance()->ExecuteS('DROP TABLE `' . _DB_PREFIX_ . 'menu_lang`');
		}
		return true;
	}
}
