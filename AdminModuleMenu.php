<?php
require_once PS_ADMIN_DIR . '/../classes/AdminTab.php';
require_once _PS_MODULE_DIR_ . 'jbx_menu/menu.class.php';
require_once _PS_MODULE_DIR_ . 'jbx_menu/jbx_menu.php';

class AdminModuleMenu extends AdminTab
{
    private $_module = 'jbx_menu';
    private $_modulePath = '';
    private $_html = '';
    private $_config = array(
        'MENU_CATEGORIES_ZERO',
        'MENU_CATEGORIES_NUM',
        'MENU_LEVEL',
        'MENU_WIDTH',
        'MENU_INDEX',
        'MENU_HEIGHT',
        'MENU_MENU_COLOR',
        'MENU_MENU_LIGHT',
        'MENU_ITEM_COLOR',
        'MENU_ITEM_HOVER_COLOR',
        'MENU_ITEM_SIZE',
        'MENU_TEXT_OVER_UNDERLINE',
        'MENU_TEXT_OVER_ITALIC',
        'MENU_TEXT_OVER_BOLD',
        'MENU_TEXT_OVER_COLOR',
        'MENU_TEXT_UNDERLINE',
        'MENU_TEXT_ITALIC',
        'MENU_TEXT_BOLD',
        'MENU_TEXT_COLOR',
        'MENU_TEXT_SIZE',
        'MENU_TEXT_VERTICAL',
        'MENU_LANG',
        'MENU_SEARCH',
        'MENU_BUTTON',
        'MENU_COMPLETION',
        'MENU_ICONS',
        'MENU_HOOK',
		'MENU_CACHE_ENABLE',
		'MENU_ALLOW_OPTIONS',
    );

    public function __construct() {
        $this->_modulePath =  _PS_MODULE_DIR_ . $this->_module . DIRECTORY_SEPARATOR;
        //$this->_importLang();
        parent::__construct();
    }

    public function display() {
        $this->_displayHeades();
        $this->_displayItemsList();
        $this->_displayItemAdd();
		if ($this->_isAdmin() || (bool) Configuration::get('MENU_ALLOW_OPTIONS')) {
        	$this->_displayOptionsForm();
		}
        echo $this->_html;
    }

  public function postProcess() {
    global $currentIndex;

    $id_menu = Tools::getValue('id_menu');

	Menu::forceCache();

    if (Tools::isSubmit('submitOptions')) {
      foreach ($this->_config as $key) {
        $key = subStr(strToLower($key), 5, strLen($key));
        Configuration::updateValue('MENU_' . strToUpper($key), Tools::getValue($key, 0));
        if ($key == 'menu_color' && Menu::haveGd()) {
          // LIGHT CALCUL
            $light = -150;
            if (isset($_GET['light']) && ((int)$_GET['light'] >= 0 && (int)$_GET['light'] <= 200)) {
                $light = (int)$_GET['light'] + $light;
            }
            else {
                $light += 100;
            }
          $img = imageCreateFromGif(_PS_MODULE_DIR_ . 'jbx_menu/gfx/menu/menu_orig.gif');
          Menu::colorize($img, Tools::getValue($key, '000000'), $light);
          imageGif($img, _PS_MODULE_DIR_ . 'jbx_menu/gfx/menu/menu.gif');
          imagedestroy($img);
          // OVER
          $img = imageCreateFromGif(_PS_MODULE_DIR_ . 'jbx_menu/gfx/menu/hover_orig.gif');
          Menu::colorize($img, Tools::getValue($key, '000000'), $light);
          imageGif($img, _PS_MODULE_DIR_ . 'jbx_menu/gfx/menu/hover.gif');
          imagedestroy($img);
        }
      }
	  Menu::createCssCache();
      $this->_upload('menu');
      $this->_upload('hover');
      Tools::redirectAdmin($currentIndex.'&conf=4&token='.$this->token);
    }
    else if (Tools::isSubmit('submitItem')) {
      // For Edit;
      $id_menu = Tools::isSubmit('edit') ? intVal(Tools::getValue('id_menu')) : null;

      $title = Tools::getValue('title');
      $id_parent = intval(Tools::getValue('id_parent'));
      $id_category = intval(Tools::getValue('id_category'));
      $id_product = intval(Tools::getValue('id_product'));
      $id_cms = intval(Tools::getValue('id_cms'));
      $id_manufacturer = intval(Tools::getValue('id_manufacturer'));
      $id_supplier = intval(Tools::getValue('id_supplier'));
      $type = trim(Tools::getValue('type'));
      $level = intval(Tools::getValue('category_level'));
      $ignore = Tools::getValue('category_ignore');
      $link = Tools::getValue('link');
      $logged = Tools::getValue('logged', 0);
      $new_window = Tools::getValue('new_window', 0);
      $css = Tools::getValue('css', '');

      $item = new Menu($id_menu);
      // Spec. Position with parent ID
      if ($item->id_parent != $id_parent) {
        $reorder = array($item->id_parent, $id_parent);
      }
      $item->id_parent = $id_parent;
      $item->title = $title;
      $item->css = $css;
      //Tools::d($_POST);exit;
      $item->new_window = $new_window;
      $item->logged = $logged;

      switch ($type) {
        case 'category':
          $fieldsRequired = array('id_category'=>'isUnsignedInt');
          if ($this->_fieldsValidate($fieldsRequired)) {
            $item->id_item = $id_category;
            $item->type = 'category'; // $this->l('Category');
            $item->level = $level;
            $item->ignore = $ignore;
            $item->save();
            $id = $item->id;
          }
          else {
            $this->_errors[] = $this->l('You must enter the required fields');
          }
          break;
        case 'product':
          $fieldsRequired = array('id_product'=>'isUnsignedInt');
          if ($this->_fieldsValidate($fieldsRequired)) {
            $item->id_item = $id_product;
            $item->type = 'product'; // $this->l('Product');
            $item->save();
            $id = $item->id;
          }
          else {
            $this->_errors[] = $this->l('You must enter the required fields');
          }
          break;
        case 'cms':
          $fieldsRequired = array('id_cms'=>'isUnsignedInt');
          if ($this->_fieldsValidate($fieldsRequired)) {
            $item->id_item = $id_cms;
            $item->type = 'cms'; // $this->l('Cms');
            $item->save();
            $id = $item->id;
          }
          else {
            $this->_errors[] = $this->l('You must enter the required fields');
          }
          break;
        case 'manufacturers':
          $fieldsRequired = array('title'=>'isGenericName');
          if ($this->_fieldsValidate($fieldsRequired)) {
            $item->type = 'manufacturers'; // $this->l('Manufacturers');
            $item->save();
            $id = $item->id;
          }
          else {
            $this->_errors[] = $this->l('You must enter the required fields');
          }
          break;
        case 'manufacturer':
          $fieldsRequired = array('id_manufacturer'=>'isUnsignedInt');
          if ($this->_fieldsValidate($fieldsRequired)) {
            $item->id_item = $id_manufacturer;
            $item->type = 'manufacturer'; // $this->l('Manufacturer');
            $item->save();
            $id = $item->id;
          }
          else {
            $this->_errors[] = $this->l('You must enter the required fields');
          }
          break;
        case 'suppliers':
          $fieldsRequired = array('title'=>'isGenericName');
          if ($this->_fieldsValidate($fieldsRequired)) {
            $item->type = 'suppliers'; // $this->l('Suppliers');
            $item->save();
            $id = $item->id;
          }
          else {
            $this->_errors[] = $this->l('You must enter the required fields');
          }
          break;
        case 'supplier':
          $fieldsRequired = array('id_supplier'=>'isUnsignedInt');
          if ($this->_fieldsValidate($fieldsRequired)) {
            $item->id_item = $id_supplier;
            $item->type = 'supplier'; // $this->l('Supplier');
            $item->save();
            $id = $item->id;
          }
          else {
            $this->_errors[] = $this->l('You must enter the required fields');
          }
          break;
        case 'link':
          $fieldsRequired = array('title'=>'isGenericName', 'link'=>'isCleanHtml');
          if ($this->_fieldsValidate($fieldsRequired)) {
            $item->type = 'link'; // $this->l('Link');
            $item->link = $link;
            $item->save();
            $id = $item->id;
          }
          else {
            $this->_errors[] = $this->l('You must enter the required fields');
          }
          break;
      }
      if (isset($id)) {
        if (isset($reorder) && is_array($reorder) && !is_null($id_menu)) {
          foreach ($reorder as $id_parent) {
            Menu::remakePosition(intval($id_parent));
          }
        }
        if (($filename = $this->_getFilename($id))) {
          //@unlink($filename);
        }
        $this->_uploadIcon($id);
        Tools::redirectAdmin($currentIndex . '&conf=' . (is_null($id_menu) ? 3 : 4) . '&token=' . $this->token);
      }
    }
    else if (Tools::isSubmit('up')) {
      if (Menu::setUpPosition($id_menu)) {
        $this->_displayConfirmation($this->l('ok'));
      }
      else {
        $this->_errors[] = $this->l('Unable to up this item.');
      }
    }
    else if (Tools::isSubmit('down')) {
      if (Menu::setDownPosition($id_menu)) {
        $this->_displayConfirmation($this->l('ok'));
      }
      else {
        $this->_errors[] = $this->l('Unable to down this item.');
      }
    }
    else if (Tools::isSubmit('delete')) {
      $menu = new Menu($id_menu);
      $menu->delete();
      Tools::redirectAdmin($currentIndex . '&conf=1&token=' . $this->token);
    }
    else if (Tools::isSubmit('deleteIcon')) {
      $filename = $this->_getFilename($id_menu);
      $success = false;
      if ($filename) {
        if (@unlink($filename)) {
          $success = true;
        }
      }
      
      if ($success) {
        Tools::redirectAdmin($currentIndex . '&conf=1&token=' . $this->token);
      }
      else {
        $this->_errors[] = $this->l('Unable to delete this icon.');
      }
    }
  }

    private function _displayConfirmation($string) {
        $this->_html .= '<div class="conf confirm"><img src="../img/admin/ok.gif" /> ' . $string . '</div>';
    }

  private function _fieldsValidate($fields) {
    foreach ($fields as $field => $values) {
      if (!is_array($values)) {
        $values = array($field=>$values);
      }
      foreach ($values as $field => $method) {
        $_values = Tools::getValue($field, $method == 'isUnsignedInt' ? 0 : '');
        if (!is_array($_values)) {
          $_values = array($_values);
        }
        foreach ($_values as $value) {
          $return = call_user_func(array('Validate', $method), $value);
          if (!$return || ($method == 'isUnsignedInt' && intval(Tools::getValue($field, 0)) === 0)) {
            return false;
          }
        }
      }
    }
    return true;
  }


  private function _upload($fieldname) {
    $allowedTypes = array(1); // Gif
    $dirname = $this->_modulePath . 'gfx/menu/';

    if (!isset($_FILES[$fieldname])) {
      return;
    }

    $name = $_FILES[$fieldname]['name'];
    $type = $_FILES[$fieldname]['type'];
    $size = $_FILES[$fieldname]['size'];
    $erro = $_FILES[$fieldname]['error'];
    $tmpn = $_FILES[$fieldname]['tmp_name'];
    if ($erro == 4) {
      return;
    }
    list($width, $height, $type_num, $attr) = getimagesize($tmpn);

    if (file_exists($dirname) && is_dir($dirname) && is_writable($dirname)) {
      if(!in_array($type_num, $allowedTypes)) {
        $this->_errors[] = $this->l('Bad type of your icon.');
        return;
      }
      $ext = '.gif';
      return move_uploaded_file($tmpn, $dirname . $fieldname . $ext) && 
      copy($dirname . $fieldname . $ext, $dirname . $fieldname . '_orig' . $ext);
    }
  }

  private function _uploadIcon($id) {
    $allowedTypes = array(1, 2, 3);
    $dirname = $this->_modulePath . 'gfx/icons/';
    if (file_exists($dirname) && is_dir($dirname) && is_writable($dirname)) {
      $fieldname = 'icon';
      if (!isset($_FILES[$fieldname])) {
        return;
      }
      $name = $_FILES[$fieldname]['name'];
      $type = $_FILES[$fieldname]['type'];
      $size = $_FILES[$fieldname]['size'];
      $erro = $_FILES[$fieldname]['error'];
      $tmpn = $_FILES[$fieldname]['tmp_name'];
      list($width, $height, $type_num, $attr) = getimagesize($tmpn);

      if(!in_array($type_num, $allowedTypes)) {
        $this->_errors[] = $this->l('Bad type of your icon.');
        return;
      }

      $exts = array(1 => '.gif', '.jpg', '.png');
      $ext = $exts[$type_num];

      return move_uploaded_file($tmpn, $dirname . $id . $ext);
    }
    else {
      $this->_errors[] = $this->l('Unable to write in') . ' <b>' . $dirname . '</b>.';
    }
  }

  private function _displayHeades() {
    global $currentIndex;

    $this->_html .= '
    <link rel="stylesheet" media="screen" type="text/css" href="' . _MODULE_DIR_ . $this->_module . '/css/tab.css" />
    <link rel="stylesheet" media="screen" type="text/css" href="' . _MODULE_DIR_ . $this->_module . '/css/colorpicker.css" />
    <script type="text/javascript" src="' . _MODULE_DIR_ . $this->_module . '/js/colorpicker.js"></script>
    <script type="text/javascript" src="' . _MODULE_DIR_ . $this->_module . '/js/tab.js"></script>';
    $this->_html .= "
    <script type=\"text/javascript\">
    var txt_select_list = '" . $this->l('You must select an item from the list !', __CLASS__, true, false) . "';
    var txt_delete = '" . $this->l('Are you sure you want to remove this menu and its submenus ?', __CLASS__, true, false) . "';
    var base_dir = '" . $currentIndex . "&token=" . $this->token . "';
    $(document).ready(function(){
      addPicker('colorText', '" . Configuration::get('MENU_TEXT_COLOR') . "', 0);
      addPicker('colorTextOver', '" . Configuration::get('MENU_TEXT_OVER_COLOR') . "', 0);
      addPicker('colorMenu', '" . Configuration::get('MENU_MENU_COLOR') . "', 1);
      addPicker('colorItem', '" . Configuration::get('MENU_ITEM_COLOR') . "', 0);
      addPicker('colorItemHover', '" . Configuration::get('MENU_ITEM_HOVER_COLOR') . "', 0);
    });
    </script>";
  }


  private function _displayItemsList() {
    global $cookie;

    $this->_html .= '<h2>' . $this->l('List of items') . '</h2>';
    $items = Menu::getItems($cookie->id_lang);
    if (count($items)) {
      $this->_html .= '
      <fieldset>
        <legend>
          <img src="' . _MODULE_DIR_ . $this->_module . '/logo.gif" /> ' . $this->l('Menu') . '
        </legend>
        <div style="float:left;width:400px;">
          <select id="items" size="10" style="width:100%;height:285px;">';
          $this->_html .= $this->_showOption($items, $cookie->id_lang, 0, Tools::getValue('id_menu', 0));
          $this->_html .= '
          </select>
        </div>
        <div style="float:left;margin-left:10px;">
          <img src="' . _PS_ADMIN_IMG_ . '/up.gif" alt="up" class="pointer action" style="margin-top:10px;" /><br />
          <img src="' . _PS_ADMIN_IMG_ . '/edit.gif" alt="edit" class="pointer action" style="margin-top:100px;" /><br />
          <img src="' . _PS_ADMIN_IMG_ . '/delete.gif" alt="delete" class="pointer action" style="margin-top:10px;" /><br />
          <img src="' . _PS_ADMIN_IMG_ . '/down.gif" alt="down" class="pointer action" style="margin-top:105px;" /><br />
        </div>
      </fieldset><br />';
    }
    else {
      $this->_html .= '<p class="bold center">
        ' . $this->l('Your menu is fully empty !!!'). '
      </p>';
    }
  }

  private function _showOption($items, $id_lang, $level = 0, $itemSelected = 0, $ignoreItems = array()) {
    foreach ($items as $item) {
      $value = $item['id_menu'];
      if (in_array($item['id_menu'], $ignoreItems)) {
        continue;
      }
      $this->_html .= '<option value="' . $value . '" ' . 
                      (intVal($itemSelected) == $item['id_menu'] ? 'selected=""' : '') . 
                      ' style="padding-left: ' . ($level*15) . 'px;">' . 
                      Menu::getTitle($item['id_menu'], $id_lang) . ' (' . $this->l(ucFirst($item['type'])) . ')' .
                      '</option>' . PHP_EOL;
      if (isset($item['childrens']) && count($item['childrens'])) {
        $this->_showOption($item['childrens'], $id_lang, $level+1, $itemSelected, $ignoreItems);
      }
    }
  }

  private function _displayItemAdd() {
    global $cookie, $currentIndex;

    $id_menu = Tools::isSubmit('edit') ? intVal(Tools::getValue('id_menu')) : 0;
    $menu = new Menu($id_menu);

    $id_lang = $cookie->id_lang;
    $defaultLanguage = intval(Configuration::get('PS_LANG_DEFAULT'));
    $iso = Language::getIsoById(intval($cookie->id_lang));
    $languages = Language::getLanguages();
    $divLangName = 'title¤link';

    $this->_html .= '
    <script type="text/javascript">id_language = Number(' . $defaultLanguage . ');</script>
    <form action="" method="post" enctype="multipart/form-data">
      <fieldset>
        <legend>
          <img src="' . _MODULE_DIR_ . $this->_module . '/gfx/tab/add.gif" alt="" title="" /> ' . $this->l('Add item') . '
        </legend>
        <label for="type">' . $this->l('Type:') . '</label>
        <div class="margin-form">
          <select name="type" onchange="$(\'.case\').addClass(\'hide\');$(\'.case_\'+$(this).val()).removeClass(\'hide\');">
            <option value="">-- ' . $this->l('Select an item type') . ' --</option>
            <option value="category" ' . ($menu->type == 'category' && (Tools::isSubmit('edit')) ? 'selected=""' : '') . '>' . $this->l('Categories') . '</option>
            <option value="product"' . ($menu->type == 'product' && (Tools::isSubmit('edit')) ? 'selected=""' : '') . '>' . $this->l('Products') . '</option>
            <option value="cms"' . ($menu->type == 'cms' && (Tools::isSubmit('edit')) ? 'selected=""' : '') . '>' . $this->l('CMS') . '</option>
            <option value="manufacturers"' . ($menu->type == 'manufacturers' && (Tools::isSubmit('edit')) ? 'selected=""' : '') . '>' . $this->l('Manufacturers List') . '</option>
            <option value="manufacturer"' . ($menu->type == 'manufacturer' && (Tools::isSubmit('edit')) ? 'selected=""' : '') . '>' . $this->l('Manufacturer') . '</option>
            <option value="suppliers"' . ($menu->type == 'suppliers' && (Tools::isSubmit('edit')) ? 'selected=""' : '') . '>' . $this->l('Suppliers List') . '</option>
            <option value="supplier"' . ($menu->type == 'supplier' && (Tools::isSubmit('edit')) ? 'selected=""' : '') . '>' . $this->l('Supplier') . '</option>
            <option value="link"' . ($menu->type == 'link' && (Tools::isSubmit('edit')) ? 'selected=""' : '') . '>' . $this->l('Links') . '</option>
          </select><sup> *</sup>
          <!-- <p class="clear">...</p> -->
        </div>
        <label for="type">' . $this->l('Parent Item:') . '</label>
        <div class="margin-form">
          <select name="id_parent">
            <option value="0">-- ' . $this->l('Choose a parent item') . ' --</option>';
            $items = Menu::getItems($id_lang);
            $this->_html .= $this->_showOption($items, $id_lang, 0, $menu->id_parent, array($menu->id));
          $this->_html .= '
          </select>
        </div>

        <!-- 2010-02-22 12:40:43 -->
        <label for="css">' . $this->l('CSS ID:') . '</label>
        <div class="margin-form">
          <input type="text" name="css" id="css" value="' . (!is_null($menu->id) ? $menu->css : '') . '" />
          <p class="clear">' . $this->l('Use this option to set a CSS ID to this item.') . '</p>
        </div>
        <!-- /2010-02-22 12:40:43 -->

        <!-- INT -->
        <label for="title">' . $this->l('Title:') . '</label>
        <div class="margin-form">';

      		foreach ($languages as $language) {
      			$this->_html .= '
            <div id="title_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').'; float: left;">
              <input type="text" name="title['.$language['id_lang'].']" value="' . (!is_null($menu->id) ? $menu->title[$language['id_lang']] : '') . 
              '" class="'.($language['id_lang'] != $defaultLanguage ? 'clone' : 'cloneParent').'" /><sup class="case case_link hide"> *</sup>
            </div>';
      		}

      		ob_start();
    		  $this->displayFlags($languages, $defaultLanguage, $divLangName, 'title');
    		  $this->_html .= ob_get_contents();
    		  ob_end_clean();

          $this->_html .= '
          <p class="clear">' . $this->l('If you leave the field blank, the default title will be used.') . '</p>
        </div>
        <!-- INT -->

        <!-- 2009-12-14 09:56:32 -->
        <label for="logged">' . $this->l('Must be logged:') . '</label>
        <div class="margin-form">
          <input type="checkbox" id="logged" name="logged" value="1"' . (!is_null($menu->id) && $menu->logged ? ' checked=""' : '') . '/>
          <p class="clear">' . $this->l('User must be logged.') . '</p>
        </div>
        <!-- /2009-12-14 09:56:32 -->

        <div class="case_category case ' . ($menu->type == 'category' && (Tools::isSubmit('edit')) ? '' : 'hide'). '">
          <label for="category_level">' . $this->l('Level:') . '</label>
          <div class="margin-form">
            <input type="text" name="category_level" id="category_level" value="' . (!is_null($menu->id) ? $menu->level : '0') .'" size="5" />
            <p class="clear">' . $this->l('Maximum level of unfolding, leave 0 for all.') . '</p>
          </div>
          <label for="id_category">' . $this->l('Category:') . '</label>
          <div class="margin-form">
            <select name="id_category" id="id_category" size="10">';
            $this->_getCategoryOption(1, $cookie->id_lang, true, (!is_null($menu->id)) ? $menu->id_item : null);
            $this->_html .= '
            </select><sup> *</sup>
            <p class="clear">' . $this->l('Start category') . '</p>
          </div>
          <label for="category_ignore">' . $this->l('Categories to ignore:') . '</label>
          <div class="margin-form">
            <input type="text" name="category_ignore" id="category_ignore" value="' . (!is_null($menu->id) ? $menu->ignore : '') .'" />
            <p class="clear">' . $this->l('Separate with comma. (id1,id2,...)') . '</p>
          </div>
        </div>
        <div class="case_product case ' . ($menu->type == 'product' && (Tools::isSubmit('edit')) ? '' : 'hide'). '">
          <label for="id_product">' . $this->l('Product ID:') . '</label>
          <div class="margin-form">
            <input type="text" name="id_product" id="id_product" size="6" />
            <!--
            <select name="id_product" id="id_product">';
            $this->_html .= '
            </select><sup> *</sup>
            -->
          </div>
        </div>
        <div class="case_cms case ' . ($menu->type == 'cms' && (Tools::isSubmit('edit')) ? '' : 'hide'). '">
          <label for="id_cms">' . $this->l('CMS Page:') . '</label>
          <div class="margin-form">
            <select name="id_cms" id="id_cms">';
            $_cms = CMS::listCms($cookie->id_lang);
            foreach($_cms as $cms)
              $this->_html .= '<option value="' . $cms['id_cms'] . '" 
              ' . ((!is_null($menu->id) && $menu->id_item == $cms['id_cms']) ? 'selected=""' : '') . '
              >' . 
              $cms['meta_title'] . '</option>';
            $this->_html .= '
            </select><sup> *</sup>
          </div>
        </div>
        <div class="case_manufacturer case ' . ($menu->type == 'manufacturer' && (Tools::isSubmit('edit')) ? '' : 'hide'). '">
          <label for="manufacturer_id">' . $this->l('Manufacturer:') . '</label>
          <div class="margin-form">
            <select name="id_manufacturer" id="id_manufacturer">';
            $manufacturers = Manufacturer::getManufacturers(false, $cookie->id_lang);
            foreach($manufacturers as $manufacturer)
              $this->_html .= '<option value="' . $manufacturer['id_manufacturer'] . '" 
              ' . ((!is_null($menu->id) && $menu->id_item == $manufacturer['id_manufacturer']) ? 'selected=""' : '') . '
              >' . $manufacturer['name'] . '</option>';
            $this->_html .= '
            </select>
          </div>
        </div>
        <div class="case_supplier case ' . ($menu->type == 'supplier' && (Tools::isSubmit('edit')) ? '' : 'hide'). '">
          <label for="id_supplier">' . $this->l('Supplier:') . '</label>
          <div class="margin-form">
            <select name="id_supplier" id="id_supplier">';
            $suppliers = Supplier::getSuppliers(false, $cookie->id_lang);
            foreach($suppliers as $supplier)
              $this->_html .= '<option value="' . $supplier['id_supplier'] . '" 
              ' . ((!is_null($menu->id) && $menu->id_item == $supplier['id_supplier']) ? 'selected=""' : '') . '
              >' . $supplier['name'] . '</option>';
            $this->_html .= '
            </select><sup> *</sup>
          </div>
        </div>
        <div class="case_link case ' . ($menu->type == 'link' && (Tools::isSubmit('edit')) ? '' : 'hide'). '">

          <label for="link">' . $this->l('URL:') . '</label>
          <div class="margin-form">';

      		foreach ($languages as $language) {
      			$this->_html .= '
            <div id="link_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').'; float: left;">
              <input type="text" name="link['.$language['id_lang'].']" value="' . (!is_null($menu->id) ? $menu->link[$language['id_lang']] : '') . 
              '" class="'.($language['id_lang'] != $defaultLanguage ? 'clone' : 'cloneParent').'" /><sup> *</sup>
            </div>';
      		}

      		ob_start();
    		  $this->displayFlags($languages, $defaultLanguage, $divLangName, 'link');
    		  $this->_html .= ob_get_contents();
    		  ob_end_clean();

          $this->_html .= '
            <p class="clear">&nbsp;</p>
          </div>
        </div>
        <!-- v5.6.5 -->
        <label for="new_window">' . $this->l('New window:') . '</label>
        <div class="margin-form">
          <input type="checkbox" id="new_window" name="new_window" value="1"' . (!is_null($menu->id) && $menu->new_window ? ' checked=""' : '') . '/>
          <p class="clear">' . $this->l('On click, open link in new window.') . '</p>
        </div>

        <label for="icon">' . $this->l('Icon:') . '</label>
        <div class="margin-form">
          <input type="file" name="icon" id="icon" /><br />';
        $filename = $this->_getFilename($menu->id);
        if ($filename) {
          $this->_html .= '
          <div id="image" style="float: none; width: 100px;">
            <img src="' . $filename . '?refresh=' . rand(1, 10) . '" /> 
            <a href="' . $_SERVER['REQUEST_URI'] . '&deleteIcon"><img src="' . _PS_ADMIN_IMG_ . '/delete.gif" alt="" /></a>
          </div>';
        }
        $this->_html .= '
        </div>

        <div class="clear center">
          <input type="submit" name="submitItem" value="' . $this->l(' Save item ') . '" class="button" />
        </div>
      </fieldset>
    </form>';
  }

  private function _getFilename($id) {
    if (is_null($id)) {
      return false;
    }
    $base = '../modules/jbx_menu/gfx/icons/';
    $exts = array('.gif', '.jpg', '.png');
    foreach ($exts as $ext) {
      $filename = $base . $id . $ext;
      if (file_exists($filename)) {
        return $filename;
      }
    }
    return false;
  }

  private function _displayOptionsForm() {
    if (Menu::haveGd()) {
      $base = _MODULE_DIR_ . $this->_module . '/colorize.php?preview&color=';
      $bgMenu =  $base . Configuration::get('MENU_MENU_COLOR');
      $bgMenuAlt = $base . '#COLOR#' . '&light=#LIGHT#';
    }
    else {
      $bgMenu = _MODULE_DIR_ . $this->_module . '/gfx/menu/menu.gif';
      $bgMenuAlt = $bgMenu;
    }

    $this->_html .= '<br />
    <form action="" method="post" enctype="multipart/form-data">
      <fieldset>
        <legend>
          <img src="' . _MODULE_DIR_ . $this->_module . '/gfx/tab/settings.gif" alt="" title="" /> ' . $this->l('Settings') . '
        </legend>
        <div style="margin-left:50px;overflow-x:hidden;width:845px;">
          <label class="clean bold">' . $this->l('Menu Background Color Preview') . '</label>
          <p class="center">';
          $this->_html .= '
            <img src="' . $bgMenu . '" 
                 alt="' . $bgMenuAlt . '" 
                 title="' . $this->l('Menu Background Color Preview') . '" 
                 id="menuBackgroundImage" width="100%" height="30" style="border-radius:7px;-moz-border-radius:7px;-webkit-border-radius:7px;" />';
          $this->_html .= '
          </p>
        </div>
        <div class="firstColumn left">';
		// $this->l('Yes'); $this->l('No');
        // $this->l('Show lang ?'); $this->l('Show lang menu into your menu.');
        //$this->_html .= $this->_displayInputRadio('Show lang ?', 'lang', Configuration::get('MENU_LANG'), 'Show lang menu into your menu.');
        // $this->l('Show search bar ?'); $this->l('Show search field into your menu.');
        $this->_html .= $this->_displayInputRadio('Show search bar ?', 'search', Configuration::get('MENU_SEARCH'), 'Show search field into your menu.');
        // $this->l('Show search button ?'); $this->l('Show button to run search request.');
        $this->_html .= $this->_displayInputRadio('Show search button ?', 'button', Configuration::get('MENU_BUTTON'), 'Show button to run search request.');
        // $this->l('Use auto-completion ?'); $this->l('Use auto-completion with search bar.');
        $this->_html .= $this->_displayInputRadio('Use auto-completion ?', 'completion', Configuration::get('MENU_COMPLETION'), 'Use auto-completion with search bar.');
        // $this->l('Show icons ?'); $this->l('Show image with the items of menu.');
        $this->_html .= $this->_displayInputRadio('Show icons ?', 'icons', Configuration::get('MENU_ICONS'), 'Show image with the items of menu.');
        // $this->l('Hook to Use ?'); $this->l('Choose the best method to attach your menu to your theme.');
        $this->_html .= $this->_displayInputRadio('Hook to Use ?', 'hook', Configuration::get('MENU_HOOK'), 'Choose the best method to attach your menu to your theme.', array('Top'=>'top', 'Menu'=>'menu'));
        // $this->l('Use cache ?'); $this->l('Use cache for best performances.');
        $this->_html .= $this->_displayInputRadio('Use cache ?', 'cache_enable', Configuration::get('MENU_CACHE_ENABLE'), 'Use cache for best performances.');
		if ($this->_isAdmin()) {
        // $this->l('Allow options ?'); $this->l('Admin only. Use this for disable or enable options.');
        	$this->_html .= $this->_displayInputRadio('Allow options ?', 'allow_options', Configuration::get('MENU_ALLOW_OPTIONS'), 'Admin only. Use this for disable or enable options.');
		}
        $this->_html .= '
        </div>
        <div class="lastColumn left">

          <p class="clear">
            <div class="columnTdLeft columnColorSelector">
              <label class="clean bold" for="colorText">
                ' . $this->l('Text') . ' 
                <img src="' . _MODULE_DIR_ . $this->_module . '/gfx/tab/help.png" 
                     alt="" 
                     title="' . $this->l('Change color to the text of items of your menu.') . '" 
                     class="help middle" />
              </label> 
            </div>
            <div class="columnTdCenter">
              <div id="colorTextContener" class="colorSelector"><div style="background-color: #' . Configuration::get('MENU_TEXT_COLOR') . ';"></div></div>
              <input type="hidden" name="text_color" id="colorText" value="' . Configuration::get('MENU_TEXT_COLOR') . '" />
            </div>
            <div class="columnTdRight columnColorSelector default">
              <img src="' . _MODULE_DIR_ . $this->_module . '/gfx/tab/text_bold.png" alt="' . $this->l('B') . '" title="' . $this->l('B') . '" class="middle" />&nbsp;
              <input type="checkbox" name="text_bold" id="text_bold" value="1" class="pointer middle" ' . (Configuration::get('MENU_TEXT_BOLD') ? 'checked=""' : '') . ' />&nbsp;
              <img src="' . _MODULE_DIR_ . $this->_module . '/gfx/tab/text_italic.png" alt="' . $this->l('I') . '" title="' . $this->l('I') . '" class="middle" />&nbsp;
              <input type="checkbox" name="text_italic" id="text_italic" value="1" class="pointer middle" ' . (Configuration::get('MENU_TEXT_ITALIC') ? 'checked=""' : '') . ' />&nbsp
              <img src="' . _MODULE_DIR_ . $this->_module . '/gfx/tab/text_underline.png" alt="' . $this->l('U') . '" title="' . $this->l('U') . '" class="middle" />&nbsp;
              <input type="checkbox" name="text_underline" id="text_underline" value="1" class="pointer middle" ' . (Configuration::get('MENU_TEXT_UNDERLINE') ? 'checked=""' : '') . ' />&nbsp;
            </div>
          </p>

          <!-- 12/12/2009 11:35:44 -->
          <p class="clear">
            <div class="columnTdLeft">
              <label class="clean bold" for="text_size">
                ' . $this->l('Size of text tabs') . ' 
                <img src="' . _MODULE_DIR_ . $this->_module . '/gfx/tab/help.png" 
                     alt="" 
                     title="' . $this->l('Set a size of text of tabs.') . '" 
                     class="help middle" />
              </label> 
            </div>
            <div class="columnTdRight">
              <input type="text" name="text_size" id="text_size" value="' . Configuration::get('MENU_TEXT_SIZE') . '" size="6" class="right" /> 
              <i>(' . $this->l('Default value:') . ' 12)</i>
            </div>
          </p>
          <p class="clear">
            <div class="columnTdLeft">
              <label class="clean bold" for="text_vertical">
                ' . $this->l('Vertical alignment of tabs') . ' 
                <img src="' . _MODULE_DIR_ . $this->_module . '/gfx/tab/help.png" 
                     alt="" 
                     title="' . $this->l('Set a vertical alignment of tabs.') . '" 
                     class="help middle" />
              </label> 
            </div>
            <div class="columnTdRight">
              <input type="text" name="text_vertical" id="text_vertical" value="' . Configuration::get('MENU_TEXT_VERTICAL') . '" size="6" class="right" /> 
               <i>(' . $this->l('Default value:') . ' 7)</i>
            </div>
          </p>
          <!-- / 12/12/2009 11:35:44 -->

          <p class="clear">
            <div class="columnTdLeft columnColorSelector">
              <label class="clean bold" for="colorTextOver">
                ' . $this->l('Text over') . ' 
                <img src="' . _MODULE_DIR_ . $this->_module . '/gfx/tab/help.png" 
                     alt="" 
                     title="' . $this->l('Change color to the text over of items of your menu.') . '" 
                     class="help middle" />
              </label> 
            </div>
            <div class="columnTdCenter">
              <div id="colorTextOverContener" class="colorSelector"><div style="background-color: #' . Configuration::get('MENU_TEXT_OVER_COLOR') . ';"></div></div>
              <input type="hidden" name="text_over_color" id="colorTextOver" value="' . Configuration::get('MENU_TEXT_OVER_COLOR') . '" />
            </div>
            <div class="columnTdRight columnColorSelector default">
              <img src="' . _MODULE_DIR_ . $this->_module . '/gfx/tab/text_bold.png" alt="' . $this->l('B') . '" title="' . $this->l('B') . '" class="middle" />&nbsp;
              <input type="checkbox" name="text_over_bold" id="text_over_bold" value="1" class="pointer middle" ' . (Configuration::get('MENU_TEXT_OVER_BOLD') ? 'checked=""' : '') . ' />&nbsp;
              <img src="' . _MODULE_DIR_ . $this->_module . '/gfx/tab/text_italic.png" alt="' . $this->l('I') . '" title="' . $this->l('I') . '" class="middle" />&nbsp;
              <input type="checkbox" name="text_over_italic" id="text_over_italic" value="1" class="pointer middle" ' . (Configuration::get('MENU_TEXT_OVER_ITALIC') ? 'checked=""' : '') . ' />&nbsp;
              <img src="' . _MODULE_DIR_ . $this->_module . '/gfx/tab/text_underline.png" alt="' . $this->l('U') . '" title="' . $this->l('U') . '" class="middle" />&nbsp;
              <input type="checkbox" name="text_over_underline" id="text_over_underline" value="1" class="pointer middle" ' . (Configuration::get('MENU_TEXT_OVER_UNDERLINE') ? 'checked=""' : '') . ' />&nbsp;
            </div>
          </p>';

          if (Menu::haveGd()) {
            $this->_html .= '
            <p class="clear">
              <div class="columnTdLeft columnColorSelector">
                <label class="clean bold" for="colorMenu">
                  ' . $this->l('Menu color') . ' 
                  <img src="' . _MODULE_DIR_ . $this->_module . '/gfx/tab/help.png" 
                       alt="" 
                       title="' . $this->l('Apply color to the background image to your menu.') . '" 
                       class="help middle" />
                </label> 
              </div>
              <div class="columnTdCenter">
                <div id="colorMenuContener" class="colorSelector"><div style="background-color: #' . Configuration::get('MENU_MENU_COLOR') . ';"></div></div>
                <input type="hidden" name="menu_color" id="colorMenu" value="' . Configuration::get('MENU_MENU_COLOR') . '" />
              </div>
            </p>';
            $this->_html .= '
            <p class="clear">
              <div class="columnTdLeft">
                <label class="clean bold" for="lightMenu">
                  ' . $this->l('Menu light') . '
                  <img src="' . _MODULE_DIR_ . $this->_module . '/gfx/tab/help.png"
                       alt=""
                       title="' . $this->l('Apply light to the background image to your menu.') . '"
                       class="help middle" />
                </label>
              </div>
              <div class="columnTdCenter">
                <select name="menu_light" id="lightMenu" onChange="changeLight()">';
                    for($percent = 0; $percent <= 200; $percent = $percent + 10) {
                        $this->_html .= '<option value="' . $percent. '"' . ((int)Configuration::get('MENU_MENU_LIGHT') == $percent ? ' selected=""' : ''). '>' . $percent . '</option>';
                    }
                    $this->_html .= '
                </select>
              </div>
            </p>';
          }

          $this->_html .= '
          <!-- 2009-12-14 09:12:33 -->
          <p class="clear">
            <div class="columnTdLeft columnColorSelector">
              <label class="clean bold" for="colorItem">
                ' . $this->l('Items color') . ' 
                <img src="' . _MODULE_DIR_ . $this->_module . '/gfx/tab/help.png" 
                     alt="" 
                     title="' . $this->l('Apply color to the background to items menu.') . '" 
                     class="help middle" />
              </label> 
            </div>
            <div class="columnTdCenter">
              <div id="colorItemContener" class="colorSelector"><div style="background-color: #' . Configuration::get('MENU_ITEM_COLOR') . ';"></div></div>
              <input type="hidden" name="item_color" id="colorItem" value="' . Configuration::get('MENU_ITEM_COLOR') . '" />
            </div>
          </p>
          <p class="clear">
            <div class="columnTdLeft columnColorSelector">
              <label class="clean bold" for="colorItemHover">
                ' . $this->l('Items Hover color') . ' 
                <img src="' . _MODULE_DIR_ . $this->_module . '/gfx/tab/help.png" 
                     alt="" 
                     title="' . $this->l('Apply color to the background to items (hover) menu.') . '" 
                     class="help middle" />
              </label> 
            </div>
            <div class="columnTdCenter">
              <div id="colorItemHoverContener" class="colorSelector"><div style="background-color: #' . Configuration::get('MENU_ITEM_HOVER_COLOR') . ';"></div></div>
              <input type="hidden" name="item_hover_color" id="colorItemHover" value="' . Configuration::get('MENU_ITEM_HOVER_COLOR') . '" />
            </div>
          </p>
          <!-- /2009-12-14 09:12:33 -->

          <!-- 2009-12-23 00:19:38 -->
          <p class="clear">
            <div class="columnTdLeft">
              <label class="clean bold" for="item_size">
                ' . $this->l('Items Width') . ' 
                <img src="' . _MODULE_DIR_ . $this->_module . '/gfx/tab/help.png" 
                     alt="" 
                     title="' . $this->l('Set a width to items of your menu.') . '" 
                     class="help middle" />
              </label> 
            </div>
            <div class="columnTdRight">
              <input type="text" name="item_size" id="item_size" value="' . Configuration::get('MENU_ITEM_SIZE') . '" size="6" class="right" /> 
              <i>(' . $this->l('Default value:') . ' 13)</i>
            </div>
          </p>
          <!-- /2009-12-23 00:19:38 -->

          <p class="clear">
            <div class="columnTdLeft">
              <label class="clean bold" for="index">
                ' . $this->l('Index Layer') . ' 
                <img src="' . _MODULE_DIR_ . $this->_module . '/gfx/tab/help.png" 
                     alt="" 
                     title="' . $this->l('Set a layer position of your menu.') . '" 
                     class="help middle" />
              </label> 
            </div>
            <div class="columnTdRight">
              <input type="text" name="index" id="index" value="' . Configuration::get('MENU_INDEX') . '" size="6" class="right" /> 
              <i>(' . $this->l('Default value:') . ' 1000)</i>
            </div>
          </p>
          <!--
          <p class="clear">
            <div class="columnTdLeft">
              <label class="clean bold" for="level">
                ' . $this->l('Max Level') . ' 
                <img src="' . _MODULE_DIR_ . $this->_module . '/gfx/tab/help.png" 
                     alt="" 
                     title="' . $this->l('Level maximum of your menu.') . '" 
                     class="help middle" />
              </label> 
            </div>
            <div class="columnTdRight">
              <input type="text" name="level" id="level" value="' . Configuration::get('MENU_LEVEL') . '" size="6" class="right" /> 
              <i>(' . $this->l('Default value:') . ' 0)</i>
            </div>
          </p>
          -->
          <p class="clear">
            <div class="columnTdLeft">
              <label class="clean bold" for="width">
                ' . $this->l('Width') . ' 
                <img src="' . _MODULE_DIR_ . $this->_module . '/gfx/tab/help.png" 
                     alt="" 
                     title="' . $this->l('Set width of your menu.') . '" 
                     class="help middle" />
              </label> 
            </div>
            <div class="columnTdRight">
              <input type="text" name="width" id="width" value="' . Configuration::get('MENU_WIDTH') . '" size="6" class="right" /> 
              <i>(' . $this->l('Default value:') . ' 960)</i>
            </div>
          </p>

          <!-- 12/12/2009 12:05:44 $2.1 -->
          <p class="clear">
            <div class="columnTdLeft">
              <label class="clean bold" for="height">
                ' . $this->l('Height') . ' 
                <img src="' . _MODULE_DIR_ . $this->_module . '/gfx/tab/help.png" 
                     alt="" 
                     title="' . $this->l('Set height of your menu.') . '" 
                     class="help middle" />
              </label> 
            </div>
            <div class="columnTdRight">
              <input type="text" name="height" id="height" value="' . Configuration::get('MENU_HEIGHT') . '" size="6" class="right" /> <i>(' . $this->l('Default value:') . ' 1.2)</i>
            </div>
          </p>
          <!-- / 12/12/2009 12:05:44 $2.1 -->

          <!-- 2009-12-16 16:14:16 $2.4 -->
          <p class="clear">
            <div class="columnTdLeft">
              <label class="clean bold" for="categories_num">
                ' . $this->l('Num. prod. categories') . ' 
                <img src="' . _MODULE_DIR_ . $this->_module . '/gfx/tab/help.png" 
                     alt="" 
                     title="' . $this->l('Show number of products per categories.') . '" 
                     class="help middle" />
              </label> 
            </div>
            <div class="columnTdRight">
              <select name="categories_num" id="categories_num">
                <option value="0"' . (Configuration::get('MENU_CATEGORIES_NUM') == '0' ? ' selected=""': '') . '>' . $this->l('No') . '</option>
                <option value="1"' . (Configuration::get('MENU_CATEGORIES_NUM') == '1' ? ' selected=""': '') . '>' . $this->l('SubMenu') . '</option>
                <option value="2"' . (Configuration::get('MENU_CATEGORIES_NUM') == '2' ? ' selected=""': '') . '>' . $this->l('Header & Submenu') . '</option>
              </select>
            </div>
          </p>
          <!-- / 2009-12-16 16:14:16 $2.4 -->

          <!-- 2009-12-26 14:41:28 -->
          <p class="clear">
            <div class="columnTdLeft">
              <label class="clean bold" for="categories_zero">
                ' . $this->l('Show num. zero') . ' 
                <img src="' . _MODULE_DIR_ . $this->_module . '/gfx/tab/help.png" 
                     alt="" 
                     title="' . $this->l('Show zero for categories num.') . '" 
                     class="help middle" />
              </label> 
            </div>
            <div class="columnTdRight">
              <input type="checkbox" name="categories_zero" id="height" value="1"' . (Configuration::get('MENU_CATEGORIES_ZERO') ? ' checked=""' : ''). '/>
            </div>
          </p>
          <!-- / 2009-12-26 14:41:28 -->

          <p class="clear">
            <div class="columnTdLeft">
              <label class="clean bold" for="_menu">
                ' . $this->l('Background image') . ' 
                <img src="' . _MODULE_DIR_ . $this->_module . '/gfx/tab/help.png" 
                     alt="" 
                     title="' . $this->l('Upload a background image for your menu.') . '" 
                     class="help middle" />
              </label> 
            </div>
            <div class="columnTdRight">
              <input type="file" name="menu" id="_menu" />
            </div>
          </p>

          <p class="clear">
            <div class="columnTdLeft">
              <label class="clean bold" for="hover">
                ' . $this->l('Hover background image') . ' 
                <img src="' . _MODULE_DIR_ . $this->_module . '/gfx/tab/help.png" 
                     alt="" 
                     title="' . $this->l('Upload a background hover image for your menu.') . '" 
                     class="help middle" />
              </label> 
            </div>
            <div class="columnTdRight">
              <input type="file" name="hover" id="hover" />
            </div>
          </p>

          <!-- 2011-02-21 00:31:24 -->
          <p class="clear">
            <div class="columnTdLeft">
              <label class="clean bold" for="cache_refresh">
                ' . $this->l('Cache refresh') . '
                <img src="' . _MODULE_DIR_ . $this->_module . '/gfx/tab/help.png"
                     alt=""
                     title="' . $this->l('Set a value to refresh cache in seconds.') . '"
                     class="help middle" />
              </label>
            </div>
            <div class="columnTdRight">
              <input type="text" name="cache_refresh" id="cache_refresh" value="' . Configuration::get('MENU_CACHE_REFRESH') . '" size="6" class="right" />
              <i>(' . $this->l('Default value:') . ' 120)</i>
            </div>
          </p>
          <!-- /2011-02-21 00:31:24 -->

        </div>
        <br class="clear" /><br class="clear" />
        <div class="clear center">
          <input type="submit" name="submitOptions" value="' . $this->l(' Save options ') . '" class="button" />
        </div>
      </fieldset>
    </form>
		<br />
		<fieldset class="flash" id="fsUploadProgress">
			<legend>' . $this->l('Powered by Julien Breux') . '</legend>
			<p>
				' . $this->l('Project author: ') . ' Julien Breux
			</p>
			<p>&nbsp;</p>
			<p style="text-align:center;">
				<a href="mailto:julien.breux@gmail.com" class="button">' . $this->l('Contact:') . ' julien.breux@gmail.com</a>
			</p>
		</fieldset>';
  }

  private function _displayInputRadio($mainLabel, $field, $fieldSelected = 1, $help = '', $values = array('Yes'=>1, 'No'=>0)) {
    $html = '
    <p class="default">
      <span class="middle bold">' . $this->l($mainLabel) . '</span> ';
    if ($help != '') {
      $html .= '
        <img src="' . _MODULE_DIR_ . $this->_module . '/gfx/tab/help.png" 
             alt="" 
             title="' . $this->l($help) . '" 
             class="help middle" />';
    }
    $html .= '<br />';
    foreach ($values as $label => $value) {
      $html .= '
      <input type="radio" name="' . $field . '" id="' . $field . '_' . $value . '" value="' . $value . '" class="pointer" ' . ($fieldSelected == $value ? 'checked=""' : ''). ' />
      <label for="' . $field . '_' . $value . '" class="clean middle pointer">' . $this->l($label) . '</label>&nbsp;&nbsp;';
    }
    return $html . '</p>';
  }

  private function _getCategoryOption($id_category, $id_lang, $children = true, $selectedCat = 0) {
    $categorie = new Category($id_category, $id_lang);
    if(is_null($categorie->id))
      return;
    if(count(explode('.', $categorie->name)) > 1)
      $name = str_replace('.', '', strstr($categorie->name, '.'));
    else
      $name = $categorie->name;
    $this->_html .= '<option value="'.$categorie->id.'" ' . (($categorie->id == $selectedCat) ? 'selected=""' : '') . ' 
                    style="margin-left:'.(($children) ? round(0+(15*(int)$categorie->level_depth)) : 0).'px;">'.$name.'</option>';
    if($children)
    {
      $childrens = Category::getChildren($id_category, $id_lang);
      if(count($childrens))
        foreach($childrens as $_children)
          $this->_getCategoryOption($_children['id_category'], $id_lang, $children, $selectedCat);
    }
  }

	protected function _isAdmin()
	{
		global $cookie;
		$employee = new Employee((int) $cookie->id_employee);
		return (int) $employee->id_profile === (int) _PS_ADMIN_PROFILE_;
	}
}
