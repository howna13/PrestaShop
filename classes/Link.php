<?php
/*
* 2007-2011 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2011 PrestaShop SA
*  @version  Release: $Revision: 7465 $
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class LinkCore
{
	/** @var boolean Rewriting activation */
	protected $allow;
	protected $url;
	public static $cache = array('page' => array());

	public $protocol_link;
	public $protocol_content;

	/**
	  * Constructor (initialization only)
	  */
	public function __construct($protocol_link = null, $protocol_content = null)
	{
		$this->allow = (int)Configuration::get('PS_REWRITING_SETTINGS');
		$this->url = $_SERVER['SCRIPT_NAME'];
		$this->protocol_link = $protocol_link;
		$this->protocol_content = $protocol_content;
	}

	/**
	 * Create a link to delete a product
	 *
	 * @param mixed $product ID of the product OR a Product object
	 * @param int $id_picture ID of the picture to delete
	 * @return string
	 */
	public function getProductDeletePictureLink($product, $id_picture)
	{
		$url = $this->getProductLink($product);
		return $url.((strpos($url, '?')) ? '&' : '?').'&deletePicture='.$id_picture;
	}

	/**
	 * Create a link to a product
	 *
	 * @param mixed $product Product object (can be an ID product, but deprecated)
	 * @param string $alias
	 * @param string $category
	 * @param string $ean13
	 * @param int $id_lang
	 * @param int $id_shop (since 1.5.0) ID shop need to be used when we generate a product link for a product in a cart
	 * @param int $ipa ID product attribute
	 * @return string
	 */
	public function getProductLink($product, $alias = null, $category = null, $ean13 = null, $id_lang = null, $id_shop = null, $ipa = 0)
	{
		$dispatcher = Dispatcher::getInstance();
		$url = _PS_BASE_URL_.__PS_BASE_URI__;

		if (!$id_lang)
			$id_lang = Context::getContext()->language->id;

		// @todo use specific method ?
		if ($id_shop && ($shop = Shop::getShop($id_shop)))
			$url = 'http://'.$shop['domain'].$shop['uri'];
		$url .= $this->getLangLink($id_lang);

		if (!is_object($product))
		{
			if (is_array($product) && isset($product['id_product']))
					$product = new Product((int)$product['id_product'], false, $id_lang);
			else if(is_numeric($product))
					$product = new Product((int)$product, false, $id_lang);
			else
				throw new PrestashopException('Invalid product vars');
		}


		// Set available keywords
		$params = array();
		$params['id'] = $product->id;
		$params['rewrite'] = (!$alias) ? $product->link_rewrite : $alias;
		$params['ean13'] = (!$ean13) ? $product->ean13 : $ean13;
		$params['category'] = (!$category) ? $product->category : $category;
		$params['meta_keywords'] =	Tools::str2url($product->meta_keywords);
		$params['meta_title'] = Tools::str2url($product->meta_title);

		if ($dispatcher->hasKeyword('product_rule', 'manufacturer'))
			$params['manufacturer'] = Tools::str2url($product->isFullyLoaded ? $product->manufacturer_name : Manufacturer::getNameById($product->id_manufacturer));

		if ($dispatcher->hasKeyword('product_rule', 'supplier'))
			$params['supplier'] = Tools::str2url($product->isFullyLoaded ? $product->supplier_name : Supplier::getNameById($product->id_supplier));

		if ($dispatcher->hasKeyword('product_rule', 'price'))
			$params['supplier'] = $product->isFullyLoaded ? $product->price : Product::getPriceStatic($product->id, false, NULL, 6, NULL, false, true, 1, false, NULL, NULL, NULL, $product->specificPrice);

		if ($dispatcher->hasKeyword('product_rule', 'tags'))
			$params['tags'] = Tools::str2url($product->getTags($id_lang));

		if ($ipa)
			$anchor = $product->getAnchor($ipa);
		else
			$anchor = '';

		return $url.$dispatcher->createUrl('product_rule', $params, $this->allow, $anchor);
	}

	/**
	 * Create a link to a category
	 *
	 * @param mixed $category Category object (can be an ID category, but deprecated)
	 * @param string $alias
	 * @param int $id_lang
	 * @return string
	 */
	public function getCategoryLink($category, $alias = null, $id_lang = null)
	{
		if (!$id_lang)
			$id_lang = Context::getContext()->language->id;
		$url = _PS_BASE_URL_.__PS_BASE_URI__.$this->getLangLink($id_lang);

		if (!is_object($category))
			$category = new Category($category, $id_lang);

		// Set available keywords
		$params = array();
		$params['id'] = $category->id;
		$params['rewrite'] = (!$alias) ? $category->link_rewrite : $alias;
		$params['meta_keywords'] =	Tools::str2url($category->meta_keywords);
		$params['meta_title'] = Tools::str2url($category->meta_title);

		return $url.Dispatcher::getInstance()->createUrl('category_rule', $params, $this->allow);
	}

	/**
	 * Create a link to a CMS category
	 *
	 * @param mixed $category CMSCategory object (can be an ID category, but deprecated)
	 * @param string $alias
	 * @param int $id_lang
	 * @return string
	 */
	public function getCMSCategoryLink($category, $alias = null, $id_lang = null)
	{
		if (!$id_lang)
			$id_lang = Context::getContext()->language->id;
		$url = _PS_BASE_URL_.__PS_BASE_URI__.$this->getLangLink($id_lang);

		if (!is_object($category))
			$category = new CMSCategory($category, $id_lang);

		// Set available keywords
		$params = array();
		$params['id'] = $category->id;
		$params['rewrite'] = (!$alias) ? $category->link_rewrite : $alias;
		$params['meta_keywords'] =	Tools::str2url($category->meta_keywords);
		$params['meta_title'] = Tools::str2url($category->meta_title);

		return $url.Dispatcher::getInstance()->createUrl('cms_category_rule', $params, $this->allow);
	}

	/**
	 * Create a link to a CMS page
	 *
	 * @param mixed $cms CMS object (can be an ID CMS, but deprecated)
	 * @param string $alias
	 * @param bool $ssl
	 * @param int $id_lang
	 * @return string
	 */
	public function getCMSLink($cms, $alias = null, $ssl = false, $id_lang = null)
	{
		$base = (($ssl AND Configuration::get('PS_SSL_ENABLED')) ? Tools::getShopDomainSsl(true) : Tools::getShopDomain(true));

		if (!$id_lang)
			$id_lang = Context::getContext()->language->id;
		$url = $base.__PS_BASE_URI__.$this->getLangLink($id_lang);

		if (!is_object($cms))
			$cms = new CMS($cms, $id_lang);

		// Set available keywords
		$params = array();
		$params['id'] = $cms->id;
		$params['rewrite'] = (!$alias) ? $cms->link_rewrite : $alias;
		$params['meta_keywords'] =	Tools::str2url($cms->meta_keywords);
		$params['meta_title'] = Tools::str2url($cms->meta_title);

		return $url.Dispatcher::getInstance()->createUrl('cms_rule', $params, $this->allow);
	}

	/**
	 * Create a link to a supplier
	 *
	 * @param mixed $supplier Supplier object (can be an ID supplier, but deprecated)
	 * @param string $alias
	 * @param int $id_lang
	 * @return string
	 */
	public function getSupplierLink($supplier, $alias = null, $id_lang = null)
	{
		if (!$id_lang)
			$id_lang = Context::getContext()->language->id;
		$url = _PS_BASE_URL_.__PS_BASE_URI__.$this->getLangLink($id_lang);

		if (!is_object($supplier))
			$supplier = new Supplier($supplier, $id_lang);

		// Set available keywords
		$params = array();
		$params['id'] = $supplier->id;
		$params['rewrite'] = (!$alias) ? $supplier->link_rewrite : $alias;
		$params['meta_keywords'] =	Tools::str2url($supplier->meta_keywords);
		$params['meta_title'] = Tools::str2url($supplier->meta_title);

		return $url.Dispatcher::getInstance()->createUrl('supplier_rule', $params, $this->allow);
	}

	/**
	 * Create a link to a manufacturer
	 *
	 * @param mixed $manufacturer Manufacturer object (can be an ID supplier, but deprecated)
	 * @param string $alias
	 * @param int $id_lang
	 * @return string
	 */
	public function getManufacturerLink($manufacturer, $alias = null, $id_lang = null)
	{
		if (!$id_lang)
			$id_lang = Context::getContext()->language->id;
		$url = _PS_BASE_URL_.__PS_BASE_URI__.$this->getLangLink($id_lang);

		if (!is_object($manufacturer))
			$manufacturer = new Manufacturer($manufacturer, $id_lang);

		// Set available keywords
		$params = array();
		$params['id'] = $manufacturer->id;
		$params['rewrite'] = (!$alias) ? $manufacturer->link_rewrite : $alias;
		$params['meta_keywords'] =	Tools::str2url($manufacturer->meta_keywords);
		$params['meta_title'] = Tools::str2url($manufacturer->meta_title);

		return $url.Dispatcher::getInstance()->createUrl('manufacturer_rule', $params, $this->allow);
	}

	/**
	 * Create a link to a module
	 *
	 * @since 1.5.0
	 * @param string $module Module name
	 * @param string $process Action name
	 * @param int $id_lang
	 * @return string
	 */
	public function getModuleLink($module, $process, $ssl = false, $id_lang = null)
	{
		$base = (($ssl && Configuration::get('PS_SSL_ENABLED')) ? _PS_BASE_URL_SSL_ : _PS_BASE_URL_);

		if (!$id_lang)
			$id_lang = Context::getContext()->language->id;
		$url = $base.__PS_BASE_URI__.$this->getLangLink($id_lang);

		// Set available keywords
		$params = array();
		$params['module'] = $module;
		$params['process'] = $process;

		return $url.Dispatcher::getInstance()->createUrl('module', $params, $this->allow);
	}

	/**
	 * Use controller name to create a link
	 *
	 * @param string $controller
	 * @param boolean $with_token include or not the token in the url
	 * @return controller url
	 */
	public function getAdminLink($controller, $with_token = true)
	{
		$params = $with_token ? array('token' => Tools::getAdminTokenLite($controller)) : array();
		return Dispatcher::getInstance()->createUrl($controller, $params, false);
	}

	/**
	 * Returns a link to a product image for display
	 * Note: the new image filesystem stores product images in subdirectories of img/p/
	 *
	 * @param string $name rewrite link of the image
	 * @param string $ids id part of the image filename - can be "id_product-id_image" (legacy support, recommended) or "id_image" (new)
	 * @param string $type
	 */
	public function getImageLink($name, $ids, $type = null)
	{
		// legacy mode or default image
		$theme = ((Shop::isFeatureActive() && file_exists(_PS_PROD_IMG_DIR_.$ids.($type ? '-'.$type : '').'-'.(int)Context::getContext()->shop->id_theme.'.jpg')) ? '-'.Context::getContext()->shop->id_theme : '');
		if ((Configuration::get('PS_LEGACY_IMAGES')
			&& (file_exists(_PS_PROD_IMG_DIR_.$ids.($type ? '-'.$type : '').$theme.'.jpg')))
			|| strpos($ids, 'default') !== false)
		{
			if ($this->allow == 1)
				$uri_path = __PS_BASE_URI__.$ids.($type ? '-'.$type : '').$theme.'/'.$name.'.jpg';
			else
				$uri_path = _THEME_PROD_DIR_.$ids.($type ? '-'.$type : '').$theme.'.jpg';
		}
		else
		{
			// if ids if of the form id_product-id_image, we want to extract the id_image part
			$split_ids = explode('-', $ids);
			$id_image = (isset($split_ids[1]) ? $split_ids[1] : $split_ids[0]);
			$theme = ((Shop::isFeatureActive() && file_exists(_PS_PROD_IMG_DIR_.Image::getImgFolderStatic($id_image).$id_image.($type ? '-'.$type : '').'-'.(int)Context::getContext()->shop->id_theme.'.jpg')) ? '-'.Context::getContext()->shop->id_theme : '');
			if ($this->allow == 1)
				$uri_path = __PS_BASE_URI__.$id_image.($type ? '-'.$type : '').$theme.'/'.$name.'.jpg';
			else
				$uri_path = _THEME_PROD_DIR_.Image::getImgFolderStatic($id_image).$id_image.($type ? '-'.$type : '').$theme.'.jpg';
		}

		return $this->protocol_content.Tools::getMediaServer($uri_path).$uri_path;
	}

	public function getMediaLink($filepath)
	{
		return Tools::getProtocol().Tools::getMediaServer($filepath).$filepath;
	}

	/**
	 * Create a simple link
	 *
	 * @param string $controller
	 * @param bool $ssl
	 * @param int $id_lang
	 * @param string|array $request
	 * @param Context $context
	 */
	public function getPageLink($controller, $ssl = false, $id_lang = null, $request = null)
	{
		$controller = str_replace('.php', '', $controller);

		if (!$id_lang)
			$id_lang = (int)Context::getContext()->language->id;

		if (!is_array($request))
			parse_str($request, $request);
		unset($request['controller']);

		$uri_path = Dispatcher::getInstance()->createUrl($controller, $request);
		$url = ($ssl AND Configuration::get('PS_SSL_ENABLED')) ? Tools::getShopDomainSsl(true) : Tools::getShopDomain(true);
		$url .= __PS_BASE_URI__.$this->getLangLink($id_lang).ltrim($uri_path, '/');

		return $url;
	}

	public function getCatImageLink($name, $id_category, $type = null)
	{
		return ($this->allow == 1) ? (__PS_BASE_URI__.'c/'.$id_category.($type ? '-'.$type : '').'/'.$name.'.jpg') : (_THEME_CAT_DIR_.$id_category.($type ? '-'.$type : '').'.jpg');
	}

	/**
	  * Create link after language change, for the change language block
	  *
	  * @param integer $id_lang Language ID
	  * @return string link
	  */
	public function getLanguageLink($id_lang, Context $context = null)
	{
		if (!$context)
			$context = Context::getContext();
		$matches = array();
		$request = $_SERVER['REQUEST_URI'];
		preg_match('#^/([a-z]{2})/([^\?]*).*$#', $request, $matches);
		if ($matches)
		{
			$current_iso = $matches[1];
			$rewrite = $matches[2];
			$url_rewrite = Meta::getEquivalentUrlRewrite($id_lang, Language::getIdByIso($current_iso), $rewrite);
			$request = str_replace($rewrite, $url_rewrite, $request);
		}

		parse_str($_SERVER['QUERY_STRING'], $queryTab);
		unset($queryTab['isolang'], $queryTab['controller']);

		if (!$this->allow)
			$queryTab['id_lang'] = $id_lang;

		return $this->getPageLink(Dispatcher::getInstance()->getController(), false, $id_lang, $queryTab);
	}

	public function goPage($url, $p)
	{
		return $url.($p == 1 ? '' : (!strstr($url, '?') ? '?' : '&amp;').'p='.(int)($p));
	}

	public function getPaginationLink($type, $id_object, $nb = false, $sort = false, $pagination = false, $array = false)
	{
		if ($type AND $id_object)
			$url = $this->{'get'.$type.'Link'}($id_object, NULL);
		else
		{
			$url = $this->url;
			if (Configuration::get('PS_REWRITING_SETTINGS'))
				$url = $this->getPageLink(basename($url));
		}
		$vars = (!$array ? '' : array());
		$varsNb = array('n', 'search_query');
		$varsSort = array('orderby', 'orderway');
		$varsPagination = array('p');

		$n = 0;
		foreach ($_GET AS $k => $value)
			if ($k != 'id_'.$type)
			{
				if (Configuration::get('PS_REWRITING_SETTINGS') AND ($k == 'isolang' OR $k == 'id_lang'))
					continue;
				$ifNb = (!$nb || ($nb AND !in_array($k, $varsNb)));
				$ifSort = (!$sort OR ($sort AND !in_array($k, $varsSort)));
				$ifPagination = (!$pagination || ($pagination && !in_array($k, $varsPagination)));
				if ($ifNb && $ifSort && $ifPagination AND !is_array($value))
					!$array ? ($vars .= ((!$n++ && ($this->allow == 1 || $url == $this->url)) ? '?' : '&').urlencode($k).'='.urlencode($value)) : ($vars[urlencode($k)] = urlencode($value));
			}
		if (!$array)
			return $url.$vars;
		$vars['requestUrl'] = $url;
		if ($type AND $id_object)
			$vars['id_'.$type] = (is_object($id_object) ? (int)$id_object->id : (int)$id_object);
		return $vars;
	}

	public function addSortDetails($url, $orderby, $orderway)
	{
		return $url.(!strstr($url, '?') ? '?' : '&').'orderby='.urlencode($orderby).'&orderway='.urlencode($orderway);
	}

	protected function getLangLink($id_lang = NULL, Context $context = null)
	{
		if (!$context)
			$context = Context::getContext();
		if (!$this->allow OR !Language::isMultiLanguageActivated())
			return '';

		if (!$id_lang)
			$id_lang = $context->language->id;

		return Language::getIsoById($id_lang).'/';
	}
}
