<?php
defined('EXTENSION_NAME')			|| define('EXTENSION_NAME',            'Admin Quick Edit PRO');
defined('EXTENSION_VERSION')		|| define('EXTENSION_VERSION',         '6.2.4');
defined('EXTENSION_ID')				|| define('EXTENSION_ID',              '3805');
defined('EXTENSION_COMPATIBILITY')	|| define('EXTENSION_COMPATIBILITY',   'OpenCart 3.0.x.x');
defined('EXTENSION_STORE_URL')		|| define('EXTENSION_STORE_URL',       'https://www.opencart.com/index.php?route=marketplace/extension/info&extension_id=' . EXTENSION_ID);
defined('EXTENSION_PURCHASE_URL')	|| define('EXTENSION_PURCHASE_URL',    'https://www.opencart.com/index.php?route=marketplace/purchase&extension_id=' . EXTENSION_ID);
defined('EXTENSION_RATE_URL')		|| define('EXTENSION_RATE_URL',        'https://www.opencart.com/index.php?route=account/rating/add&extension_id=' . EXTENSION_ID);
defined('EXTENSION_SUPPORT_EMAIL')	|| define('EXTENSION_SUPPORT_EMAIL',   'support@opencart.ee');
defined('EXTENSION_SUPPORT_LINK')	|| define('EXTENSION_SUPPORT_LINK',    'https://www.opencart.com/index.php?route=support/seller&extension_id=' . EXTENSION_ID);
defined('EXTENSION_SUPPORT_FORUM')	|| define('EXTENSION_SUPPORT_FORUM',   'https://forum.opencart.com/viewtopic.php?f=123&t=45057');
defined('OTHER_EXTENSIONS')			|| define('OTHER_EXTENSIONS',          'https://www.opencart.com/index.php?route=marketplace/extension&filter_member=bull5-i');
defined('EXTENSION_MIN_PHP_VERSION')|| define('EXTENSION_MIN_PHP_VERSION', '5.4.0');

class ControllerExtensionModuleAdminQuickEdit extends Controller {
	private $error = array();
	protected $alert = array(
		'error'     => array(),
		'warning'   => array(),
		'success'   => array(),
		'info'      => array()
	);

	private static $config_defaults = array(
		'module_admin_quick_edit_installed'                                 => 1,
		'module_admin_quick_edit_installed_version'                         => EXTENSION_VERSION,
		'module_admin_quick_edit_status'                                    => 0,
		'module_admin_quick_edit_override_menu_entry'                       => 0,
		'module_admin_quick_edit_match_anywhere'                            => 0,
		'module_admin_quick_edit_alternate_row_colour'                      => 0,
		'module_admin_quick_edit_row_hover_highlighting'                    => 0,
		'module_admin_quick_edit_highlight_status'                          => 0,
		'module_admin_quick_edit_highlight_yes_no'                          => 0,
		'module_admin_quick_edit_highlight_actions'                         => 0,
		'module_admin_quick_edit_interval_filter'                           => 0,
		'module_admin_quick_edit_price_relative_to'                         => 'previous', // 'product'
		'module_admin_quick_edit_batch_edit'                                => 0,
		'module_admin_quick_edit_quick_edit_on'                             => 'click',
		'module_admin_quick_edit_list_view_image_width'                     => 40,
		'module_admin_quick_edit_list_view_image_height'                    => 40,
		'module_admin_quick_edit_single_language_editing'                   => 0,
		'module_admin_quick_edit_catalog_categories_status'                 => 0,
		'module_admin_quick_edit_catalog_categories_default_sort'           => 'name',
		'module_admin_quick_edit_catalog_categories_default_order'          => 'ASC',
		'module_admin_quick_edit_catalog_products_status'                   => 0,
		'module_admin_quick_edit_catalog_products_filter_sub_category'      => 0,
		'module_admin_quick_edit_catalog_products_default_sort'             => 'pd.name',
		'module_admin_quick_edit_catalog_products_default_order'            => 'ASC',
		'module_admin_quick_edit_catalog_recurrings_status'                 => 0,
		'module_admin_quick_edit_catalog_recurrings_default_sort'           => 'rd.name',
		'module_admin_quick_edit_catalog_recurrings_default_order'          => 'ASC',
		'module_admin_quick_edit_catalog_filters_status'                    => 0,
		'module_admin_quick_edit_catalog_filters_default_sort'              => 'fgd.name',
		'module_admin_quick_edit_catalog_filters_default_order'             => 'ASC',
		'module_admin_quick_edit_catalog_attributes_status'                 => 0,
		'module_admin_quick_edit_catalog_attributes_default_sort'           => 'ad.name',
		'module_admin_quick_edit_catalog_attributes_default_order'          => 'ASC',
		'module_admin_quick_edit_catalog_attribute_groups_status'           => 0,
		'module_admin_quick_edit_catalog_attribute_groups_default_sort'     => 'agd.name',
		'module_admin_quick_edit_catalog_attribute_groups_default_order'    => 'ASC',
		'module_admin_quick_edit_catalog_options_status'                    => 0,
		'module_admin_quick_edit_catalog_options_default_sort'              => 'od.name',
		'module_admin_quick_edit_catalog_options_default_order'             => 'ASC',
		'module_admin_quick_edit_catalog_manufacturers_status'              => 0,
		'module_admin_quick_edit_catalog_manufacturers_default_sort'        => 'm.name',
		'module_admin_quick_edit_catalog_manufacturers_default_order'       => 'ASC',
		'module_admin_quick_edit_catalog_downloads_status'                  => 0,
		'module_admin_quick_edit_catalog_downloads_default_sort'            => 'dd.name',
		'module_admin_quick_edit_catalog_downloads_default_order'           => 'ASC',
		'module_admin_quick_edit_catalog_reviews_status'                    => 0,
		'module_admin_quick_edit_catalog_reviews_default_sort'              => 'r.date_added',
		'module_admin_quick_edit_catalog_reviews_default_order'             => 'DESC',
		'module_admin_quick_edit_catalog_information_status'                => 0,
		'module_admin_quick_edit_catalog_information_default_sort'          => 'id.title',
		'module_admin_quick_edit_catalog_information_default_order'         => 'ASC',
		'module_admin_quick_edit_customer_customers_status'                 => 0,
		'module_admin_quick_edit_customer_customers_default_sort'           => 'name',
		'module_admin_quick_edit_customer_customers_default_order'          => 'ASC',
		'module_admin_quick_edit_design_seo_urls_status'                    => 0,
		'module_admin_quick_edit_design_seo_urls_default_sort'              => 'keyword',
		'module_admin_quick_edit_design_seo_urls_default_order'             => 'ASC',
		'module_admin_quick_edit_marketing_coupons_status'                  => 0,
		'module_admin_quick_edit_marketing_coupons_default_sort'            => 'c.name',
		'module_admin_quick_edit_marketing_coupons_default_order'           => 'ASC',
		'module_admin_quick_edit_marketing_marketing_status'                => 0,
		'module_admin_quick_edit_marketing_marketing_default_sort'          => 'm.name',
		'module_admin_quick_edit_marketing_marketing_default_order'         => 'ASC',
		'module_admin_quick_edit_sale_orders_status'                        => 0,
		'module_admin_quick_edit_sale_orders_notify_customer'               => 0,
		'module_admin_quick_edit_sale_returns_status'                       => 0,
		'module_admin_quick_edit_sale_returns_notify_customer'              => 0,
		'module_admin_quick_edit_sale_returns_default_sort'                 => 'r.return_id',
		'module_admin_quick_edit_sale_returns_default_order'                => 'DESC',
		'module_admin_quick_edit_sale_vouchers_status'                      => 0,
		'module_admin_quick_edit_sale_vouchers_default_sort'                => 'v.date_added',
		'module_admin_quick_edit_sale_vouchers_default_order'               => 'DESC',
		'module_admin_quick_edit_sale_voucher_themes_status'                => 0,
		'module_admin_quick_edit_sale_voucher_themes_default_sort'          => 'vtd.name',
		'module_admin_quick_edit_sale_voucher_themes_default_order'         => 'ASC',
		'module_admin_quick_edit_services'                                  => "W10=",
	);

	private static $column_defaults = array(
		'module_admin_quick_edit_catalog_products'  => array(
			'selector'          => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>   0, 'align' => 'text-center', 'type' =>           '', 'sort' => ''                , 'rel' => array(),               'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
			'id'                => array('display' => 0, 'qe_status' => 0, 'editable' => 0, 'index' =>   5, 'align' =>   'text-left', 'type' =>           '', 'sort' => 'p.product_id'    , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'image'             => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  10, 'align' => 'text-center', 'type' =>   'image_qe', 'sort' => ''                , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'category'          => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  20, 'align' =>   'text-left', 'type' =>     'cat_qe', 'sort' => ''                , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'manufacturer'      => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  30, 'align' =>   'text-left', 'type' => 'manufac_qe', 'sort' => 'm.name'          , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'name'              => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  40, 'align' =>   'text-left', 'type' =>    'name_qe', 'sort' => 'pd.name'         , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => array('return' => array('label' =>  'name', 'value' => 'product_id')))),
			'tag'               => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  50, 'align' =>   'text-left', 'type' =>     'tag_qe', 'sort' => ''                , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'model'             => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  60, 'align' =>   'text-left', 'type' =>         'qe', 'sort' => 'p.model'         , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => array('return' => array('label' => 'model', 'value' => 'product_id')))),
			'price'             => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  70, 'align' =>  'text-right', 'type' =>   'price_qe', 'sort' => 'p.price'         , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'quantity'          => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  80, 'align' =>  'text-right', 'type' =>     'qty_qe', 'sort' => 'p.quantity'      , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'sku'               => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  90, 'align' =>   'text-left', 'type' =>         'qe', 'sort' => 'p.sku'           , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => array('return' => array('label' =>   'sku', 'value' => 'product_id')))),
			'upc'               => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' => 100, 'align' =>   'text-left', 'type' =>         'qe', 'sort' => 'p.upc'           , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => array('return' => array('label' =>   'upc', 'value' => 'product_id')))),
			'ean'               => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' => 110, 'align' =>   'text-left', 'type' =>         'qe', 'sort' => 'p.ean'           , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => array('return' => array('label' =>   'ean', 'value' => 'product_id')))),
			'jan'               => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' => 120, 'align' =>   'text-left', 'type' =>         'qe', 'sort' => 'p.jan'           , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => array('return' => array('label' =>   'jan', 'value' => 'product_id')))),
			'isbn'              => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' => 130, 'align' =>   'text-left', 'type' =>         'qe', 'sort' => 'p.isbn'          , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => array('return' => array('label' =>  'isbn', 'value' => 'product_id')))),
			'mpn'               => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' => 140, 'align' =>   'text-left', 'type' =>         'qe', 'sort' => 'p.mpn'           , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => array('return' => array('label' =>   'mpn', 'value' => 'product_id')))),
			'location'          => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' => 150, 'align' =>   'text-left', 'type' =>         'qe', 'sort' => 'p.location'      , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => array('return' => array('label' => 'location', 'value' => 'location')))),
			'seo'               => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' => 160, 'align' =>   'text-left', 'type' =>     'seo_qe', 'sort' => 'seo'             , 'rel' => array('view_in_store'),'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => array('return' => array('label' =>   'seo', 'value' => 'product_id')))),
			'tax_class'         => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' => 170, 'align' =>   'text-left', 'type' => 'tax_cls_qe', 'sort' => 'tc.title'        , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'minimum'           => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' => 180, 'align' =>  'text-right', 'type' =>         'qe', 'sort' => 'p.minimum'       , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'subtract'          => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' => 190, 'align' => 'text-center', 'type' =>  'yes_no_qe', 'sort' => 'p.subtract'      , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'stock_status'      => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' => 200, 'align' =>   'text-left', 'type' =>   'stock_qe', 'sort' => 'ss.name'         , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'requires_shipping' => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' => 210, 'align' => 'text-center', 'type' =>  'yes_no_qe', 'sort' => 'p.shipping'      , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'date_added'        => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' => 215, 'align' =>   'text-left', 'type' =>'datetime_qe', 'sort' => 'p.date_added'    , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'date_available'    => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' => 220, 'align' =>   'text-left', 'type' =>    'date_qe', 'sort' => 'p.date_available', 'rel' => array(),               'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'date_modified'     => array('display' => 0, 'qe_status' => 0, 'editable' => 0, 'index' => 230, 'align' =>   'text-left', 'type' =>'datetime_qe', 'sort' => 'p.date_modified' , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'length'            => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' => 240, 'align' =>   'text-left', 'type' =>         'qe', 'sort' => 'p.length'        , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'width'             => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' => 250, 'align' =>  'text-right', 'type' =>         'qe', 'sort' => 'p.width'         , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'height'            => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' => 260, 'align' =>  'text-right', 'type' =>         'qe', 'sort' => 'p.height'        , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'weight'            => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' => 270, 'align' =>  'text-right', 'type' =>         'qe', 'sort' => 'p.weight'        , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'length_class'      => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' => 280, 'align' =>   'text-left', 'type' =>  'length_qe', 'sort' => 'lc.title'        , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'weight_class'      => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' => 290, 'align' =>   'text-left', 'type' =>  'weight_qe', 'sort' => 'wc.title'        , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'points'            => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' => 300, 'align' =>  'text-right', 'type' =>         'qe', 'sort' => 'p.points'        , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'filter'            => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' => 310, 'align' =>   'text-left', 'type' =>  'filter_qe', 'sort' => ''                , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'download'          => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' => 320, 'align' =>   'text-left', 'type' =>      'dl_qe', 'sort' => ''                , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'store'             => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' => 330, 'align' =>   'text-left', 'type' =>   'store_qe', 'sort' => ''                , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'sort_order'        => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' => 340, 'align' =>  'text-right', 'type' =>         'qe', 'sort' => 'p.sort_order'    , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'status'            => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' => 350, 'align' => 'text-center', 'type' =>  'status_qe', 'sort' => 'p.status'        , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'viewed'            => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' => 360, 'align' =>  'text-right', 'type' =>         'qe', 'sort' => 'p.viewed'        , 'rel' => array(),               'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'view_in_store'     => array('display' => 0, 'qe_status' => 0, 'editable' => 0, 'index' => 370, 'align' =>   'text-left', 'type' =>           '', 'sort' => ''                , 'rel' => array(),               'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
			'action'            => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' => 380, 'align' =>  'text-right', 'type' =>           '', 'sort' => ''                , 'rel' => array(),               'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
		),
		'module_admin_quick_edit_catalog_products_actions' => array(
			'attributes'        => array('display' => 0, 'index' =>  0, 'short' => 'attr',  'type' =>    'attr_qe', 'class' =>            '', 'rel' => array()),
			'discounts'         => array('display' => 0, 'index' =>  1, 'short' => 'dscnt', 'type' =>   'dscnt_qe', 'class' =>            '', 'rel' => array()),
			'images'            => array('display' => 0, 'index' =>  2, 'short' => 'img',   'type' =>  'images_qe', 'class' =>            '', 'rel' => array()),
			'filters'           => array('display' => 0, 'index' =>  3, 'short' => 'fltr',  'type' => 'filters_qe', 'class' =>            '', 'rel' => array('filter')),
			'options'           => array('display' => 0, 'index' =>  4, 'short' => 'opts',  'type' =>  'option_qe', 'class' =>            '', 'rel' => array()),
			'recurrings'        => array('display' => 0, 'index' =>  5, 'short' => 'rec',   'type' =>   'recur_qe', 'class' =>            '', 'rel' => array()),
			'related'           => array('display' => 0, 'index' =>  6, 'short' => 'rel',   'type' => 'related_qe', 'class' =>            '', 'rel' => array()),
			'downloads'         => array('display' => 0, 'index' =>  7, 'short' => 'dls',   'type' =>     'dls_qe', 'class' =>            '', 'rel' => array('download')),
			'specials'          => array('display' => 0, 'index' =>  8, 'short' => 'spcl',  'type' => 'special_qe', 'class' =>            '', 'rel' => array('price')),
			'keywords'          => array('display' => 0, 'index' =>  9, 'short' => 'seo',   'type' =>    'keyw_qe', 'class' =>            '', 'rel' => array('seo', 'view_in_store')),
			'descriptions'      => array('display' => 0, 'index' => 10, 'short' => 'desc',  'type' =>   'descr_qe', 'class' =>            '', 'rel' => array()),
			'view'              => array('display' => 1, 'index' => 11, 'short' => 'vw',    'type' =>       'view', 'class' =>            '', 'rel' => array()),
			'edit'              => array('display' => 1, 'index' => 12, 'short' => 'ed',    'type' =>       'edit', 'class' => 'btn-primary', 'rel' => array()),
		),
		'module_admin_quick_edit_catalog_categories' => array(
			'selector'          => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>   0, 'align' => 'text-center', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
			'id'                => array('display' => 0, 'qe_status' => 0, 'editable' => 0, 'index' =>   5, 'align' =>   'text-left', 'type' =>           '', 'sort' =>      'cp.category_id', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'image'             => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  10, 'align' => 'text-center', 'type' =>   'image_qe', 'sort' =>                    '', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'parent'            => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  15, 'align' =>   'text-left', 'type' =>  'parent_qe', 'sort' =>                'path', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false),        'rel' => array('name')),
			'name'              => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  20, 'align' =>   'text-left', 'type' =>    'name_qe', 'sort' =>                'name', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => array('return' => array('label' => 'short_name', 'value' => 'category_id')))),
			'column'            => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  25, 'align' =>   'text-left', 'type' =>         'qe', 'sort' =>            'c.column', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'top'               => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  30, 'align' => 'text-center', 'type' =>  'yes_no_qe', 'sort' =>               'c.top', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'seo'               => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  35, 'align' =>   'text-left', 'type' =>     'seo_qe', 'sort' =>                 'seo', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => array('return' => array('label' =>        'seo', 'value' => 'category_id'))), 'rel' => array('view_in_store')),
			'filter'            => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  40, 'align' =>   'text-left', 'type' =>  'filter_qe', 'sort' =>                    '', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'store'             => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  45, 'align' =>   'text-left', 'type' =>   'store_qe', 'sort' =>                    '', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'sort_order'        => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  50, 'align' =>  'text-right', 'type' =>         'qe', 'sort' =>        'c.sort_order', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'status'            => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  55, 'align' => 'text-center', 'type' =>  'status_qe', 'sort' =>            'c.status', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'view_in_store'     => array('display' => 0, 'qe_status' => 0, 'editable' => 0, 'index' =>  60, 'align' =>   'text-left', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
			'action'            => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>  65, 'align' =>  'text-right', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
		),
		'module_admin_quick_edit_catalog_recurrings' => array(
			'selector'          => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>   0, 'align' => 'text-center', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
			'id'                => array('display' => 0, 'qe_status' => 0, 'editable' => 0, 'index' =>   5, 'align' =>   'text-left', 'type' =>           '', 'sort' =>      'r.recurring_id', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'name'              => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  10, 'align' =>   'text-left', 'type' =>    'name_qe', 'sort' =>             'rd.name', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'sort_order'        => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  15, 'align' =>  'text-right', 'type' =>         'qe', 'sort' =>        'r.sort_order', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'status'            => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  20, 'align' => 'text-center', 'type' =>  'status_qe', 'sort' =>            'r.status', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'price'             => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  25, 'align' =>  'text-right', 'type' =>  'number_qe', 'sort' =>             'r.price', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'frequency'         => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  30, 'align' =>   'text-left', 'type' =>    'freq_qe', 'sort' =>         'r.frequency', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'duration'          => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  35, 'align' =>  'text-right', 'type' =>         'qe', 'sort' =>          'r.duration', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'cycle'             => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  40, 'align' =>  'text-right', 'type' =>         'qe', 'sort' =>             'r.cycle', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'trial_status'      => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  45, 'align' => 'text-center', 'type' =>  'status_qe', 'sort' =>      'r.trial_status', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'trial_price'       => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  50, 'align' =>  'text-right', 'type' =>  'number_qe', 'sort' =>       'r.trial_price', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'trial_frequency'   => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  55, 'align' =>   'text-left', 'type' =>    'freq_qe', 'sort' =>   'r.trial_frequency', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'trial_duration'    => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  60, 'align' =>  'text-right', 'type' =>         'qe', 'sort' =>    'r.trial_duration', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'trial_cycle'       => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  65, 'align' =>  'text-right', 'type' =>         'qe', 'sort' =>       'r.trial_cycle', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'action'            => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>  70, 'align' =>  'text-right', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
		),
		'module_admin_quick_edit_catalog_filters' => array(
			'selector'          => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>   0, 'align' => 'text-center', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
			'id'                => array('display' => 0, 'qe_status' => 0, 'editable' => 0, 'index' =>   5, 'align' =>   'text-left', 'type' =>           '', 'sort' =>  'fg.filter_group_id', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'group_name'        => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  10, 'align' =>   'text-left', 'type' =>    'name_qe', 'sort' =>            'fgd.name', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => array('return' => array('label' =>   'name', 'value' => 'filter_group_id')))),
			'filter'            => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  15, 'align' =>   'text-left', 'type' =>  'filter_qe', 'sort' =>                    '', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => array('return' => array('label' =>   'name', 'value' => 'filter_id')))),
			'sort_order'        => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  20, 'align' =>  'text-right', 'type' =>         'qe', 'sort' =>       'fg.sort_order', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'action'            => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>  25, 'align' =>  'text-right', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
		),
		'module_admin_quick_edit_catalog_attributes' => array(
			'selector'          => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>   0, 'align' => 'text-center', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
			'id'                => array('display' => 0, 'qe_status' => 0, 'editable' => 0, 'index' =>   5, 'align' =>   'text-left', 'type' =>           '', 'sort' =>      'a.attribute_id', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'name'              => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  10, 'align' =>   'text-left', 'type' =>    'name_qe', 'sort' =>             'ad.name', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => array('return' => array('label' =>   'name', 'value' => 'attribute_id')))),
			'attribute_group'   => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  15, 'align' =>   'text-left', 'type' =>   'group_qe', 'sort' =>     'attribute_group', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'sort_order'        => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  20, 'align' =>  'text-right', 'type' =>         'qe', 'sort' =>        'a.sort_order', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'action'            => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>  25, 'align' =>  'text-right', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
		),
		'module_admin_quick_edit_catalog_attribute_groups' => array(
			'selector'          => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>   0, 'align' => 'text-center', 'type' =>           '', 'sort' =>                      '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
			'id'                => array('display' => 0, 'qe_status' => 0, 'editable' => 0, 'index' =>   5, 'align' =>   'text-left', 'type' =>           '', 'sort' => 'ag.attribute_group_id', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'name'              => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  10, 'align' =>   'text-left', 'type' =>    'name_qe', 'sort' =>              'agd.name', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => array('return' => array('label' =>   'name', 'value' => 'attribute_group_id')))),
			'sort_order'        => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  15, 'align' =>  'text-right', 'type' =>         'qe', 'sort' =>         'ag.sort_order', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'action'            => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>  20, 'align' =>  'text-right', 'type' =>           '', 'sort' =>                      '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
		),
		'module_admin_quick_edit_catalog_options' => array(
			'selector'          => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>   0, 'align' => 'text-center', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
			'id'                => array('display' => 0, 'qe_status' => 0, 'editable' => 0, 'index' =>   5, 'align' =>   'text-left', 'type' =>           '', 'sort' =>         'o.option_id', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'name'              => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  10, 'align' =>   'text-left', 'type' =>    'name_qe', 'sort' =>             'od.name', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => array('return' => array('label' =>   'name', 'value' => 'option_id')))),
			'type'              => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  15, 'align' =>   'text-left', 'type' =>    'type_qe', 'sort' =>              'o.type', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false), 'rel' => array('option_value')),
			'option_value'      => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  20, 'align' =>   'text-left', 'type' => 'opt_val_qe', 'sort' =>                    '', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => array('return' => array('label' =>   'name', 'value' => 'option_value_id')))),
			'sort_order'        => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  25, 'align' =>  'text-right', 'type' =>         'qe', 'sort' =>        'o.sort_order', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'action'            => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>  30, 'align' =>  'text-right', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
		),
		'module_admin_quick_edit_catalog_manufacturers' => array(
			'selector'          => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>   0, 'align' => 'text-center', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
			'id'                => array('display' => 0, 'qe_status' => 0, 'editable' => 0, 'index' =>   5, 'align' =>   'text-left', 'type' =>           '', 'sort' =>   'm.manufacturer_id', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'image'             => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  10, 'align' => 'text-center', 'type' =>   'image_qe', 'sort' =>                    '', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'name'              => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  15, 'align' =>   'text-left', 'type' =>         'qe', 'sort' =>              'm.name', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => array('return' => array('label' =>   'name', 'value' => 'manufacturer_id')))),
			'seo'               => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  20, 'align' =>   'text-left', 'type' =>     'seo_qe', 'sort' =>                 'seo', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => array('return' => array('label' =>    'seo', 'value' => 'manufacturer_id'))), 'rel' => array('view_in_store')),
			'store'             => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  25, 'align' =>   'text-left', 'type' =>   'store_qe', 'sort' =>                    '', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'sort_order'        => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  30, 'align' =>  'text-right', 'type' =>         'qe', 'sort' =>        'm.sort_order', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'view_in_store'     => array('display' => 0, 'qe_status' => 0, 'editable' => 0, 'index' =>  35, 'align' =>   'text-left', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
			'action'            => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>  40, 'align' =>  'text-right', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
		),
		'module_admin_quick_edit_catalog_downloads' => array(
			'selector'          => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>   0, 'align' => 'text-center', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
			'id'                => array('display' => 0, 'qe_status' => 0, 'editable' => 0, 'index' =>   5, 'align' =>   'text-left', 'type' =>           '', 'sort' =>       'd.download_id', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'name'              => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  10, 'align' =>   'text-left', 'type' =>    'name_qe', 'sort' =>             'dd.name', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => array('return' => array('label' =>   'name', 'value' => 'download_id')))),
			'filename'          => array('display' => 0, 'qe_status' => 0, 'editable' => 0, 'index' =>  15, 'align' =>   'text-left', 'type' =>         'qe', 'sort' =>          'd.filename', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'mask'              => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  20, 'align' =>   'text-left', 'type' =>         'qe', 'sort' =>              'd.mask', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'date_added'        => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>  25, 'align' =>  'text-right', 'type' =>           '', 'sort' =>        'd.date_added', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'action'            => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>  30, 'align' =>  'text-right', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
		),
		'module_admin_quick_edit_catalog_reviews' => array(
			'selector'          => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>   0, 'align' => 'text-center', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
			'id'                => array('display' => 0, 'qe_status' => 0, 'editable' => 0, 'index' =>   5, 'align' =>   'text-left', 'type' =>           '', 'sort' =>         'r.review_id', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'product'           => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  10, 'align' =>   'text-left', 'type' => 'product_qe', 'sort' =>             'pd.name', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => array('return' => array('label' => 'product', 'value' => 'product_id'))), 'rel' => array('date_modified')),
			'author'            => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  15, 'align' =>   'text-left', 'type' =>         'qe', 'sort' =>            'r.author', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false), 'rel' => array('date_modified')),
			'text'              => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  20, 'align' =>   'text-left', 'type' =>    'text_qe', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false), 'rel' => array('date_modified')),
			'rating'            => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  25, 'align' =>  'text-right', 'type' =>         'qe', 'sort' =>            'r.rating', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false), 'rel' => array('date_modified')),
			'status'            => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  30, 'align' => 'text-center', 'type' =>  'status_qe', 'sort' =>            'r.status', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false), 'rel' => array('date_modified')),
			'date_added'        => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  35, 'align' =>   'text-left', 'type' =>    'date_qe', 'sort' =>        'r.date_added', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false), 'rel' => array('date_modified')),
			'date_modified'     => array('display' => 0, 'qe_status' => 0, 'editable' => 0, 'index' =>  40, 'align' =>   'text-left', 'type' =>           '', 'sort' =>     'r.date_modified', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'action'            => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>  45, 'align' =>  'text-right', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
		),
		'module_admin_quick_edit_catalog_information' => array(
			'selector'          => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>   0, 'align' => 'text-center', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
			'id'                => array('display' => 0, 'qe_status' => 0, 'editable' => 0, 'index' =>   5, 'align' =>   'text-left', 'type' =>           '', 'sort' =>    'i.information_id', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'title'             => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  10, 'align' =>   'text-left', 'type' =>   'title_qe', 'sort' =>            'id.title', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => array('return' => array('label' => 'title', 'value' => 'information_id')))),
			'seo'               => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  15, 'align' =>   'text-left', 'type' =>     'seo_qe', 'sort' =>                 'seo', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => array('return' => array('label' =>   'seo', 'value' => 'information_id'))), 'rel' => array('view_in_store')),
			'bottom'            => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  20, 'align' => 'text-center', 'type' =>  'yes_no_qe', 'sort' =>            'i.bottom', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'store'             => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  25, 'align' =>   'text-left', 'type' =>   'store_qe', 'sort' =>                    '', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'sort_order'        => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  30, 'align' =>  'text-right', 'type' =>         'qe', 'sort' =>        'i.sort_order', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'status'            => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  35, 'align' => 'text-center', 'type' =>  'status_qe', 'sort' =>            'i.status', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'view_in_store'     => array('display' => 0, 'qe_status' => 0, 'editable' => 0, 'index' =>  40, 'align' =>   'text-left', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
			'action'            => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>  45, 'align' =>  'text-right', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
		),
		'module_admin_quick_edit_customer_customers' => array(
			'selector'          => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>   0, 'align' => 'text-center', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
			'id'                => array('display' => 0, 'qe_status' => 0, 'editable' => 0, 'index' =>   5, 'align' =>   'text-left', 'type' =>           '', 'sort' =>       'c.customer_id', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'name'              => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  10, 'align' =>   'text-left', 'type' =>    'name_qe', 'sort' =>                'name', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => array('return' => array('label' =>  'name', 'value' => 'customer_id')))),
			'email'             => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  15, 'align' =>   'text-left', 'type' =>         'qe', 'sort' =>             'c.email', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => array('return' => array('label' => 'email', 'value' => 'customer_id')))),
			'telephone'         => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  20, 'align' =>   'text-left', 'type' =>         'qe', 'sort' =>         'c.telephone', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'newsletter'        => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  30, 'align' => 'text-center', 'type' =>  'yes_no_qe', 'sort' =>        'c.newsletter', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'customer_group'    => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  35, 'align' =>   'text-left', 'type' =>   'group_qe', 'sort' =>      'customer_group', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'status'            => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  40, 'align' => 'text-center', 'type' =>  'status_qe', 'sort' =>            'c.status', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'safe'              => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  50, 'align' => 'text-center', 'type' =>  'yes_no_qe', 'sort' =>              'c.safe', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'ip'                => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>  55, 'align' =>   'text-left', 'type' =>         'qe', 'sort' =>                'c.ip', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),

			'company'           => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  60, 'align' =>   'text-left', 'type' =>         'qe', 'sort' =>          'ca.company', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'website'           => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  65, 'align' =>   'text-left', 'type' =>         'qe', 'sort' =>          'ca.company', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'tracking'          => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  70, 'align' =>   'text-left', 'type' =>         'qe', 'sort' =>         'ca.tracking', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'commission'        => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  75, 'align' =>  'text-right', 'type' =>  'number_qe', 'sort' =>       'ca.commission', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'tax'               => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  80, 'align' =>  'text-right', 'type' =>         'qe', 'sort' =>              'ca.tax', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'affiliate_status'  => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  85, 'align' => 'text-center', 'type' =>  'status_qe', 'sort' =>           'ca.status', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),

			'date_added'        => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>  90, 'align' =>   'text-left', 'type' =>    'date_qe', 'sort' =>        'c.date_added', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'action'            => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>  95, 'align' =>  'text-right', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
		),
		'module_admin_quick_edit_sale_orders' => array(
			'status'            => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>   0, 'align' =>   'text-left', 'type' =>  'status_qe', 'sort' =>                    ''),
		),
		'module_admin_quick_edit_sale_returns' => array(
			'selector'          => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>   0, 'align' => 'text-center', 'type' =>          '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
			'id'                => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>   5, 'align' =>  'text-right', 'type' =>         'qe', 'sort' =>        'r.return_id', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'order_id'          => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  10, 'align' =>  'text-right', 'type' =>         'qe', 'sort' =>         'r.order_id', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false), 'rel' => array('date_modified')),
			'customer_id'       => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  15, 'align' =>   'text-left', 'type' =>'customer_qe', 'sort' =>      'customer_name', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => array('return' => array('label' => 'full_name', 'value' => 'customer_id'))), 'rel' => array('date_modified')),
			'customer'          => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  20, 'align' =>   'text-left', 'type' =>'fullname_qe', 'sort' =>           'customer', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => array('return' => array('label' => 'full_name', 'value' => 'customer_id'))), 'rel' => array('date_modified')),
			'email'             => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  25, 'align' =>   'text-left', 'type' =>         'qe', 'sort' =>            'r.email', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false), 'rel' => array('date_modified')),
			'telephone'         => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  30, 'align' =>   'text-left', 'type' =>         'qe', 'sort' =>        'r.telephone', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false), 'rel' => array('date_modified')),
			'product_id'        => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  35, 'align' =>   'text-left', 'type' => 'product_qe', 'sort' =>       'product_name', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => array('return' => array('label' => 'name', 'value' => 'product_id'))), 'rel' => array('date_modified')),
			'product'           => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  40, 'align' =>   'text-left', 'type' =>         'qe', 'sort' =>          'r.product', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => array('return' => array('label' => 'name', 'value' => 'product_id'))), 'rel' => array('date_modified')),
			'model'             => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  45, 'align' =>   'text-left', 'type' =>         'qe', 'sort' =>            'r.model', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false), 'rel' => array('date_modified')),
			'quantity'          => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  50, 'align' =>  'text-right', 'type' =>         'qe', 'sort' =>         'r.quantity', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false), 'rel' => array('date_modified')),
			'return_reason'     => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  55, 'align' =>   'text-left', 'type' =>  'reason_qe', 'sort' =>      'return_reason', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false), 'rel' => array('date_modified')),
			'opened'            => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  60, 'align' => 'text-center', 'type' =>  'yes_no_qe', 'sort' =>           'r.opened', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false), 'rel' => array('date_modified')),
			'comment'           => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  65, 'align' =>   'text-left', 'type' =>    'text_qe', 'sort' =>          'r.comment', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false), 'rel' => array('date_modified')),
			'return_action'     => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  70, 'align' =>   'text-left', 'type' =>  'action_qe', 'sort' =>      'return_action', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false), 'rel' => array('date_modified')),
			'return_status'     => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  75, 'align' =>   'text-left', 'type' =>  'status_qe', 'sort' =>      'return_status', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false), 'rel' => array('date_modified')),
			'date_ordered'      => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  80, 'align' =>   'text-left', 'type' =>    'date_qe', 'sort' =>     'r.date_ordered', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false), 'rel' => array('date_modified')),
			'date_added'        => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>  85, 'align' =>   'text-left', 'type' =>    'date_qe', 'sort' =>       'r.date_added', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false), 'rel' => array('date_modified')),
			'date_modified'     => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>  90, 'align' =>   'text-left', 'type' =>           '', 'sort' =>    'r.date_modified', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'action'            => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>  95, 'align' =>  'text-right', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
		),
		'module_admin_quick_edit_sale_vouchers' => array(
			'selector'          => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>   0, 'align' => 'text-center', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
			'id'                => array('display' => 0, 'qe_status' => 0, 'editable' => 0, 'index' =>   5, 'align' =>   'text-left', 'type' =>           '', 'sort' =>        'v.voucher_id', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'code'              => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  10, 'align' =>   'text-left', 'type' =>         'qe', 'sort' =>              'v.code', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'from_name'         => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  15, 'align' =>   'text-left', 'type' =>         'qe', 'sort' =>         'v.from_name', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'from_email'        => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  20, 'align' =>   'text-left', 'type' =>         'qe', 'sort' =>        'v.from_email', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'to_name'           => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  25, 'align' =>   'text-left', 'type' =>         'qe', 'sort' =>           'v.to_name', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'to_email'          => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  30, 'align' =>   'text-left', 'type' =>         'qe', 'sort' =>          'v.to_email', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'amount'            => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  35, 'align' =>  'text-right', 'type' =>  'amount_qe', 'sort' =>            'v.amount', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'theme'             => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  40, 'align' =>   'text-left', 'type' =>   'theme_qe', 'sort' =>               'theme', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'message'           => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  45, 'align' =>   'text-left', 'type' =>    'text_qe', 'sort' =>                    '', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'status'            => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  50, 'align' => 'text-center', 'type' =>  'status_qe', 'sort' =>            'v.status', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'date_added'        => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>  55, 'align' =>   'text-left', 'type' =>    'date_qe', 'sort' =>        'v.date_added', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'action'            => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>  60, 'align' =>  'text-right', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
		),
		'module_admin_quick_edit_sale_voucher_themes' => array(
			'selector'          => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>   0, 'align' => 'text-center', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
			'id'                => array('display' => 0, 'qe_status' => 0, 'editable' => 0, 'index' =>   5, 'align' =>   'text-left', 'type' =>           '', 'sort' => 'vt.voucher_theme_id', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'image'             => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  10, 'align' => 'text-center', 'type' =>   'image_qe', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
			'name'              => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  15, 'align' =>   'text-left', 'type' =>    'name_qe', 'sort' =>            'vtd.name', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'action'            => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>  20, 'align' =>  'text-right', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
		),
		'module_admin_quick_edit_marketing_marketing' => array(
			'selector'          => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>   0, 'align' => 'text-center', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
			'id'                => array('display' => 0, 'qe_status' => 0, 'editable' => 0, 'index' =>   5, 'align' =>   'text-left', 'type' =>           '', 'sort' =>      'm.marketing_id', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'name'              => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  10, 'align' =>   'text-left', 'type' =>         'qe', 'sort' =>              'm.name', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'description'       => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  15, 'align' =>   'text-left', 'type' =>    'text_qe', 'sort' =>       'm.description', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'code'              => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  20, 'align' =>   'text-left', 'type' =>         'qe', 'sort' =>              'm.code', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'clicks'            => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>  25, 'align' =>  'text-right', 'type' =>           '', 'sort' =>            'm.clicks', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'orders'            => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>  30, 'align' =>  'text-right', 'type' =>           '', 'sort' =>              'orders', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'date_added'        => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>  35, 'align' =>   'text-left', 'type' =>    'date_qe', 'sort' =>        'm.date_added', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'action'            => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>  40, 'align' =>  'text-right', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
		),
		'module_admin_quick_edit_marketing_coupons' => array(
			'selector'          => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>   0, 'align' => 'text-center', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
			'id'                => array('display' => 0, 'qe_status' => 0, 'editable' => 0, 'index' =>   5, 'align' =>   'text-left', 'type' =>           '', 'sort' =>         'c.coupon_id', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'name'              => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  10, 'align' =>   'text-left', 'type' =>         'qe', 'sort' =>              'c.name', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'code'              => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  15, 'align' =>   'text-left', 'type' =>         'qe', 'sort' =>              'c.code', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'type'              => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  20, 'align' =>   'text-left', 'type' =>    'type_qe', 'sort' =>              'c.type', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'total'             => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  25, 'align' =>  'text-right', 'type' =>         'qe', 'sort' =>             'c.total', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'products'          => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  30, 'align' =>   'text-left', 'type' =>    'prod_qe', 'sort' =>                    '', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'categories'        => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  35, 'align' =>   'text-left', 'type' =>     'cat_qe', 'sort' =>                    '', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'logged'            => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  40, 'align' => 'text-center', 'type' =>  'yes_no_qe', 'sort' =>            'c.logged', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'shipping'          => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  45, 'align' => 'text-center', 'type' =>  'yes_no_qe', 'sort' =>          'c.shipping', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'discount'          => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  50, 'align' =>  'text-right', 'type' =>         'qe', 'sort' =>          'c.discount', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'date_start'        => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  55, 'align' =>   'text-left', 'type' =>    'date_qe', 'sort' =>        'c.date_start', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'date_end'          => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  60, 'align' =>   'text-left', 'type' =>    'date_qe', 'sort' =>          'c.date_end', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'uses_total'        => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  65, 'align' =>  'text-right', 'type' =>         'qe', 'sort' =>        'c.uses_total', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'uses_customer'     => array('display' => 0, 'qe_status' => 1, 'editable' => 1, 'index' =>  70, 'align' =>  'text-right', 'type' =>         'qe', 'sort' =>     'c.uses_customer', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'status'            => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  75, 'align' => 'text-center', 'type' =>  'status_qe', 'sort' =>            'c.status', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'action'            => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>  80, 'align' =>  'text-right', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
		),
		'module_admin_quick_edit_design_seo_urls' => array(
			'selector'          => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>   0, 'align' => 'text-center', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
			'id'                => array('display' => 0, 'qe_status' => 0, 'editable' => 0, 'index' =>   5, 'align' =>   'text-left', 'type' =>           '', 'sort' =>          'seo_url_id', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'query'             => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  10, 'align' =>   'text-left', 'type' =>         'qe', 'sort' =>               'query', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'keyword'           => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  15, 'align' =>   'text-left', 'type' =>         'qe', 'sort' =>             'keyword', 'filter' => array('show' => 1, 'type' => 0, 'autocomplete' => false)),
			'store'             => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  20, 'align' =>   'text-left', 'type' =>   'store_qe', 'sort' =>               'store', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'language'          => array('display' => 1, 'qe_status' => 1, 'editable' => 1, 'index' =>  25, 'align' =>   'text-left', 'type' =>    'lang_qe', 'sort' =>            'language', 'filter' => array('show' => 1, 'type' => 1, 'autocomplete' => false)),
			'action'            => array('display' => 1, 'qe_status' => 0, 'editable' => 0, 'index' =>  30, 'align' =>  'text-right', 'type' =>           '', 'sort' =>                    '', 'filter' => array('show' => 0, 'type' => 0, 'autocomplete' => false)),
		),
	);

	private static $event_hooks = array(
		'admin_module_admin_quick_edit_menu'                            => array('trigger' => 'admin/view/common/column_left/before',                       'action' => 'extension/module/admin_quick_edit/menu_hook'),
		'admin_module_admin_quick_edit_design_seo_url_index'            => array('trigger' => 'admin/controller/design/seo_url/before',                     'action' => 'extension/module/admin_quick_edit/design_seo_url_index_hook'),
		'admin_module_admin_quick_edit_design_seo_url_delete'           => array('trigger' => 'admin/controller/design/seo_url/delete/before',              'action' => 'extension/module/admin_quick_edit/design_seo_url_delete_hook'),
		'admin_module_admin_quick_edit_design_seo_url_form'             => array('trigger' => 'admin/view/design/seo_url_form/before',                      'action' => 'extension/module/admin_quick_edit/design_seo_url_form_hook'),
		'admin_module_admin_quick_edit_customer_customer_index'         => array('trigger' => 'admin/controller/customer/customer/before',                  'action' => 'extension/module/admin_quick_edit/customer_customer_index_hook'),
		'admin_module_admin_quick_edit_customer_customer_delete'        => array('trigger' => 'admin/controller/customer/customer/delete/before',           'action' => 'extension/module/admin_quick_edit/customer_customer_delete_hook'),
		'admin_module_admin_quick_edit_customer_customer_form'          => array('trigger' => 'admin/view/customer/customer_form/before',                   'action' => 'extension/module/admin_quick_edit/customer_customer_form_hook'),
		'admin_module_admin_quick_edit_marketing_coupon_index'          => array('trigger' => 'admin/controller/marketing/coupon/before',                   'action' => 'extension/module/admin_quick_edit/marketing_coupon_index_hook'),
		'admin_module_admin_quick_edit_marketing_coupon_delete'         => array('trigger' => 'admin/controller/marketing/coupon/delete/before',            'action' => 'extension/module/admin_quick_edit/marketing_coupon_delete_hook'),
		'admin_module_admin_quick_edit_marketing_coupon_form'           => array('trigger' => 'admin/view/marketing/coupon_form/before',                    'action' => 'extension/module/admin_quick_edit/marketing_coupon_form_hook'),
		'admin_module_admin_quick_edit_marketing_marketing_index'       => array('trigger' => 'admin/controller/marketing/marketing/before',                'action' => 'extension/module/admin_quick_edit/marketing_marketing_index_hook'),
		'admin_module_admin_quick_edit_marketing_marketing_delete'      => array('trigger' => 'admin/controller/marketing/marketing/delete/before',         'action' => 'extension/module/admin_quick_edit/marketing_marketing_delete_hook'),
		'admin_module_admin_quick_edit_marketing_marketing_form'        => array('trigger' => 'admin/view/marketing/marketing_form/before',                 'action' => 'extension/module/admin_quick_edit/marketing_marketing_form_hook'),
		'admin_module_admin_quick_edit_sale_return_index'               => array('trigger' => 'admin/controller/sale/return/before',                        'action' => 'extension/module/admin_quick_edit/sale_return_index_hook'),
		'admin_module_admin_quick_edit_sale_return_delete'              => array('trigger' => 'admin/controller/sale/return/delete/before',                 'action' => 'extension/module/admin_quick_edit/sale_return_delete_hook'),
		'admin_module_admin_quick_edit_sale_return_form'                => array('trigger' => 'admin/view/sale/return_form/before',                         'action' => 'extension/module/admin_quick_edit/sale_return_form_hook'),
		'admin_module_admin_quick_edit_sale_voucher_index'              => array('trigger' => 'admin/controller/sale/voucher/before',                       'action' => 'extension/module/admin_quick_edit/sale_voucher_index_hook'),
		'admin_module_admin_quick_edit_sale_voucher_delete'             => array('trigger' => 'admin/controller/sale/voucher/delete/before',                'action' => 'extension/module/admin_quick_edit/sale_voucher_delete_hook'),
		'admin_module_admin_quick_edit_sale_voucher_form'               => array('trigger' => 'admin/view/sale/voucher_form/before',                        'action' => 'extension/module/admin_quick_edit/sale_voucher_form_hook'),
		'admin_module_admin_quick_edit_sale_voucher_theme_index'        => array('trigger' => 'admin/controller/sale/voucher_theme/before',                 'action' => 'extension/module/admin_quick_edit/sale_voucher_theme_index_hook'),
		'admin_module_admin_quick_edit_sale_voucher_theme_delete'       => array('trigger' => 'admin/controller/sale/voucher_theme/delete/before',          'action' => 'extension/module/admin_quick_edit/sale_voucher_theme_delete_hook'),
		'admin_module_admin_quick_edit_sale_voucher_theme_form'         => array('trigger' => 'admin/view/sale/voucher_theme_form/before',                  'action' => 'extension/module/admin_quick_edit/sale_voucher_theme_form_hook'),
		'admin_module_admin_quick_edit_catalog_information_index'       => array('trigger' => 'admin/controller/catalog/information/before',                'action' => 'extension/module/admin_quick_edit/catalog_information_index_hook'),
		'admin_module_admin_quick_edit_catalog_information_delete'      => array('trigger' => 'admin/controller/catalog/information/delete/before',         'action' => 'extension/module/admin_quick_edit/catalog_information_delete_hook'),
		'admin_module_admin_quick_edit_catalog_information_form'        => array('trigger' => 'admin/view/catalog/information_form/before',                 'action' => 'extension/module/admin_quick_edit/catalog_information_form_hook'),
		'admin_module_admin_quick_edit_catalog_review_index'            => array('trigger' => 'admin/controller/catalog/review/before',                     'action' => 'extension/module/admin_quick_edit/catalog_review_index_hook'),
		'admin_module_admin_quick_edit_catalog_review_delete'           => array('trigger' => 'admin/controller/catalog/review/delete/before',              'action' => 'extension/module/admin_quick_edit/catalog_review_delete_hook'),
		'admin_module_admin_quick_edit_catalog_review_form'             => array('trigger' => 'admin/view/catalog/review_form/before',                      'action' => 'extension/module/admin_quick_edit/catalog_review_form_hook'),
		'admin_module_admin_quick_edit_catalog_download_index'          => array('trigger' => 'admin/controller/catalog/download/before',                   'action' => 'extension/module/admin_quick_edit/catalog_download_index_hook'),
		'admin_module_admin_quick_edit_catalog_download_delete'         => array('trigger' => 'admin/controller/catalog/download/delete/before',            'action' => 'extension/module/admin_quick_edit/catalog_download_delete_hook'),
		'admin_module_admin_quick_edit_catalog_download_form'           => array('trigger' => 'admin/view/catalog/download_form/before',                    'action' => 'extension/module/admin_quick_edit/catalog_download_form_hook'),
		'admin_module_admin_quick_edit_catalog_manufacturer_index'      => array('trigger' => 'admin/controller/catalog/manufacturer/before',               'action' => 'extension/module/admin_quick_edit/catalog_manufacturer_index_hook'),
		'admin_module_admin_quick_edit_catalog_manufacturer_delete'     => array('trigger' => 'admin/controller/catalog/manufacturer/delete/before',        'action' => 'extension/module/admin_quick_edit/catalog_manufacturer_delete_hook'),
		'admin_module_admin_quick_edit_catalog_manufacturer_form'       => array('trigger' => 'admin/view/catalog/manufacturer_form/before',                'action' => 'extension/module/admin_quick_edit/catalog_manufacturer_form_hook'),
		'admin_module_admin_quick_edit_catalog_option_index'            => array('trigger' => 'admin/controller/catalog/option/before',                     'action' => 'extension/module/admin_quick_edit/catalog_option_index_hook'),
		'admin_module_admin_quick_edit_catalog_option_delete'           => array('trigger' => 'admin/controller/catalog/option/delete/before',              'action' => 'extension/module/admin_quick_edit/catalog_option_delete_hook'),
		'admin_module_admin_quick_edit_catalog_option_form'             => array('trigger' => 'admin/view/catalog/option_form/before',                      'action' => 'extension/module/admin_quick_edit/catalog_option_form_hook'),
		'admin_module_admin_quick_edit_catalog_attribute_group_index'   => array('trigger' => 'admin/controller/catalog/attribute_group/before',            'action' => 'extension/module/admin_quick_edit/catalog_attribute_group_index_hook'),
		'admin_module_admin_quick_edit_catalog_attribute_group_delete'  => array('trigger' => 'admin/controller/catalog/attribute_group/delete/before',     'action' => 'extension/module/admin_quick_edit/catalog_attribute_group_delete_hook'),
		'admin_module_admin_quick_edit_catalog_attribute_group_form'    => array('trigger' => 'admin/view/catalog/attribute_group_form/before',             'action' => 'extension/module/admin_quick_edit/catalog_attribute_group_form_hook'),
		'admin_module_admin_quick_edit_catalog_attribute_index'         => array('trigger' => 'admin/controller/catalog/attribute/before',                  'action' => 'extension/module/admin_quick_edit/catalog_attribute_index_hook'),
		'admin_module_admin_quick_edit_catalog_attribute_delete'        => array('trigger' => 'admin/controller/catalog/attribute/delete/before',           'action' => 'extension/module/admin_quick_edit/catalog_attribute_delete_hook'),
		'admin_module_admin_quick_edit_catalog_attribute_form'          => array('trigger' => 'admin/view/catalog/attribute_form/before',                   'action' => 'extension/module/admin_quick_edit/catalog_attribute_form_hook'),
		'admin_module_admin_quick_edit_catalog_filter_index'            => array('trigger' => 'admin/controller/catalog/filter/before',                     'action' => 'extension/module/admin_quick_edit/catalog_filter_index_hook'),
		'admin_module_admin_quick_edit_catalog_filter_delete'           => array('trigger' => 'admin/controller/catalog/filter/delete/before',              'action' => 'extension/module/admin_quick_edit/catalog_filter_delete_hook'),
		'admin_module_admin_quick_edit_catalog_filter_form'             => array('trigger' => 'admin/view/catalog/filter_form/before',                      'action' => 'extension/module/admin_quick_edit/catalog_filter_form_hook'),
		'admin_module_admin_quick_edit_catalog_recurring_index'         => array('trigger' => 'admin/controller/catalog/recurring/before',                  'action' => 'extension/module/admin_quick_edit/catalog_recurring_index_hook'),
		'admin_module_admin_quick_edit_catalog_recurring_delete'        => array('trigger' => 'admin/controller/catalog/recurring/delete/before',           'action' => 'extension/module/admin_quick_edit/catalog_recurring_delete_hook'),
		'admin_module_admin_quick_edit_catalog_recurring_copy'          => array('trigger' => 'admin/controller/catalog/recurring/copy/before',             'action' => 'extension/module/admin_quick_edit/catalog_recurring_copy_hook'),
		'admin_module_admin_quick_edit_catalog_recurring_form'          => array('trigger' => 'admin/view/catalog/recurring_form/before',                   'action' => 'extension/module/admin_quick_edit/catalog_recurring_form_hook'),
		'admin_module_admin_quick_edit_catalog_category_index'          => array('trigger' => 'admin/controller/catalog/category/before',                   'action' => 'extension/module/admin_quick_edit/catalog_category_index_hook'),
		'admin_module_admin_quick_edit_catalog_category_delete'         => array('trigger' => 'admin/controller/catalog/category/delete/before',            'action' => 'extension/module/admin_quick_edit/catalog_category_delete_hook'),
		'admin_module_admin_quick_edit_catalog_category_form'           => array('trigger' => 'admin/view/catalog/category_form/before',                    'action' => 'extension/module/admin_quick_edit/catalog_category_form_hook'),
		'admin_module_admin_quick_edit_catalog_product_index'           => array('trigger' => 'admin/controller/catalog/product/before',                    'action' => 'extension/module/admin_quick_edit/catalog_product_index_hook'),
		'admin_module_admin_quick_edit_catalog_product_delete'          => array('trigger' => 'admin/controller/catalog/product/delete/before',             'action' => 'extension/module/admin_quick_edit/catalog_product_delete_hook'),
		'admin_module_admin_quick_edit_catalog_product_copy'            => array('trigger' => 'admin/controller/catalog/product/copy/before',               'action' => 'extension/module/admin_quick_edit/catalog_product_copy_hook'),
		'admin_module_admin_quick_edit_catalog_product_form'            => array('trigger' => 'admin/view/catalog/product_form/before',                     'action' => 'extension/module/admin_quick_edit/catalog_product_form_hook'),
	);

	public function __construct($registry) {
		parent::__construct($registry);
		$this->config->load('aqep');
	}

	// Interpreter hook
	public function __call($name, $arguments) {
		if (method_exists($this, $name)) {
			// Forward call to existing method
			return call_user_func_array(array($this, $name), $arguments);
		} else {
			// Validate the call name and forward call to appropriate class and method
			if (substr_count($name, "__") == 2) {
				list($catalog, $controller, $method) = explode("__", $name);
				return $this->load->controller("extension/module/aqe/{$catalog}/{$controller}" . ($method ? "/{$method}" : ""), $arguments);
			} else {
				// Unknown method call
				$this->log->write("AQE PRO: unknown method call: " . $name);
			}
		}
	}

	public function index() {
		$this->load->language('extension/module/admin_quick_edit');

		$this->document->setTitle($this->language->get('extension_name'));

		$this->load->model('setting/setting');

		$ajax_request = isset($this->request->server['HTTP_X_REQUESTED_WITH']) && !empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && !$ajax_request && $this->validateForm($this->request->post)) {
			$original_settings = $this->model_setting_setting->getSetting('module_admin_quick_edit');

			foreach (self::$config_defaults as $setting => $default) {
				$value = $this->config->get($setting);
				if ($value === null) {
					$original_settings[$setting] = $default;
				}
			}

			foreach (self::$column_defaults as $page => $columns) {
				$page_conf = $this->config->get($page);

				if ($page_conf === null) {
					$page_conf = $value;
				}

				foreach ($columns as $column => $attributes) {
					if (!isset($page_conf[$column])) {
						$page_conf[$column] = $attributes;
					} else {
						foreach ($attributes as $key => $value) {
							if (!isset($page_conf[$column][$key])) {
								$page_conf[$column][$key] = $value;
							} else {
								switch ($key) {
									case 'display':
										$page_conf[$column][$key] = (isset($this->request->post['display'][$page][$column])) ? 1 : 0;
										break;
									case 'index':
										$page_conf[$column][$key] = (isset($this->request->post['index'][$page][$column])) ? $this->request->post['index'][$page][$column] : $value;
										break;
									case 'qe_status':
										$page_conf[$column][$key] = (isset($this->request->post['qe_status'][$page][$column])) ? 1 : 0;
										break;
									default:
										$page_conf[$column][$key] = $value;
										break;
								}
							}
						}

						foreach (array_diff(array_keys($page_conf[$column]), array_keys($columns[$column])) as $key) {
							unset($page_conf[$column]);
						}
					}
				}

				foreach (array_diff(array_keys($page_conf), array_keys($columns)) as $key) {
					unset($page_conf[$key]);
				}

				$this->request->post[$page] = $page_conf;
			}

			unset($this->request->post['index']);
			unset($this->request->post['display']);
			unset($this->request->post['qe_status']);

			$settings = array_merge($original_settings, $this->request->post);
			$settings['module_admin_quick_edit_installed_version'] = $original_settings['module_admin_quick_edit_installed_version'];

			$settings['module_admin_quick_edit_list_view_image_width'] = (int)$settings['module_admin_quick_edit_list_view_image_width'];
			$settings['module_admin_quick_edit_list_view_image_height'] = (int)$settings['module_admin_quick_edit_list_view_image_height'];

			$this->model_setting_setting->editSetting('module_admin_quick_edit', $settings);

			$this->session->data['success'] = $this->language->get('text_success_update');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		} else if ($this->request->server['REQUEST_METHOD'] == 'POST' && $ajax_request) {
			$response = array();

			if ($this->validateForm($this->request->post)) {
				$original_settings = $this->model_setting_setting->getSetting('module_admin_quick_edit');

				foreach (self::$config_defaults as $setting => $default) {
					$value = $this->config->get($setting);
					if ($value === null) {
						$original_settings[$setting] = $default;
					}
				}

				foreach (self::$column_defaults as $page => $columns) {
					$page_conf = $this->config->get($page);

					if ($page_conf === null) {
						$page_conf = $value;
					}

					foreach ($columns as $column => $attributes) {
						if (!isset($page_conf[$column])) {
							$page_conf[$column] = $attributes;
						} else {
							foreach ($attributes as $key => $value) {
								if (!isset($page_conf[$column][$key])) {
									$page_conf[$column][$key] = $value;
								} else {
									switch ($key) {
										case 'display':
											$page_conf[$column][$key] = (isset($this->request->post['display'][$page][$column])) ? 1 : 0;
											break;
										case 'index':
											$page_conf[$column][$key] = (isset($this->request->post['index'][$page][$column])) ? $this->request->post['index'][$page][$column] : $value;
											break;
										case 'qe_status':
											$page_conf[$column][$key] = (isset($this->request->post['qe_status'][$page][$column])) ? 1 : 0;
											break;
										default:
											$page_conf[$column][$key] = $value;
											break;
									}
								}
							}

							foreach (array_diff(array_keys($page_conf[$column]), array_keys($columns[$column])) as $key) {
								unset($page_conf[$column][$key]);
							}
						}
					}

					foreach (array_diff(array_keys($page_conf), array_keys($columns)) as $key) {
						unset($page_conf[$key]);
					}

					$this->request->post[$page] = $page_conf;
				}

				unset($this->request->post['index']);
				unset($this->request->post['display']);
				unset($this->request->post['qe_status']);

				$settings = array_merge($original_settings, $this->request->post);
				$settings['module_admin_quick_edit_installed_version'] = $original_settings['module_admin_quick_edit_installed_version'];

				$settings['module_admin_quick_edit_list_view_image_width'] = (int)$settings['module_admin_quick_edit_list_view_image_width'];
				$settings['module_admin_quick_edit_list_view_image_height'] = (int)$settings['module_admin_quick_edit_list_view_image_height'];

				if ((int)$original_settings['module_admin_quick_edit_status'] != (int)$this->request->post['module_admin_quick_edit_status']
					 || (int)$original_settings['module_admin_quick_edit_override_menu_entry'] != (int)$this->request->post['module_admin_quick_edit_override_menu_entry']
					 || (int)$original_settings['module_admin_quick_edit_catalog_categories_status'] != (int)$this->request->post['module_admin_quick_edit_catalog_categories_status']
					 || (int)$original_settings['module_admin_quick_edit_catalog_products_status'] != (int)$this->request->post['module_admin_quick_edit_catalog_products_status']
					 || (int)$original_settings['module_admin_quick_edit_catalog_recurrings_status'] != (int)$this->request->post['module_admin_quick_edit_catalog_recurrings_status']
					 || (int)$original_settings['module_admin_quick_edit_catalog_filters_status'] != (int)$this->request->post['module_admin_quick_edit_catalog_filters_status']
					 || (int)$original_settings['module_admin_quick_edit_catalog_attributes_status'] != (int)$this->request->post['module_admin_quick_edit_catalog_attributes_status']
					 || (int)$original_settings['module_admin_quick_edit_catalog_attribute_groups_status'] != (int)$this->request->post['module_admin_quick_edit_catalog_attribute_groups_status']
					 || (int)$original_settings['module_admin_quick_edit_catalog_options_status'] != (int)$this->request->post['module_admin_quick_edit_catalog_options_status']
					 || (int)$original_settings['module_admin_quick_edit_catalog_manufacturers_status'] != (int)$this->request->post['module_admin_quick_edit_catalog_manufacturers_status']
					 || (int)$original_settings['module_admin_quick_edit_catalog_downloads_status'] != (int)$this->request->post['module_admin_quick_edit_catalog_downloads_status']
					 || (int)$original_settings['module_admin_quick_edit_catalog_reviews_status'] != (int)$this->request->post['module_admin_quick_edit_catalog_reviews_status']
					 || (int)$original_settings['module_admin_quick_edit_catalog_information_status'] != (int)$this->request->post['module_admin_quick_edit_catalog_information_status']
					 || (int)$original_settings['module_admin_quick_edit_customer_customers_status'] != (int)$this->request->post['module_admin_quick_edit_customer_customers_status']
					 || (int)$original_settings['module_admin_quick_edit_marketing_coupons_status'] != (int)$this->request->post['module_admin_quick_edit_marketing_coupons_status']
					 || (int)$original_settings['module_admin_quick_edit_marketing_marketing_status'] != (int)$this->request->post['module_admin_quick_edit_marketing_marketing_status']
					 || (int)$original_settings['module_admin_quick_edit_sale_returns_status'] != (int)$this->request->post['module_admin_quick_edit_sale_returns_status']
					 || (int)$original_settings['module_admin_quick_edit_sale_vouchers_status'] != (int)$this->request->post['module_admin_quick_edit_sale_vouchers_status']
					 || (int)$original_settings['module_admin_quick_edit_sale_voucher_themes_status'] != (int)$this->request->post['module_admin_quick_edit_sale_voucher_themes_status']
					 || (int)$original_settings['module_admin_quick_edit_design_seo_urls_status'] != (int)$this->request->post['module_admin_quick_edit_design_seo_urls_status'])
				{
					$response['reload'] = true;
					$this->session->data['success'] = $this->language->get('text_success_update');
				}

				$this->model_setting_setting->editSetting('module_admin_quick_edit', $settings);

				$response['success'] = true;
				$response['msg'] = $this->language->get("text_success_update");
			} else {
				if (!$this->checkVersion()) {
					$response['reload'] = true;
				}
				$response = array_merge($response, array("error" => true), array("errors" => $this->error), array("alerts" => $this->alert));
			}

			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($response, JSON_UNESCAPED_SLASHES));
			return;
		}

		$data['heading_title'] = $this->language->get('extension_name');
		$data['text_other_extensions'] = sprintf($this->language->get('text_other_extensions'), OTHER_EXTENSIONS);

		$data['ext_name'] = EXTENSION_NAME;
		$data['ext_version'] = EXTENSION_VERSION;
		$data['ext_id'] = EXTENSION_ID;
		$data['ext_compatibility'] = EXTENSION_COMPATIBILITY;
		$data['ext_store_url'] = EXTENSION_STORE_URL;
		$data['ext_rate_url'] = EXTENSION_RATE_URL;
		$data['ext_purchase_url'] = EXTENSION_PURCHASE_URL;
		$data['ext_support_email'] = EXTENSION_SUPPORT_EMAIL;
		$data['ext_support_link'] = EXTENSION_SUPPORT_LINK;
		$data['ext_support_forum'] = EXTENSION_SUPPORT_FORUM;
		$data['other_extensions_url'] = OTHER_EXTENSIONS;
		$data['oc_version'] = VERSION;
		$data['php_version'] = phpversion();
		$data['installed_extensions'] = (array)$this->config->get('aqe_pro_extensions');

		$data['server_ips'] = array($_SERVER['SERVER_ADDR'] ? $_SERVER['SERVER_ADDR'] : $_SERVER['LOCAL_ADDR']);

		if (ini_get('allow_url_fopen')) {
			// $external_content = file_get_contents('http://checkip.dyndns.com/');
			$external_content = file_get_contents('https://ipecho.net/plain');
			if ($external_content !== FALSE) {
				// preg_match('/Current IP Address: \[?([:.0-9a-fA-F]+)\]?/', $external_content, $match);
				if (preg_match('/([:.0-9a-fA-F]+)/', $external_content, $match)) {
					$data['server_ips'][] = $match[1];
				}
			}
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
			'active'    => false
		);

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_extension'),
			'href'      => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true),
			'active'    => false
		);

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('extension_name'),
			'href'      => $this->url->link('extension/module/admin_quick_edit', 'user_token=' . $this->session->data['user_token'], true),
			'active'    => true
		);

		$data['save'] = $this->url->link('extension/module/admin_quick_edit', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
		$data['upgrade'] = $this->url->link('extension/module/admin_quick_edit/upgrade', 'user_token=' . $this->session->data['user_token'], true);
		$data['extension_installer'] = $this->url->link('marketplace/installer', 'user_token=' . $this->session->data['user_token'], true);
		$data['modifications'] = $this->url->link('marketplace/modification', 'user_token=' . $this->session->data['user_token'], true);
		$data['api'] = $this->url->link('user/api', 'user_token=' . $this->session->data['user_token'], true);
		$data['events'] = $this->url->link('marketplace/event', 'user_token=' . $this->session->data['user_token'], true);
		$data['services'] = html_entity_decode($this->url->link('extension/module/admin_quick_edit/services', 'user_token=' . $this->session->data['user_token'], true));

		if (!$this->checkPrerequisites()) {
			$this->showErrorPage($data);
			return;
		}

		$this->checkVersion(true);

		$data['update_pending'] = !$this->checkVersion();

		if (!$data['update_pending']) {
			$this->updateEventHooks();
		}

		$data['ssl'] = (
				(int)$this->config->get('config_secure') ||
				isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ||
				!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' ||
				!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on'
			) ? 's' : '';

		# Loop through all settings for the post/config values
		foreach (array_keys(self::$config_defaults) as $setting) {
			if (isset($this->request->post[$setting])) {
				$data[$setting] = $this->request->post[$setting];
			} else {
				$data[$setting] = $this->config->get($setting);
				if ($data[$setting] === null) {
					if (!isset($this->alert['warning']['unsaved']) && $this->checkVersion())  {
						$this->alert['warning']['unsaved'] = $this->language->get('error_unsaved_settings');
					}
					if (isset(self::$config_defaults[$setting])) {
						$data[$setting] = self::$config_defaults[$setting];
					}
				}
			}
		}

		// Check for multistore setup
		$this->load->model('setting/store');

		$multistore = $this->model_setting_store->getTotalStores();

		$data['installed_version'] = $this->installedVersion();

		$data['sorts'] = array();

		foreach (self::$column_defaults as $page => $columns) {
			$conf = $this->config->get($page);
			if (!is_array($conf)) {
				if (!isset($this->alert['warning']['unsaved']) && $this->checkVersion())  {
					$this->alert['warning']['unsaved'] = $this->language->get('error_unsaved_settings');
				}
				$conf = $columns;
			}

			foreach ($columns as $column => $attributes) {
				if (!isset($conf[$column])) {
					if (!isset($this->alert['warning']['unsaved']) && $this->checkVersion())  {
						$this->alert['warning']['unsaved'] = $this->language->get('error_unsaved_settings');
					}
					$conf[$column] = $attributes;
				}

				foreach ($attributes as $key => $value) {
					if (!isset($conf[$column][$key])) {
						if (!isset($this->alert['warning']['unsaved']) && $this->checkVersion())  {
							$this->alert['warning']['unsaved'] = $this->language->get('error_unsaved_settings');
						}
						$conf[$column][$key] = $value;
					}
					switch ($key) {
						case 'display':
						case 'qe_status':
						case 'index':
							break;
						default:
							if ($conf[$column][$key] != $value) {
								if (!isset($this->alert['warning']['unsaved']) && $this->checkVersion())  {
									$this->alert['warning']['unsaved'] = $this->language->get('error_unsaved_settings');
								}
							}
							break;
					}
				}

				if (array_diff(array_keys($conf[$column]), array_keys($columns[$column])) && !isset($this->alert['warning']['unsaved']) && $this->checkVersion()) {
					$this->alert['warning']['unsaved'] = $this->language->get('error_unsaved_settings');
				}

				$conf[$column]['name'] = $this->language->get('txt_' . $column);

				if (!empty($attributes['sort'])) {
					$data['sorts'][$page][$column]['name'] = $conf[$column]['name'];
					$data['sorts'][$page][$column]['value'] = $attributes['sort'];
				}

				if ($column == 'view_in_store' && !$multistore) {
					unset($conf[$column]);
				}
			}

			if (array_diff(array_keys($conf), array_keys($columns)) && !isset($this->alert['warning']['unsaved']) && $this->checkVersion()) {
				$this->alert['warning']['unsaved'] = $this->language->get('error_unsaved_settings');
			}

			uasort($conf, 'column_sort');
			$data[$page] = $conf;
		}

		if (isset($this->session->data['error'])) {
			$this->error = $this->session->data['error'];

			unset($this->session->data['error']);
		}

		if (isset($this->error['warning'])) {
			$this->alert['warning']['warning'] = $this->error['warning'];
		}

		if (isset($this->error['error'])) {
			$this->alert['error']['error'] = $this->error['error'];
		}

		if (isset($this->session->data['success'])) {
			$this->alert['success']['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		}

		$this->document->addStyle('view/stylesheet/aqe/module.min.css?v=' . EXTENSION_VERSION);

		$this->document->addScript('view/javascript/aqe/module.min.js?v=' . EXTENSION_VERSION);

		$data['errors'] = $this->error;

		$data['user_token'] = $this->session->data['user_token'];

		$data['alerts'] = $this->alert;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$template = 'extension/module/admin_quick_edit';
		$this->response->setOutput($this->load->view($template, $data));
	}

	public function install() {
		$this->load->language('extension/module/admin_quick_edit');

		$this->registerEventHooks();

		$this->load->model('setting/setting');
		$this->model_setting_setting->editSetting('module_admin_quick_edit', array_merge(self::$config_defaults, self::$column_defaults));
	}

	public function uninstall() {
		$this->load->language('extension/module/admin_quick_edit');

		$this->removeEventHooks();

		$this->load->model('setting/setting');
		$this->model_setting_setting->deleteSetting('module_admin_quick_edit');
	}

	public function upgrade() {
		$this->load->language('extension/module/admin_quick_edit');

		$ajax_request = isset($this->request->server['HTTP_X_REQUESTED_WITH']) && !empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

		$response = array();

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validateUpgrade()) {
			$this->load->model('setting/setting');

			$settings = array();

			// Go over all settings, add new values and remove old ones
			foreach (self::$config_defaults as $setting => $default) {
				$value = $this->config->get($setting);
				if ($value === null) {
					$settings[$setting] = $default;
				} else {
					$settings[$setting] = $value;
				}
			}

			foreach (self::$column_defaults as $page => $columns) {
				$setting = array();

				$conf = $this->config->get($page);

				if ($conf === null || !is_array($conf)) {
					$conf = $columns;
				}

				foreach ($columns as $column => $values) {
					$setting[$column] = array();

					foreach ($values as $key => $value) {
						if (!isset($conf[$column][$key])) {
							$setting[$column][$key] = $value;
						} else {
							$setting[$column][$key] = $conf[$column][$key];
						}
					}
				}

				$settings[$page] = $setting;
			}

			$settings['module_admin_quick_edit_installed_version'] = EXTENSION_VERSION;

			$this->model_setting_setting->editSetting('module_admin_quick_edit', $settings);

			$this->session->data['success'] = sprintf($this->language->get('text_success_upgrade'), EXTENSION_VERSION);
			$this->alert['success']['upgrade'] = sprintf($this->language->get('text_success_upgrade'), EXTENSION_VERSION);

			$response['success'] = true;
			$response['reload'] = true;
		}

		$response = array_merge($response, array("errors" => $this->error), array("alerts" => $this->alert));

		if (!$ajax_request) {
			$this->session->data['errors'] = $this->error;
			$this->session->data['alerts'] = $this->alert;
			$this->response->redirect($this->url->link('extension/module/admin_quick_edit', 'user_token=' . $this->session->data['user_token'], true));
		} else {
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($response, JSON_UNESCAPED_SLASHES));
			return;
		}
	}

	public function services() {
		$this->load->language('extension/module/admin_quick_edit');

		$services = base64_decode($this->config->get('module_admin_quick_edit_services'));
		$response = json_decode($services, true);
		$force = isset($this->request->get['force']) && (int)$this->request->get['force'];

		if ($response && isset($response['expires']) && $response['expires'] >= strtotime("now") && !$force) {
			$response['cached'] = true;
		} else {
			$url = "https://www.opencart.ee/services/?eid=" . EXTENSION_ID . "&info=true&general=true&currency=" . $this->config->get('config_currency');
			$hostname = (!empty($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : '' ;

			if (function_exists('curl_init')) {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_HEADER, false);
				curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
				curl_setopt($ch, CURLOPT_TIMEOUT, 60);
				curl_setopt($ch, CURLOPT_USERAGENT, base64_encode("curl " . EXTENSION_ID));
				curl_setopt($ch, CURLOPT_REFERER, $hostname);
				$json = curl_exec($ch);
			} else {
				$json = false;
			}

			if ($json !== false) {
				$this->load->model('setting/setting');
				$settings = $this->model_setting_setting->getSetting('module_admin_quick_edit');
				$settings['module_admin_quick_edit_services'] = base64_encode($json);
				$this->model_setting_setting->editSetting('module_admin_quick_edit', $settings);
				$response = json_decode($json, true);
			} else {
				$response = array();
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($response, JSON_UNESCAPED_SLASHES));
	}

	// Image
	public function image() {
		$this->load->model('tool/image');

		if (isset($this->request->get['size'])) {
			$width = $height = (int)$this->request->get['size'];
		} else if (isset($this->request->get['width']) && isset($this->request->get['height'])) {
			$width = (int)$this->request->get['width'];
			$height = (int)$this->request->get['height'];
		} else {
			$width = $height = 100;
		}

		if (isset($this->request->get['image'])) {
			$this->response->setOutput($this->model_tool_image->resize(html_entity_decode($this->request->get['image'], ENT_QUOTES, 'UTF-8'), $width, $height));
		}
	}

	// Event hooks
	public function menu_hook(&$route, &$data) {
		if ($this->config->get('module_admin_quick_edit_status') && !(int)$this->config->get('module_admin_quick_edit_override_menu_entry') && $this->user->hasPermission('access', 'extension/module/admin_quick_edit')) {
			$this->load->language('extension/module/admin_quick_edit');

			$menu_hooks = array(
				'catalog' => array(
					'category' => 'categories',
					'product' => 'products',
					'recurring' => 'recurrings',
					'filter' => 'filters',
					'attribute' => 'attributes',
					'attribute_group' => 'attribute_groups',
					'option' => 'options',
					'manufacturer' => 'manufacturers',
					'download' => 'downloads',
					'review' => 'reviews',
					'information' => 'information',
				),
				'design' => array(
					'seo_url' => 'seo_urls',
				),
				'sale' => array(
					'return' => 'returns',
					'voucher' => 'vouchers',
					'voucher_theme' => 'voucher_themes',
				),
				'customer' => array(
					'customer' => 'customers',
				),
				'marketing' => array(
					'marketing' => 'marketing',
					'coupon' => 'coupons',
				)
			);

			foreach ($data['menus'] as $l1_key => $l1_menu) {
				foreach ($menu_hooks as $catalog => $items) {
					if ($l1_menu['id'] == "menu-{$catalog}") {
						$children = array();
						foreach ($l1_menu['children'] as $l2_key => $l2_menu) {
							if ($l2_menu['children']) {
								$sub_children = array();
								foreach ($l2_menu['children'] as $l3_key => $l3_menu) {
									$new_entry = null;

									foreach ($items as $controller => $setting) {
										if ($this->config->get("module_admin_quick_edit_{$catalog}_{$setting}_status") && strpos($l3_menu['href'], "route={$catalog}/{$controller}&") !== FALSE) {
											$new_entry = array(
												'name'      => $this->language->get("menu_{$catalog}_{$controller}"),
												'href'      => $this->url->link("extension/module/admin_quick_edit/{$catalog}__{$controller}__", 'user_token=' . $this->session->data['user_token'], true),
												'children'  => array()
											);
										}
									}

									$sub_children[] = $l3_menu;
									if ($new_entry) $sub_children[] = $new_entry;
								}
								$l2_menu['children'] = $sub_children;
								$children[] = $l2_menu;
							} else {
								$new_entry = null;

								foreach ($items as $controller => $setting) {
									if ($this->config->get("module_admin_quick_edit_{$catalog}_{$setting}_status") && strpos($l2_menu['href'], "route={$catalog}/{$controller}&") !== FALSE) {
										$new_entry = array(
											'name'      => $this->language->get("menu_{$catalog}_{$controller}"),
											'href'      => $this->url->link("extension/module/admin_quick_edit/{$catalog}__{$controller}__", 'user_token=' . $this->session->data['user_token'], true),
											'children'  => array()
										);
									}
								}

								$children[] = $l2_menu;
								if ($new_entry) $children[] = $new_entry;
							}
						}
						$data['menus'][$l1_key]['children'] = $children;
					}
				}
			}
		}
	}

	public function design_seo_url_index_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_design_seo_urls_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']) || !empty($this->session->data['aqer']))) {
			unset($this->session->data['aqer']);
			$route = 'extension/module/admin_quick_edit/design__seo_url__';
		}
	}

	public function design_seo_url_delete_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_design_seo_urls_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']))) {
			$route = 'extension/module/admin_quick_edit/design__seo_url__delete';
		}
	}

	public function design_seo_url_form_hook(&$route, &$data) {
		if ($this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_design_seo_urls_status') && isset($this->request->get['aqer'])) {
			$this->session->data['aqer'] = 1;
		}
	}

	public function customer_customer_index_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_customer_customers_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']) || !empty($this->session->data['aqer']))) {
			unset($this->session->data['aqer']);
			$route = 'extension/module/admin_quick_edit/customer__customer__';
		}
	}

	public function customer_customer_delete_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_customer_customers_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']))) {
			$route = 'extension/module/admin_quick_edit/customer__customer__delete';
		}
	}

	public function customer_customer_form_hook(&$route, &$data) {
		if ($this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_customer_customers_status') && isset($this->request->get['aqer'])) {
			$this->session->data['aqer'] = 1;
		}
	}

	public function marketing_coupon_index_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_marketing_coupons_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']) || !empty($this->session->data['aqer']))) {
			unset($this->session->data['aqer']);
			$route = 'extension/module/admin_quick_edit/marketing__coupon__';
		}
	}

	public function marketing_coupon_delete_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_marketing_coupons_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']))) {
			$route = 'extension/module/admin_quick_edit/marketing__coupon__delete';
		}
	}

	public function marketing_coupon_form_hook(&$route, &$data) {
		if ($this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_marketing_coupons_status') && isset($this->request->get['aqer'])) {
			$this->session->data['aqer'] = 1;
		}
	}

	public function marketing_marketing_index_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_marketing_marketing_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']) || !empty($this->session->data['aqer']))) {
			unset($this->session->data['aqer']);
			$route = 'extension/module/admin_quick_edit/marketing__marketing__';
		}
	}

	public function marketing_marketing_delete_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_marketing_marketing_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']))) {
			$route = 'extension/module/admin_quick_edit/marketing__marketing__delete';
		}
	}

	public function marketing_marketing_form_hook(&$route, &$data) {
		if ($this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_marketing_marketing_status') && isset($this->request->get['aqer'])) {
			$this->session->data['aqer'] = 1;
		}
	}

	public function sale_return_index_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_sale_returns_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']) || !empty($this->session->data['aqer']))) {
			unset($this->session->data['aqer']);
			$route = 'extension/module/admin_quick_edit/sale__return__';
		}
	}

	public function sale_return_delete_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_sale_returns_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']))) {
			$route = 'extension/module/admin_quick_edit/sale__return__delete';
		}
	}

	public function sale_return_form_hook(&$route, &$data) {
		if ($this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_sale_returns_status') && isset($this->request->get['aqer'])) {
			$this->session->data['aqer'] = 1;
		}
	}

	public function sale_voucher_index_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_sale_vouchers_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']) || !empty($this->session->data['aqer']))) {
			unset($this->session->data['aqer']);
			$route = 'extension/module/admin_quick_edit/sale__voucher__';
		}
	}

	public function sale_voucher_delete_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_sale_vouchers_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']))) {
			$route = 'extension/module/admin_quick_edit/sale__voucher__delete';
		}
	}

	public function sale_voucher_form_hook(&$route, &$data) {
		if ($this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_sale_vouchers_status') && isset($this->request->get['aqer'])) {
			$this->session->data['aqer'] = 1;
		}
	}

	public function sale_voucher_theme_index_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_sale_voucher_themes_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']) || !empty($this->session->data['aqer']))) {
			unset($this->session->data['aqer']);
			$route = 'extension/module/admin_quick_edit/sale__voucher_theme__';
		}
	}

	public function sale_voucher_theme_delete_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_sale_voucher_themes_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']))) {
			$route = 'extension/module/admin_quick_edit/sale__voucher_theme__delete';
		}
	}

	public function sale_voucher_theme_form_hook(&$route, &$data) {
		if ($this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_sale_voucher_themes_status') && isset($this->request->get['aqer'])) {
			$this->session->data['aqer'] = 1;
		}
	}

	public function catalog_information_index_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_information_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']) || !empty($this->session->data['aqer']))) {
			unset($this->session->data['aqer']);
			$route = 'extension/module/admin_quick_edit/catalog__information__';
		}
	}

	public function catalog_information_delete_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_information_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']))) {
			$route = 'extension/module/admin_quick_edit/catalog__information__delete';
		}
	}

	public function catalog_information_form_hook(&$route, &$data) {
		if ($this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_information_status') && isset($this->request->get['aqer'])) {
			$this->session->data['aqer'] = 1;
		}
	}

	public function catalog_review_index_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_reviews_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']) || !empty($this->session->data['aqer']))) {
			unset($this->session->data['aqer']);
			$route = 'extension/module/admin_quick_edit/catalog__review__';
		}
	}

	public function catalog_review_delete_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_reviews_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']))) {
			$route = 'extension/module/admin_quick_edit/catalog__review__delete';
		}
	}

	public function catalog_review_form_hook(&$route, &$data) {
		if ($this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_reviews_status') && isset($this->request->get['aqer'])) {
			$this->session->data['aqer'] = 1;
		}
	}

	public function catalog_download_index_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_downloads_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']) || !empty($this->session->data['aqer']))) {
			unset($this->session->data['aqer']);
			$route = 'extension/module/admin_quick_edit/catalog__download__';
		}
	}

	public function catalog_download_delete_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_downloads_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']))) {
			$route = 'extension/module/admin_quick_edit/catalog__download__delete';
		}
	}

	public function catalog_download_form_hook(&$route, &$data) {
		if ($this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_downloads_status') && isset($this->request->get['aqer'])) {
			$this->session->data['aqer'] = 1;
		}
	}

	public function catalog_manufacturer_index_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_manufacturers_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']) || !empty($this->session->data['aqer']))) {
			unset($this->session->data['aqer']);
			$route = 'extension/module/admin_quick_edit/catalog__manufacturer__';
		}
	}

	public function catalog_manufacturer_delete_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_manufacturers_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']))) {
			$route = 'extension/module/admin_quick_edit/catalog__manufacturer__delete';
		}
	}

	public function catalog_manufacturer_form_hook(&$route, &$data) {
		if ($this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_manufacturers_status') && isset($this->request->get['aqer'])) {
			$this->session->data['aqer'] = 1;
		}
	}

	public function catalog_option_index_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_options_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']) || !empty($this->session->data['aqer']))) {
			unset($this->session->data['aqer']);
			$route = 'extension/module/admin_quick_edit/catalog__option__';
		}
	}

	public function catalog_option_delete_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_options_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']))) {
			$route = 'extension/module/admin_quick_edit/catalog__option__delete';
		}
	}

	public function catalog_option_form_hook(&$route, &$data) {
		if ($this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_options_status') && isset($this->request->get['aqer'])) {
			$this->session->data['aqer'] = 1;
		}
	}

	public function catalog_attribute_group_index_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_attribute_groups_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']) || !empty($this->session->data['aqer']))) {
			unset($this->session->data['aqer']);
			$route = 'extension/module/admin_quick_edit/catalog__attribute_group__';
		}
	}

	public function catalog_attribute_group_delete_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_attribute_groups_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']))) {
			$route = 'extension/module/admin_quick_edit/catalog__attribute_group__delete';
		}
	}

	public function catalog_attribute_group_form_hook(&$route, &$data) {
		if ($this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_attribute_groups_status') && isset($this->request->get['aqer'])) {
			$this->session->data['aqer'] = 1;
		}
	}

	public function catalog_attribute_index_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_attributes_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']) || !empty($this->session->data['aqer']))) {
			unset($this->session->data['aqer']);
			$route = 'extension/module/admin_quick_edit/catalog__attribute__';
		}
	}

	public function catalog_attribute_delete_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_attributes_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']))) {
			$route = 'extension/module/admin_quick_edit/catalog__attribute__delete';
		}
	}

	public function catalog_attribute_form_hook(&$route, &$data) {
		if ($this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_attributes_status') && isset($this->request->get['aqer'])) {
			$this->session->data['aqer'] = 1;
		}
	}

	public function catalog_filter_index_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_filters_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']) || !empty($this->session->data['aqer']))) {
			unset($this->session->data['aqer']);
			$route = 'extension/module/admin_quick_edit/catalog__filter__';
		}
	}

	public function catalog_filter_delete_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_filters_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']))) {
			$route = 'extension/module/admin_quick_edit/catalog__filter__delete';
		}
	}

	public function catalog_filter_form_hook(&$route, &$data) {
		if ($this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_filters_status') && isset($this->request->get['aqer'])) {
			$this->session->data['aqer'] = 1;
		}
	}

	public function catalog_recurring_index_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_recurrings_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']) || !empty($this->session->data['aqer']))) {
			unset($this->session->data['aqer']);
			$route = 'extension/module/admin_quick_edit/catalog__recurring__';
		}
	}

	public function catalog_recurring_delete_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_recurrings_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']))) {
			$route = 'extension/module/admin_quick_edit/catalog__recurring__delete';
		}
	}

	public function catalog_recurring_copy_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_recurrings_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']))) {
			$route = 'extension/module/admin_quick_edit/catalog__recurring__copy';
		}
	}

	public function catalog_recurring_form_hook(&$route, &$data) {
		if ($this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_recurrings_status') && isset($this->request->get['aqer'])) {
			$this->session->data['aqer'] = 1;
		}
	}

	public function catalog_category_index_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_categories_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']) || !empty($this->session->data['aqer']))) {
			unset($this->session->data['aqer']);
			$route = 'extension/module/admin_quick_edit/catalog__category__';
		}
	}

	public function catalog_category_delete_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_categories_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']))) {
			$route = 'extension/module/admin_quick_edit/catalog__category__delete';
		}
	}

	public function catalog_category_form_hook(&$route, &$data) {
		if ($this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_categories_status') && isset($this->request->get['aqer'])) {
			$this->session->data['aqer'] = 1;
		}
	}

	public function catalog_product_index_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_products_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']) || !empty($this->session->data['aqer']))) {
			unset($this->session->data['aqer']);
			$route = 'extension/module/admin_quick_edit/catalog__product__';
		}
	}

	public function catalog_product_delete_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_products_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']))) {
			$route = 'extension/module/admin_quick_edit/catalog__product__delete';
		}
	}

	public function catalog_product_copy_hook(&$route, &$data) {
		if ((int)$this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_products_status') && ((int)$this->config->get('module_admin_quick_edit_override_menu_entry') || isset($this->request->get['aqer']))) {
			$route = 'extension/module/admin_quick_edit/catalog__product__copy';
		}
	}

	public function catalog_product_form_hook(&$route, &$data) {
		if ($this->config->get('module_admin_quick_edit_status') && (int)$this->config->get('module_admin_quick_edit_catalog_products_status') && isset($this->request->get['aqer'])) {
			$this->session->data['aqer'] = 1;
		}
	}

	protected function showErrorPage($data = array()) {
		$this->document->addStyle('view/stylesheet/aqe/module.min.css?v=' . EXTENSION_VERSION);

		$data['alerts'] = $this->alert;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$template = 'extension/module/admin_quick_edit_error';

		$this->response->setOutput($this->load->view($template, $data));
	}

	// Private methods
	private function registerEventHooks() {
		$this->load->model('extension/module/admin_quick_edit');
		$this->load->model('setting/event');

		if (isset($this->model_extension_module_admin_quick_edit->getEventByCodeTriggerAction) && is_callable($this->model_extension_module_admin_quick_edit->getEventByCodeTriggerAction)) {
			foreach (self::$event_hooks as $code => $hook) {
				$event = $this->model_extension_module_admin_quick_edit->getEventByCodeTriggerAction($code, $hook['trigger'], $hook['action']);

				if (!$event) {
					$this->model_setting_event->addEvent($code, $hook['trigger'], $hook['action']);
				}
			}
		} else {
			$this->alert['warning']['ocmod'] = $this->language->get('error_ocmod_script');
		}
	}

	private function removeEventHooks() {
		$this->load->model('setting/event');

		foreach (self::$event_hooks as $code => $hook) {
			$this->model_setting_event->deleteEventByCode($code);
		}
	}

	private function updateEventHooks() {
		$this->load->model('extension/module/admin_quick_edit');
		$this->load->model('setting/event');

		if (isset($this->model_extension_module_admin_quick_edit->getEventByCodeTriggerAction) && is_callable($this->model_extension_module_admin_quick_edit->getEventByCodeTriggerAction)) {
			foreach (self::$event_hooks as $code => $hook) {
				$event = $this->model_extension_module_admin_quick_edit->getEventByCodeTriggerAction($code, $hook['trigger'], $hook['action']);

				if (!$event) {
					$this->model_setting_event->addEvent($code, $hook['trigger'], $hook['action']);

					if (empty($this->alert['success']['hooks_updated'])) {
						$this->alert['success']['hooks_updated'] = $this->language->get('text_success_hooks_update');
					}
				}
			}

			// Delete old triggers
			$query = $this->db->query("SELECT `code` FROM " . DB_PREFIX . "event WHERE `code` LIKE 'admin_module_admin_quick_edit_%'");
			$events = array_keys(self::$event_hooks);

			foreach ($query->rows as $row) {
				if (!in_array($row['code'], $events)) {
					$this->model_setting_event->deleteEventByCode($row['code']);

					if (empty($this->alert['success']['hooks_updated'])) {
						$this->alert['success']['hooks_updated'] = $this->language->get('text_success_hooks_update');
					}
				}
			}
		} else {
			$this->alert['warning']['ocmod'] = $this->language->get('error_ocmod_script');
		}
	}

	protected function checkPrerequisites() {
		$errors = false;

		$this->load->language('extension/module/admin_quick_edit', 'aqe');

		if (!$this->config->get('aqe_pro_ocmod_script_working')) {
			$errors = true;
			$this->alert['error']['ocmod'] = $this->language->get('aqe')->get('error_ocmod_script');
		} else if ($this->checkVersion() && $this->installedVersion() != $this->config->get('aqe_pro_version')) {
			$this->alert['warning']['ocmod_cache'] = sprintf($this->language->get('aqe')->get('error_ocmod_cache'), $this->url->link('marketplace/modification/refresh', 'user_token=' . $this->session->data['user_token'], true));
		}

		if (version_compare(phpversion(), EXTENSION_MIN_PHP_VERSION) < 0) {
			$errors = true;
			$this->alert['error']['php'] = sprintf($this->language->get('aqe')->get('error_php_version'), phpversion(), EXTENSION_MIN_PHP_VERSION);
		}

		return !$errors;
	}

	protected function checkVersion($display_error = false) {
		$errors = false;

		$installed_version = $this->installedVersion();

		if ($installed_version != EXTENSION_VERSION) {
			$errors = true;

			if ($display_error) {
				$this->alert['info']['version'] = sprintf($this->language->get('error_version'), EXTENSION_VERSION);
			}
		}

		return !$errors;
	}

	private function checkModulePermission() {
		$errors = false;

		if (!$this->user->hasPermission('modify', 'extension/module/admin_quick_edit')) {
			$errors = true;
			$this->alert['error']['permission'] = $this->language->get('error_permission');
		}

		return $errors;
	}

	private function validate() {
		$errors = $this->checkModulePermission();

		if (!$errors) {
			return $this->checkPrerequisites() && $this->checkVersion(true);
		} else {
			return false;
		}
	}

	private function validateUpgrade() {
		$errors = $this->checkModulePermission();

		return !$errors;
	}

	private function validateForm($data) {
		$errors = false;

		if (!$data['module_admin_quick_edit_list_view_image_width'] || !is_numeric($this->request->post['module_admin_quick_edit_list_view_image_width']) || (int)$this->request->post['module_admin_quick_edit_list_view_image_width'] < 1) {
			$errors = true;
			$this->error['list_view_image_width'] = $this->language->get('error_image_width');
		}

		if (!$data['module_admin_quick_edit_list_view_image_height'] || !is_numeric($this->request->post['module_admin_quick_edit_list_view_image_height']) || (int)$this->request->post['module_admin_quick_edit_list_view_image_height'] < 1) {
			$errors = true;
			$this->error['list_view_image_height'] = $this->language->get('error_image_height');
		}

		if ($errors) {
			$errors = true;
			$this->alert['warning']['warning'] = $this->language->get('error_warning');
		}

		if (!$errors) {
			return $this->validate();
		} else {
			return false;
		}
	}

	protected function installedVersion() {
		$installed_version = $this->config->get('module_admin_quick_edit_installed_version');
		return $installed_version ? $installed_version : '6.0.0';
	}
}

/**
  * Sort columns by index key
  *
  **/
if (!function_exists('column_sort')) {
    function column_sort($a, $b) {

        if ($a['index'] == $b['index']) {
            return 0;
        }
        return ($a['index'] < $b['index']) ? -1 : 1;
    }
}


/**
  * Filter columns by display value
  *
  **/
if (!function_exists('column_display')) {
    function column_display($a) {
        return (isset($a['display'])) ? (int)$a['display'] : false;
    }
}
