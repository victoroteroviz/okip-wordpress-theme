# OKIP Theme — Sistema de Administración (Presente y Futuro)

## 1. Estado Actual (Stubs - Sin Funcionalidad Real)

Archivos existentes pero sin implementación funcional:
```
inc/admin/
├─ admin-pages.php          [Registro de página admin]
├─ fields.php               [Generadores de campos]
├─ media-fields.php         [Campos para media]
├─ sanitizers.php           [Funciones de saneo]
├─ save-handlers.php        [Guardado en wp_options]
├─ notices.php              [Notificaciones]
├─ repositories.php         [Persistencia]
├─ layout-settings.php      [Controles de layout]
├─ editors/
│   ├─ hero.php            [Panel Hero]
│   ├─ video-w-title.php   [Panel Video]
│   ├─ industry-carousel.php [Panel Carousel]
│   └─ news.php            [Panel News]
└─ partials/
    ├─ hero-cards.php      [Repeater de tarjetas]
    ├─ vwt-text-boxes.php  [Repeater de cajas]
    └─ ic-items.php        [Repeater de items]
```

## 2. Flujo de Datos (Futuro Admin)

### Lectura (Frontend)

```
okip_get_page_blocks('home')
  ├─ Lee config/pages/home.php (defaults)
  └─ apply_filters('okip_page_blocks', $blocks, 'home')
      ├─ okip_apply_page_block_order() [prio 10]
      │   └─ get_option('okip_page_blocks_order_home')
      │   └─ Reordena según admin
      │
      └─ okip_apply_page_block_overrides() [prio 20]
          └─ get_option('okip_page_blocks_overrides_home')
          └─ Mezcla diffs sobre defaults
```

### Escritura (Admin)

```
User edita bloque en panel admin
  ↓
JavaScript construye array de bloques con cambios
  ↓
AJAX POST a save-handler
  (action: 'okip_save_page_blocks')
  ├─ Recibe: { slug, blocks, order }
  └─ Sanitiza con okip_admin_sanitize_*()
  ↓
save-handler.php guarda en wp_options:
  ├─ okip_page_blocks_order_home = [instance_ids]
  └─ okip_page_blocks_overrides_home = {instance_id: {data: {...}}}
  ↓
Responde SUCCESS / ERROR al JS
  ↓
Frontend recarga contenido
  (frontend.php refresca bloques con nuevos datos)
```

## 3. Estructura de Guardado

### wp_options Keys

```php
// Orden de bloques
okip_page_blocks_order_home
// Valor: ['home-hero-main', 'home-video-w-title', ...]

// Overrides (diffs)
okip_page_blocks_overrides_home
// Valor: {
//   'home-hero-main': {
//       'type': 'hero',  // Informativo, no se edita el type
//       'data': {
//           'content': {
//               'title_line_1': 'Nuevo título'  // Solo cambios
//           }
//       }
//   }
// }
```

### Ventaja

- Config defaults siempre intacta
- Admin no puede romper defaults
- Reverticable: borrar option = volver a defaults
- No requiere reescribir archivos del tema

## 4. Sanitizadores Admin

### okip_admin_sanitize_hero_data()

```php
function okip_admin_sanitize_hero_data($data) {
    // Usa okip_normalize_hero_data() como base
    
    // Validaciones adicionales:
    // - Verifica media existe antes de guardar URL
    // - Remapea IDs de attachment → URLs
    // - Clampea rangos según esquema
    
    return $sanitized;
}
```

Patrón similar para cada tipo de bloque:
- okip_admin_sanitize_video_w_title_data()
- okip_admin_sanitize_industry_carousel_data()
- okip_admin_sanitize_product_story_data()
- okip_admin_sanitize_mission_statement_data()
- okip_admin_sanitize_news_data()

## 5. Generadores de Campos HTML

### inc/admin/fields.php

```php
// Checkboxes
okip_admin_checkbox_field($label, $name, $value);
// → <input type="checkbox" name="...">

// Text / Textarea
okip_admin_text_field($label, $name, $value);
okip_admin_textarea_field($label, $name, $value);
// → <input type="text"> / <textarea>

// Select
okip_admin_select_field($label, $name, $value, $options);
// → <select><option>...</select>

// Number
okip_admin_number_field($label, $name, $value, $help, $attrs);
// → <input type="number" min max step>

// Color
okip_admin_color_field($label, $name, $value);
// → <input type="color">

// Media picker
okip_admin_media_field($label, $name, $value, $type = 'image');
// → jQuery Media Modal
```

### inc/admin/media-fields.php

```php
// Repeater de media
okip_admin_media_repeater($label, $base_name, $items);
// → Cada item: media + alt + opcional otros campos

// Para Hero cards, Carousel items, etc.
```

## 6. Editores (Panels)

### Hero Editor (inc/admin/editors/hero.php)

```php
function okip_admin_editor_hero($instance_id, $data) {
    echo '<div class="okip-admin-panel okip-admin-editor-hero">';
    
    // Sección Content
    echo '<fieldset class="okip-admin-section">';
    okip_admin_text_field('Título línea 1', 'data[content][title_line_1]', ...);
    okip_admin_text_field('Título línea 2', 'data[content][title_line_2]', ...);
    okip_admin_textarea_field('Descripción', 'data[content][description]', ...);
    echo '</fieldset>';
    
    // Sección Background
    echo '<fieldset class="okip-admin-section">';
    okip_admin_select_field('Tipo bg', 'data[background][type]', ..., 
        ['video', 'image', 'css_motion', ...]);
    okip_admin_media_field('Video intro', 'data[background][intro_media]', ...);
    okip_admin_media_field('Video loop', 'data[background][loop_media]', ...);
    echo '</fieldset>';
    
    // Sección Cards (repeater)
    echo '<fieldset class="okip-admin-section">';
    get_template_part('inc/admin/partials/hero-cards', null, [
        'cards' => $data['cards'],
        'base_name' => 'data[cards]'
    ]);
    echo '</fieldset>';
    
    // Sección Motion (animation-controls)
    echo '<fieldset class="okip-admin-section">';
    okip_admin_motion_target_group('Background Motion', 'data[motion][background]',
        $data['motion']['background'], 'background', true, true);
    okip_admin_motion_target_group('Text Motion', 'data[motion][text]',
        $data['motion']['text'], 'text', false);
    okip_admin_motion_target_group('Cards Motion', 'data[motion][cards]',
        $data['motion']['cards'], 'cards', true);
    echo '</fieldset>';
    
    echo '</div>';
}
```

### Partials (Repeaters)

**hero-cards.php:**
```php
// Repeater de tarjetas
// Cada card: id, type, media, placeholder_label, play_mode, play_duration_ms, etc.
// Botón "Agregar tarjeta" (max N)
// Drag-drop para reordenar (opcional)
```

**ic-items.php:**
```php
// Repeater de items carousel
// Cada item: title, image, video, orange_text, title_color
// Drag-drop para reordenar (opcional)
```

## 7. Save Handlers (AJAX)

### inc/admin/save-handlers.php

```php
// Hook: wp_ajax_okip_save_page_blocks
add_action('wp_ajax_okip_save_page_blocks', 'okip_handle_save_page_blocks');

function okip_handle_save_page_blocks() {
    check_ajax_referer('okip-admin-nonce');
    
    if (!current_user_can('edit_pages')) {
        wp_die('Forbidden', 403);
    }
    
    $slug = isset($_POST['slug']) ? sanitize_key($_POST['slug']) : '';
    $blocks = isset($_POST['blocks']) ? (array) $_POST['blocks'] : [];
    $order = isset($_POST['order']) ? (array) $_POST['order'] : [];
    
    // Sanitizar cada bloque
    foreach ($blocks as $instance_id => &$block) {
        $type = isset($block['type']) ? $block['type'] : '';
        $sanitizer = 'okip_admin_sanitize_' . str_replace('-', '_', $type) . '_data';
        
        if (function_exists($sanitizer)) {
            $block['data'] = call_user_func($sanitizer, $block['data']);
        }
    }
    
    // Calcular diffs
    $base_blocks = okip_get_page_blocks($slug);
    $diffs = [];
    foreach ($blocks as $instance_id => $block) {
        $base = findBlockByInstanceId($base_blocks, $instance_id);
        $diff = okip_array_diff_recursive($block['data'], $base['data']);
        if (!empty($diff)) {
            $diffs[$instance_id] = [
                'type' => $block['type'],
                'data' => $diff
            ];
        }
    }
    
    // Guardar
    update_option('okip_page_blocks_order_' . $slug, $order);
    update_option('okip_page_blocks_overrides_' . $slug, $diffs);
    
    wp_send_json_success(['message' => 'Guardado']);
}
```

## 8. Página Admin Principal

### Ubicación

```
WordPress Admin → Appearance → OKIP Blocks
```

### Funcionalidad

```
┌─────────────────────────────────┐
│ OKIP Blocks — Home              │
├─────────────────────────────────┤
│                                 │
│ Bloques (arrastrables):         │
│                                 │
│ 1. [HERO] home-hero-main ✎ ╳    │
│ 2. [VWT] home-video-w-title ✎ ╳ │
│ 3. [IC] home-industry-carousel ✎ ╳ │
│ ... (más bloques)               │
│                                 │
│ [+ Agregar bloque]              │
│                                 │
│ [Guardar cambios]               │
│                                 │
└─────────────────────────────────┘
```

**Acciones por ítem:**
- ✎ = Editar (abre panel modal/inline)
- ✳ = Duplicar
- ↕ = Reordenar (drag-drop o botones)
- ✗ = Ocultar/Eliminar override

## 9. Validación Client-side

```javascript
// admin-blocks.js (JS del panel admin)

function validateBlockData(type, data) {
    // Usa misma lógica de normalización que server
    
    // Ejemplo: Hero
    if (type === 'hero') {
        // Validar tarjetas
        if (data.cards && data.cards.length > MAX_CARDS) {
            throw new Error(`Max ${MAX_CARDS} tarjetas`);
        }
        
        // Validar URLs de media
        data.cards.forEach(card => {
            if (card.media && !isValidUrl(card.media)) {
                throw new Error(`URL inválida: ${card.media}`);
            }
        });
    }
    
    return data;
}
```

## 10. Transiciones Admin → Frontend

### Cuando User Guarda en Admin

```
1. JavaScript valida localmente
2. AJAX POST al save-handler
3. Server sanitiza + valida
4. Server calcula diffs y guarda en wp_options
5. AJAX response: SUCCESS
6. JS recarga página o refresa bloque previsualizando cambios
7. User ve resultados en frontend en tiempo real
```

### Sin Tocar Config del Tema

- ❌ Nunca modifica `config/pages/home.php`
- ❌ Nunca modifica `config/blocks/*.php`
- ✅ Solo modifica `wp_options`
- ✅ Admin no requiere acceso al servidor de archivos

## 11. Borradores / Revisiones (Futuro)

```php
// Posible: guardar múltiples versiones

$revisions = get_option('okip_page_blocks_revisions_home');
// [
//   [timestamp, user_id, diffs],
//   ...
// ]

// Botón "Historial" → seleccionar versión anterior
```

## 12. Permisos (Capabilities)

```php
// Requerido: edit_pages
if (!current_user_can('edit_pages')) {
    wp_die('Forbidden');
}

// Futuro: admin puede delegar a editor
add_cap('editor', 'edit_okip_blocks');
```

