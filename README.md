# Botón Extra Josra - Módulo PrestaShop

Módulo para PrestaShop que añade un botón "Ver opciones" en los listados de productos que tienen combinaciones.

## Características

- ✅ Muestra botón solo en productos con combinaciones
- ✅ Múltiples ubicaciones disponibles mediante hooks nativos
- ✅ **Hook personalizado** para colocar el botón donde quieras
- ✅ Tres modos de visualización: todos los productos, por categorías o productos específicos
- ✅ Panel de configuración completo en el backoffice
- ✅ Sistema de caché para optimizar rendimiento
- ✅ Seguridad: sanitización y validación de todos los inputs
- ✅ Responsive y compatible con themes modernos

## Instalación

1. Sube el módulo a `modules/botonextrajosra/`
2. Instala el módulo desde el backoffice
3. Configura las opciones según tus necesidades

## Configuración

Desde el backoffice puedes configurar:

- **Activar/Desactivar** el módulo
- **Texto del botón** (por defecto: "Ver opciones")
- **Hook de visualización** - Elige dónde mostrar el botón:
  - `displayProductListReviews` (Recomendado - debajo del precio)
  - `displayProductPriceBlock` (Zona del precio)
  - `displayProductListFunctionalButtons` (Zona de botones)
  - `displayAfterProductThumb` (Después de la imagen)
  - `displayProductAdditionalInfo` (Info adicional)
- **Modo de visualización**:
  - Todos los productos con combinaciones
  - Solo productos en categorías seleccionadas
  - Solo productos específicos (por ID)

## 🎯 Hook Personalizado

### ¿Qué es el hook personalizado?

El módulo incluye un hook especial llamado `actionBotonExtraJosraDisplay` que te permite mostrar el botón **en cualquier lugar de tu theme** donde tengas acceso al objeto producto.

### ¿Cómo usar el hook personalizado?

#### Opción 1: Desde un archivo .tpl de tu theme

Si estás editando un template de tu theme (por ejemplo, `product-miniature.tpl` o cualquier otro), simplemente añade esta línea donde quieras que aparezca el botón:

```smarty
{hook h='actionBotonExtraJosraDisplay' product=$product}
```

**Ejemplo completo:**

```smarty
{* En tu archivo themes/tu-theme/templates/catalog/_partials/miniatures/product.tpl *}

<div class="product-miniature">
    <div class="thumbnail-container">
        <a href="{$product.url}">
            <img src="{$product.cover.bySize.home_default.url}" alt="{$product.cover.legend}">
        </a>
    </div>

    <div class="product-description">
        <h3>{$product.name}</h3>
        <div class="product-price-and-shipping">
            {$product.price}
        </div>

        {* AQUÍ COLOCAS EL HOOK PERSONALIZADO *}
        {hook h='actionBotonExtraJosraDisplay' product=$product}

        <div class="product-actions">
            {* Resto de botones *}
        </div>
    </div>
</div>
```

#### Opción 2: Desde un módulo personalizado

Si tienes tu propio módulo y quieres mostrar el botón en un hook específico:

```php
<?php
// En tu módulo personalizado

class MiModulo extends Module
{
    public function hookDisplayFooterProduct($params)
    {
        // Ejecutar el hook del módulo botonextrajosra
        return Hook::exec('actionBotonExtraJosraDisplay', [
            'product' => $params['product']
        ]);
    }
}
```

#### Opción 3: Desde un override del theme

Si has hecho un override de un controlador:

```php
<?php
// En override/controllers/front/CategoryController.php

class CategoryController extends CategoryControllerCore
{
    public function initContent()
    {
        parent::initContent();

        // Añadir el hook a tus productos
        $products = $this->getProducts();
        foreach ($products as &$product) {
            $product['extra_button'] = Hook::exec('actionBotonExtraJosraDisplay', [
                'product' => $product
            ]);
        }

        $this->context->smarty->assign('products', $products);
    }
}
```

### ¿Qué hace el hook?

El hook `actionBotonExtraJosraDisplay`:

1. Recibe el objeto `product`
2. Verifica si debe mostrar el botón según la configuración del módulo:
   - Si el producto tiene combinaciones
   - Si cumple con las reglas de modo (categorías/productos específicos)
   - Si el módulo está activo
3. Si cumple todas las condiciones, devuelve el HTML del botón
4. Si no cumple, devuelve una cadena vacía

### Ejemplo completo de uso en listado de productos

```smarty
{* En tu archivo product-list.tpl *}

<div class="products row">
    {foreach from=$products item=product}
        <div class="col-md-4">
            <article class="product-miniature">
                <div class="thumbnail">
                    <a href="{$product.url}">
                        <img src="{$product.cover.small.url}" alt="{$product.name}">
                    </a>
                </div>

                <div class="product-description">
                    <h3>{$product.name}</h3>
                    <p class="product-price">{$product.price}</p>

                    {* Hook personalizado para el botón extra *}
                    {hook h='actionBotonExtraJosraDisplay' product=$product}

                    <button class="btn btn-primary add-to-cart">
                        Añadir al carrito
                    </button>
                </div>
            </article>
        </div>
    {/foreach}
</div>
```

## Mejoras de Seguridad

El módulo incluye las siguientes mejoras de seguridad:

- ✅ **Sanitización de inputs**: Todos los datos del formulario son sanitizados con `pSQL()` y `strip_tags()`
- ✅ **Validación de valores**: Solo se aceptan valores permitidos para hooks y modos
- ✅ **Validación de IDs**: Categorías y productos validados como enteros positivos
- ✅ **Protección XSS**: Escapado de variables en templates con `escape:'html':'UTF-8'`
- ✅ **Protección SQL Injection**: Cast a `(int)` en todas las consultas SQL
- ✅ **Manejo de errores**: Try-catch en consultas SQL críticas

## Mejoras de Rendimiento

- ✅ **Caché de combinaciones**: Las verificaciones de productos con combinaciones se cachean
- ✅ **Caché de categorías**: Las categorías de productos se cachean para evitar consultas repetidas
- ✅ **Verificación optimizada**: Solo se hacen consultas SQL cuando es necesario

## Requisitos

- PrestaShop 1.7.0.0 o superior
- PHP 5.6 o superior

## Soporte

Para reportar bugs o sugerencias, contacta con el desarrollador.

## Licencia

MIT License

---

## Changelog

### v1.0.0 (2025)
- ✨ Lanzamiento inicial
- ✨ Hook personalizado `actionBotonExtraJosraDisplay`
- ✨ Sistema de caché para mejor rendimiento
- ✨ Mejoras de seguridad (sanitización y validación)
- ✨ Manejo de errores en consultas SQL
- ✨ Soporte para múltiples hooks nativos
- ✨ Tres modos de visualización configurables
