<?php
/**
 * Módulo Botón Extra - Añade botón "Ver opciones" en listados de productos
 *
 * @author Josra
 * @copyright 2025
 * @license MIT
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class BotonExtraJosra extends Module
{
    public function __construct()
    {
        $this->name = 'botonextrajosra';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Josra';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => _PS_VERSION_
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Botón Extra para Productos');
        $this->description = $this->l('Añade un botón "Ver opciones" en listados de productos con combinaciones');
        $this->confirmUninstall = $this->l('¿Estás seguro de desinstalar este módulo?');
    }

    public function install()
    {
        // Valores por defecto
        Configuration::updateValue('BOTONEXTRAJOSRA_ACTIVE', 1);
        Configuration::updateValue('BOTONEXTRAJOSRA_TEXT', 'Ver opciones');
        Configuration::updateValue('BOTONEXTRAJOSRA_HOOK', 'displayProductListReviews');
        Configuration::updateValue('BOTONEXTRAJOSRA_CATEGORIES', json_encode([]));
        Configuration::updateValue('BOTONEXTRAJOSRA_PRODUCTS', json_encode([]));
        Configuration::updateValue('BOTONEXTRAJOSRA_MODE', 'categories'); // categories, products, all

        return parent::install()
            && $this->registerHook('displayProductListReviews')
            && $this->registerHook('displayProductPriceBlock')
            && $this->registerHook('displayProductListFunctionalButtons')
            && $this->registerHook('displayAfterProductThumb')
            && $this->registerHook('displayProductAdditionalInfo')
            && $this->registerHook('header');
    }

    public function uninstall()
    {
        Configuration::deleteByName('BOTONEXTRAJOSRA_ACTIVE');
        Configuration::deleteByName('BOTONEXTRAJOSRA_TEXT');
        Configuration::deleteByName('BOTONEXTRAJOSRA_HOOK');
        Configuration::deleteByName('BOTONEXTRAJOSRA_CATEGORIES');
        Configuration::deleteByName('BOTONEXTRAJOSRA_PRODUCTS');
        Configuration::deleteByName('BOTONEXTRAJOSRA_MODE');

        return parent::uninstall();
    }

    /**
     * Configuración del módulo
     */
    public function getContent()
    {
        $output = '';

        // Procesar formulario
        if (Tools::isSubmit('submitBotonExtraJosra')) {
            $this->postProcess();
            $output .= $this->displayConfirmation($this->l('Configuración guardada correctamente'));
        }

        // Mostrar formulario
        return $output . $this->renderForm();
    }

    /**
     * Procesar datos del formulario
     */
    protected function postProcess()
    {
        Configuration::updateValue('BOTONEXTRAJOSRA_ACTIVE', (int)Tools::getValue('BOTONEXTRAJOSRA_ACTIVE'));
        Configuration::updateValue('BOTONEXTRAJOSRA_TEXT', Tools::getValue('BOTONEXTRAJOSRA_TEXT'));
        Configuration::updateValue('BOTONEXTRAJOSRA_HOOK', Tools::getValue('BOTONEXTRAJOSRA_HOOK'));
        Configuration::updateValue('BOTONEXTRAJOSRA_MODE', Tools::getValue('BOTONEXTRAJOSRA_MODE'));
        
        // Categorías
        $categories = Tools::getValue('BOTONEXTRAJOSRA_CATEGORIES');
        if ($categories && is_array($categories)) {
            Configuration::updateValue('BOTONEXTRAJOSRA_CATEGORIES', json_encode($categories));
        } else {
            Configuration::updateValue('BOTONEXTRAJOSRA_CATEGORIES', json_encode([]));
        }

        // Productos (desde el campo de texto)
        $products = Tools::getValue('BOTONEXTRAJOSRA_PRODUCTS');
        if ($products) {
            $productIds = array_map('trim', explode(',', $products));
            $productIds = array_filter($productIds, 'is_numeric');
            Configuration::updateValue('BOTONEXTRAJOSRA_PRODUCTS', json_encode($productIds));
        } else {
            Configuration::updateValue('BOTONEXTRAJOSRA_PRODUCTS', json_encode([]));
        }
    }

    /**
     * Renderizar formulario de configuración
     */
    protected function renderForm()
    {
        $helper = new HelperForm();
        
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitBotonExtraJosra';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$this->getConfigForm()]);
    }

    /**
     * Estructura del formulario
     */
    protected function getConfigForm()
    {
        // Obtener todas las categorías de forma compatible
        $categoryOptions = $this->getAllCategoriesFormatted();

        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Configuración del Botón Extra'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Activar módulo'),
                        'name' => 'BOTONEXTRAJOSRA_ACTIVE',
                        'is_bool' => true,
                        'desc' => $this->l('Activar o desactivar el botón extra'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Sí')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('No')
                            ]
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Texto del botón'),
                        'name' => 'BOTONEXTRAJOSRA_TEXT',
                        'desc' => $this->l('Texto que aparecerá en el botón'),
                        'required' => true
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Hook a utilizar'),
                        'name' => 'BOTONEXTRAJOSRA_HOOK',
                        'desc' => $this->l('Selecciona dónde quieres mostrar el botón. Si un hook no funciona en tu theme, prueba con otro.'),
                        'options' => [
                            'query' => [
                                ['id' => 'displayProductListReviews', 'name' => 'displayProductListReviews (Recomendado - Debajo del precio)'],
                                ['id' => 'displayProductPriceBlock', 'name' => 'displayProductPriceBlock (Zona del precio)'],
                                ['id' => 'displayProductListFunctionalButtons', 'name' => 'displayProductListFunctionalButtons (Zona de botones)'],
                                ['id' => 'displayAfterProductThumb', 'name' => 'displayAfterProductThumb (Después de la imagen)'],
                                ['id' => 'displayProductAdditionalInfo', 'name' => 'displayProductAdditionalInfo (Info adicional)'],
                            ],
                            'id' => 'id',
                            'name' => 'name'
                        ]
                    ],
                    [
                        'type' => 'radio',
                        'label' => $this->l('Modo de visualización'),
                        'name' => 'BOTONEXTRAJOSRA_MODE',
                        'desc' => $this->l('Elige cuándo mostrar el botón'),
                        'values' => [
                            [
                                'id' => 'mode_all',
                                'value' => 'all',
                                'label' => $this->l('Todos los productos con combinaciones')
                            ],
                            [
                                'id' => 'mode_categories',
                                'value' => 'categories',
                                'label' => $this->l('Solo productos en categorías seleccionadas')
                            ],
                            [
                                'id' => 'mode_products',
                                'value' => 'products',
                                'label' => $this->l('Solo productos específicos (por ID)')
                            ]
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Categorías'),
                        'name' => 'BOTONEXTRAJOSRA_CATEGORIES[]',
                        'desc' => $this->l('Selecciona las categorías donde mostrar el botón (solo si elegiste modo "Categorías")'),
                        'multiple' => true,
                        'class' => 'chosen',
                        'options' => [
                            'query' => $categoryOptions,
                            'id' => 'id',
                            'name' => 'name'
                        ]
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('IDs de productos'),
                        'name' => 'BOTONEXTRAJOSRA_PRODUCTS',
                        'desc' => $this->l('IDs de productos separados por comas (ej: 1,5,12,45). Solo si elegiste modo "Productos específicos"'),
                        'placeholder' => '1,2,3,4,5'
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Guardar'),
                ],
            ],
        ];
    }

    /**
     * Obtener todas las categorías en formato plano
     */
    protected function getAllCategoriesFormatted()
    {
        $sql = 'SELECT c.id_category, cl.name, c.level_depth
                FROM ' . _DB_PREFIX_ . 'category c
                LEFT JOIN ' . _DB_PREFIX_ . 'category_lang cl 
                    ON (c.id_category = cl.id_category AND cl.id_lang = ' . (int)$this->context->language->id . ')
                WHERE c.active = 1
                ORDER BY c.nleft ASC';

        $categories = Db::getInstance()->executeS($sql);
        $result = [];

        if ($categories) {
            foreach ($categories as $category) {
                $result[] = [
                    'id' => (int)$category['id_category'],
                    'name' => str_repeat('— ', (int)$category['level_depth']) . $category['name']
                ];
            }
        }

        return $result;
    }

    /**
     * Valores del formulario
     */
    protected function getConfigFormValues()
    {
        $categories = json_decode(Configuration::get('BOTONEXTRAJOSRA_CATEGORIES'), true);
        $products = json_decode(Configuration::get('BOTONEXTRAJOSRA_PRODUCTS'), true);
        
        return [
            'BOTONEXTRAJOSRA_ACTIVE' => Configuration::get('BOTONEXTRAJOSRA_ACTIVE'),
            'BOTONEXTRAJOSRA_TEXT' => Configuration::get('BOTONEXTRAJOSRA_TEXT'),
            'BOTONEXTRAJOSRA_HOOK' => Configuration::get('BOTONEXTRAJOSRA_HOOK'),
            'BOTONEXTRAJOSRA_MODE' => Configuration::get('BOTONEXTRAJOSRA_MODE'),
            'BOTONEXTRAJOSRA_CATEGORIES[]' => $categories ? $categories : [],
            'BOTONEXTRAJOSRA_PRODUCTS' => $products ? implode(',', $products) : '',
        ];
    }

    /**
     * Verificar si debe mostrar el botón para este producto
     */
    protected function shouldShowButton($product)
    {
        // Módulo desactivado
        if (!Configuration::get('BOTONEXTRAJOSRA_ACTIVE')) {
            return false;
        }

        // Verificar si tiene combinaciones
        $hasAttributes = false;
        if (isset($product['id_product_attribute']) && $product['id_product_attribute']) {
            $hasAttributes = true;
        } elseif (isset($product['attributes'])) {
            $hasAttributes = !empty($product['attributes']);
        } else {
            // Verificar directamente en BD
            $sql = 'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'product_attribute` 
                    WHERE `id_product` = ' . (int)$product['id_product'];
            $hasAttributes = (int)Db::getInstance()->getValue($sql) > 0;
        }

        if (!$hasAttributes) {
            return false;
        }

        $mode = Configuration::get('BOTONEXTRAJOSRA_MODE');

        // Modo: todos los productos
        if ($mode === 'all') {
            return true;
        }

        // Modo: categorías específicas
        if ($mode === 'categories') {
            $selectedCategories = json_decode(Configuration::get('BOTONEXTRAJOSRA_CATEGORIES'), true);
            if (!$selectedCategories || empty($selectedCategories)) {
                return false;
            }

            // Obtener categorías del producto
            $productCategories = Product::getProductCategories($product['id_product']);
            
            // Verificar intersección
            return !empty(array_intersect($selectedCategories, $productCategories));
        }

        // Modo: productos específicos
        if ($mode === 'products') {
            $selectedProducts = json_decode(Configuration::get('BOTONEXTRAJOSRA_PRODUCTS'), true);
            if (!$selectedProducts || empty($selectedProducts)) {
                return false;
            }

            return in_array($product['id_product'], $selectedProducts);
        }

        return false;
    }

    /**
     * Renderizar el botón
     */
    protected function renderButton($product)
    {
        $link = new Link();
        $productUrl = $link->getProductLink($product['id_product']);
        $buttonText = Configuration::get('BOTONEXTRAJOSRA_TEXT');

        $this->context->smarty->assign([
            'product_url' => $productUrl,
            'button_text' => $buttonText,
            'product' => $product
        ]);

        return $this->display(__FILE__, 'views/templates/hook/product-button.tpl');
    }

    /**
     * Hook: displayProductListReviews
     */
    public function hookDisplayProductListReviews($params)
    {
        if (Configuration::get('BOTONEXTRAJOSRA_HOOK') !== 'displayProductListReviews') {
            return '';
        }

        if (!isset($params['product'])) {
            return '';
        }

        if (!$this->shouldShowButton($params['product'])) {
            return '';
        }

        return $this->renderButton($params['product']);
    }

    /**
     * Hook: displayProductPriceBlock
     */
    public function hookDisplayProductPriceBlock($params)
    {
        if (Configuration::get('BOTONEXTRAJOSRA_HOOK') !== 'displayProductPriceBlock') {
            return '';
        }

        if (!isset($params['product'])) {
            return '';
        }

        if (!$this->shouldShowButton($params['product'])) {
            return '';
        }

        return $this->renderButton($params['product']);
    }

    /**
     * Hook: displayProductListFunctionalButtons
     */
    public function hookDisplayProductListFunctionalButtons($params)
    {
        if (Configuration::get('BOTONEXTRAJOSRA_HOOK') !== 'displayProductListFunctionalButtons') {
            return '';
        }

        if (!isset($params['product'])) {
            return '';
        }

        if (!$this->shouldShowButton($params['product'])) {
            return '';
        }

        return $this->renderButton($params['product']);
    }

    /**
     * Hook: displayAfterProductThumb
     */
    public function hookDisplayAfterProductThumb($params)
    {
        if (Configuration::get('BOTONEXTRAJOSRA_HOOK') !== 'displayAfterProductThumb') {
            return '';
        }

        if (!isset($params['product'])) {
            return '';
        }

        if (!$this->shouldShowButton($params['product'])) {
            return '';
        }

        return $this->renderButton($params['product']);
    }

    /**
     * Hook: displayProductAdditionalInfo
     */
    public function hookDisplayProductAdditionalInfo($params)
    {
        if (Configuration::get('BOTONEXTRAJOSRA_HOOK') !== 'displayProductAdditionalInfo') {
            return '';
        }

        if (!isset($params['product'])) {
            return '';
        }

        if (!$this->shouldShowButton($params['product'])) {
            return '';
        }

        return $this->renderButton($params['product']);
    }

    /**
     * Hook: header (para añadir CSS si es necesario)
     */
    public function hookHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/front.css');
    }
}