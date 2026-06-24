<?php

/**
 * Controles de animacion compartidos.
 *
 * @package OKIP
 */

if (! defined('ABSPATH')) {
    exit;
}

function okip_motion_ease_options()
{
    return array(
        'power1.out' => 'Power 1 out',
        'power2.out' => 'Power 2 out',
        'power3.out' => 'Power 3 out',
        'power4.out' => 'Power 4 out',
        'sine.out'   => 'Sine out',
        'expo.out'   => 'Expo out',
        'circ.out'   => 'Circ out',
        'back.out'   => 'Back out',
        'linear'     => 'Linear',
        'none'       => 'None',
    );
}

function okip_motion_direction_options()
{
    return array(
        'alternate' => __('Alterna', 'okip'),
        'normal'    => __('Normal', 'okip'),
        'reverse'   => __('Reversa', 'okip'),
    );
}

function okip_motion_replay_options()
{
    return array(
        'once'   => __('Una vez', 'okip'),
        'replay' => __('Repetir al entrar', 'okip'),
    );
}

function okip_motion_exit_trigger_options()
{
    return array(
        'viewport_leave' => __('Al salir del viewport', 'okip'),
        'none'           => __('Sin salida', 'okip'),
    );
}

function okip_motion_preset_options($target, $phase)
{
    $options = array(
        'background' => array(
            'entry' => array(
                'soft-arrive' => __('Soft arrive', 'okip'),
                'fade-blur'   => __('Fade blur', 'okip'),
                'rise-depth'  => __('Rise depth', 'okip'),
                'none'        => __('Ninguno', 'okip'),
            ),
            'playback' => array(
                'variant-motion' => __('Movimiento de variante', 'okip'),
                'slow-drift'     => __('Slow drift', 'okip'),
                'pulse-field'    => __('Pulse field', 'okip'),
                'none'           => __('Ninguno', 'okip'),
            ),
            'exit' => array(
                'fade-depth'    => __('Fade depth', 'okip'),
                'soft-blur-out' => __('Soft blur out', 'okip'),
                'none'          => __('Ninguno', 'okip'),
            ),
        ),
        'text' => array(
            'entry' => array(
                'stagger-fade-up' => __('Stagger fade up', 'okip'),
                'soft-mask-up'    => __('Soft mask up', 'okip'),
                'fade-only'       => __('Fade only', 'okip'),
                'none'            => __('Ninguno', 'okip'),
            ),
            'exit' => array(
                'fade-up'   => __('Fade up', 'okip'),
                'fade-down' => __('Fade down', 'okip'),
                'none'      => __('Ninguno', 'okip'),
            ),
        ),
        'cards' => array(
            'entry' => array(
                'stagger-float-up' => __('Stagger float up', 'okip'),
                'scale-fade'       => __('Scale fade', 'okip'),
                'slide-depth'      => __('Slide depth', 'okip'),
                'none'             => __('Ninguno', 'okip'),
            ),
            'playback' => array(
                'float-soft' => __('Float soft', 'okip'),
                'glow-pulse' => __('Glow pulse', 'okip'),
                'none'       => __('Ninguno', 'okip'),
            ),
            'exit' => array(
                'fade-scale' => __('Fade scale', 'okip'),
                'float-down' => __('Float down', 'okip'),
                'none'       => __('Ninguno', 'okip'),
            ),
        ),
    );

    return isset($options[$target][$phase]) ? $options[$target][$phase] : array('none' => __('Ninguno', 'okip'));
}

function okip_motion_stage_defaults($target, $phase)
{
    $base = array(
        'enabled'      => true,
        'preset'       => 'none',
        'duration_ms'  => 700,
        'delay_ms'     => 0,
        'stagger_ms'   => 0,
        'ease'         => 'power3.out',
        'opacity_from' => 0,
        'opacity_to'   => 1,
        'x_from'       => 0,
        'x_to'         => 0,
        'y_from'       => 0,
        'y_to'         => 0,
        'scale_from'   => 1,
        'scale_to'     => 1,
        'rotate_from'  => 0,
        'rotate_to'    => 0,
        'blur_from'    => 0,
        'blur_to'      => 0,
    );

    if ($phase === 'playback') {
        $base['duration_ms'] = 4200;
        $base['opacity_from'] = 1;
        $base['opacity_to'] = 1;
        $base['intensity'] = .5;
        $base['speed'] = 1;
        $base['direction'] = 'alternate';
        $base['yoyo'] = true;
    }

    $key = $target . '.' . $phase;
    $overrides = array(
        'background.entry' => array(
            'preset'       => 'soft-arrive',
            'duration_ms'  => 1180,
            'opacity_from' => .18,
            'opacity_to'   => 1,
            'y_from'       => 8,
            'scale_from'   => 1.026,
            'scale_to'     => 1,
            'blur_from'    => 6,
            'blur_to'      => 0,
        ),
        'background.playback' => array(
            'preset'      => 'variant-motion',
            'duration_ms' => 18000,
            'intensity'   => .34,
            'speed'       => .82,
            'yoyo'        => true,
        ),
        'background.exit' => array(
            'preset'       => 'fade-depth',
            'duration_ms'  => 700,
            'opacity_from' => 1,
            'opacity_to'   => .35,
            'y_to'         => -30,
            'scale_to'     => .96,
            'blur_to'      => 8,
            'ease'         => 'power2.out',
        ),
        'text.entry' => array(
            'preset'      => 'stagger-fade-up',
            'duration_ms' => 700,
            'delay_ms'    => 600,
            'stagger_ms'  => 120,
            'y_from'      => 28,
        ),
        'text.exit' => array(
            'preset'       => 'fade-up',
            'duration_ms'  => 500,
            'opacity_from' => 1,
            'opacity_to'   => 0,
            'y_to'         => -18,
            'ease'         => 'power2.out',
        ),
        'cards.entry' => array(
            'preset'      => 'stagger-float-up',
            'duration_ms' => 650,
            'delay_ms'    => 300,
            'stagger_ms'  => 120,
            'y_from'      => 24,
            'scale_from'  => .96,
        ),
        'cards.playback' => array(
            'preset'      => 'float-soft',
            'duration_ms' => 4200,
            'intensity'   => .45,
            'speed'       => 1,
            'y_to'        => -10,
            'scale_to'    => 1.02,
            'yoyo'        => true,
        ),
        'cards.exit' => array(
            'preset'       => 'fade-scale',
            'duration_ms'  => 500,
            'opacity_from' => 1,
            'opacity_to'   => 0,
            'y_to'         => 20,
            'scale_to'     => .96,
            'ease'         => 'power2.out',
        ),
    );

    return isset($overrides[$key]) ? array_merge($base, $overrides[$key]) : $base;
}

function okip_motion_defaults(array $targets = array('background', 'text', 'cards'))
{
    $motion = array(
        'enabled'      => true,
        'replay_mode'  => 'once',
        'exit_trigger' => 'viewport_leave',
    );

    foreach ($targets as $target) {
        $motion[$target] = array(
            'entry' => okip_motion_stage_defaults($target, 'entry'),
            'exit'  => okip_motion_stage_defaults($target, 'exit'),
        );
        if (in_array($target, array('background', 'cards'), true)) {
            $motion[$target]['playback'] = okip_motion_stage_defaults($target, 'playback');
        }
    }

    return $motion;
}

function okip_normalize_motion_stage($stage, $target, $phase)
{
    $defaults = okip_motion_stage_defaults($target, $phase);
    $stage = is_array($stage) ? $stage : array();
    $stage = okip_merge_defaults($stage, $defaults);

    $preset_options = okip_motion_preset_options($target, $phase);
    $ease_options = okip_motion_ease_options();

    $out = array(
        'enabled'      => okip_bool($stage['enabled']),
        'preset'       => okip_one_of($stage['preset'], array_keys($preset_options), $defaults['preset']),
        'duration_ms'  => okip_clamp_int($stage['duration_ms'], 0, 20000),
        'delay_ms'     => okip_clamp_int($stage['delay_ms'], 0, 20000),
        'stagger_ms'   => okip_clamp_int($stage['stagger_ms'], 0, 5000),
        'ease'         => okip_one_of($stage['ease'], array_keys($ease_options), $defaults['ease']),
        'opacity_from' => okip_clamp_float($stage['opacity_from'], 0, 1),
        'opacity_to'   => okip_clamp_float($stage['opacity_to'], 0, 1),
        'x_from'       => okip_clamp_float($stage['x_from'], -1000, 1000),
        'x_to'         => okip_clamp_float($stage['x_to'], -1000, 1000),
        'y_from'       => okip_clamp_float($stage['y_from'], -1000, 1000),
        'y_to'         => okip_clamp_float($stage['y_to'], -1000, 1000),
        'scale_from'   => okip_clamp_float($stage['scale_from'], 0, 5),
        'scale_to'     => okip_clamp_float($stage['scale_to'], 0, 5),
        'rotate_from'  => okip_clamp_float($stage['rotate_from'], -360, 360),
        'rotate_to'    => okip_clamp_float($stage['rotate_to'], -360, 360),
        'blur_from'    => okip_clamp_float($stage['blur_from'], 0, 80),
        'blur_to'      => okip_clamp_float($stage['blur_to'], 0, 80),
    );

    if ($phase === 'playback') {
        $out['intensity'] = okip_clamp_float(isset($stage['intensity']) ? $stage['intensity'] : $defaults['intensity'], 0, 1);
        $out['speed'] = okip_clamp_float(isset($stage['speed']) ? $stage['speed'] : $defaults['speed'], .1, 5);
        $out['direction'] = okip_one_of(isset($stage['direction']) ? $stage['direction'] : $defaults['direction'], array_keys(okip_motion_direction_options()), $defaults['direction']);
        $out['yoyo'] = okip_bool(isset($stage['yoyo']) ? $stage['yoyo'] : $defaults['yoyo']);
    }

    return $out;
}

function okip_normalize_motion($motion, array $targets = array('background', 'text', 'cards'))
{
    $defaults = okip_motion_defaults($targets);
    $motion = is_array($motion) ? $motion : array();

    $out = array(
        'enabled'      => okip_bool(isset($motion['enabled']) ? $motion['enabled'] : $defaults['enabled']),
        'replay_mode'  => okip_one_of(isset($motion['replay_mode']) ? $motion['replay_mode'] : $defaults['replay_mode'], array_keys(okip_motion_replay_options()), 'once'),
        'exit_trigger' => okip_one_of(isset($motion['exit_trigger']) ? $motion['exit_trigger'] : $defaults['exit_trigger'], array_keys(okip_motion_exit_trigger_options()), 'viewport_leave'),
    );

    foreach ($targets as $target) {
        $out[$target] = array(
            'entry' => okip_normalize_motion_stage(isset($motion[$target]['entry']) ? $motion[$target]['entry'] : array(), $target, 'entry'),
            'exit'  => okip_normalize_motion_stage(isset($motion[$target]['exit']) ? $motion[$target]['exit'] : array(), $target, 'exit'),
        );
        if (isset($defaults[$target]['playback'])) {
            $out[$target]['playback'] = okip_normalize_motion_stage(isset($motion[$target]['playback']) ? $motion[$target]['playback'] : array(), $target, 'playback');
        }
    }

    return $out;
}

function okip_motion_config_json(array $motion, array $selectors)
{
    return wp_json_encode(
        array(
            'motion'    => $motion,
            'selectors' => $selectors,
        ),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
}

function okip_admin_motion_stage_fields($label, $base_name, array $stage, $target, $phase)
{
    $stage = okip_normalize_motion_stage($stage, $target, $phase);
    echo '<fieldset class="okip-admin-panel okip-admin-panel--nested">';
    echo '<legend>' . esc_html($label) . '</legend>';
    echo '<div class="okip-admin-grid okip-admin-grid--two">';
    okip_admin_checkbox_field(__('Activa', 'okip'), $base_name . '[enabled]', $stage['enabled']);
    okip_admin_select_field(__('Preset', 'okip'), $base_name . '[preset]', $stage['preset'], okip_motion_preset_options($target, $phase));
    okip_admin_number_field(__('Duración ms', 'okip'), $base_name . '[duration_ms]', $stage['duration_ms'], '', array('min' => 0, 'max' => 20000, 'step' => 50));
    okip_admin_number_field(__('Delay ms', 'okip'), $base_name . '[delay_ms]', $stage['delay_ms'], '', array('min' => 0, 'max' => 20000, 'step' => 50));
    okip_admin_number_field(__('Stagger ms', 'okip'), $base_name . '[stagger_ms]', $stage['stagger_ms'], '', array('min' => 0, 'max' => 5000, 'step' => 25));
    okip_admin_select_field(__('Easing', 'okip'), $base_name . '[ease]', $stage['ease'], okip_motion_ease_options());
    if ($phase === 'playback') {
        okip_admin_number_field(__('Intensidad', 'okip'), $base_name . '[intensity]', $stage['intensity'], '', array('min' => 0, 'max' => 1, 'step' => .01));
        okip_admin_number_field(__('Velocidad', 'okip'), $base_name . '[speed]', $stage['speed'], '', array('min' => .1, 'max' => 5, 'step' => .05));
        okip_admin_select_field(__('Dirección', 'okip'), $base_name . '[direction]', $stage['direction'], okip_motion_direction_options());
        okip_admin_checkbox_field(__('Yoyo', 'okip'), $base_name . '[yoyo]', $stage['yoyo']);
    }
    echo '</div>';
    echo '<details class="okip-admin-panel okip-admin-panel--nested"><summary>' . esc_html__('Ajuste fino (transform / opacidad / blur)', 'okip') . '</summary>';
    echo '<div class="okip-admin-grid okip-admin-grid--two">';
    okip_admin_number_field(__('Opacidad desde', 'okip'), $base_name . '[opacity_from]', $stage['opacity_from'], '', array('min' => 0, 'max' => 1, 'step' => .01));
    okip_admin_number_field(__('Opacidad hasta', 'okip'), $base_name . '[opacity_to]', $stage['opacity_to'], '', array('min' => 0, 'max' => 1, 'step' => .01));
    okip_admin_number_field(__('X desde px', 'okip'), $base_name . '[x_from]', $stage['x_from'], '', array('min' => -1000, 'max' => 1000, 'step' => .5));
    okip_admin_number_field(__('X hasta px', 'okip'), $base_name . '[x_to]', $stage['x_to'], '', array('min' => -1000, 'max' => 1000, 'step' => .5));
    okip_admin_number_field(__('Y desde px', 'okip'), $base_name . '[y_from]', $stage['y_from'], '', array('min' => -1000, 'max' => 1000, 'step' => .5));
    okip_admin_number_field(__('Y hasta px', 'okip'), $base_name . '[y_to]', $stage['y_to'], '', array('min' => -1000, 'max' => 1000, 'step' => .5));
    okip_admin_number_field(__('Scale desde', 'okip'), $base_name . '[scale_from]', $stage['scale_from'], '', array('min' => 0, 'max' => 5, 'step' => .01));
    okip_admin_number_field(__('Scale hasta', 'okip'), $base_name . '[scale_to]', $stage['scale_to'], '', array('min' => 0, 'max' => 5, 'step' => .01));
    okip_admin_number_field(__('Rotación desde', 'okip'), $base_name . '[rotate_from]', $stage['rotate_from'], '', array('min' => -360, 'max' => 360, 'step' => .5));
    okip_admin_number_field(__('Rotación hasta', 'okip'), $base_name . '[rotate_to]', $stage['rotate_to'], '', array('min' => -360, 'max' => 360, 'step' => .5));
    okip_admin_number_field(__('Blur desde px', 'okip'), $base_name . '[blur_from]', $stage['blur_from'], '', array('min' => 0, 'max' => 80, 'step' => .5));
    okip_admin_number_field(__('Blur hasta px', 'okip'), $base_name . '[blur_to]', $stage['blur_to'], '', array('min' => 0, 'max' => 80, 'step' => .5));
    echo '</div>';
    echo '</details>';
    echo '</fieldset>';
}

function okip_admin_motion_target_group($legend, $base_name, array $target_motion, $target, $with_playback = false)
{
    echo '<fieldset class="okip-admin-panel okip-admin-panel--nested">';
    echo '<legend>' . esc_html($legend) . '</legend>';
    okip_admin_motion_stage_fields(__('Entrada', 'okip'), $base_name . '[entry]', isset($target_motion['entry']) ? $target_motion['entry'] : array(), $target, 'entry');
    if ($with_playback) {
        okip_admin_motion_stage_fields(__('Reproducción', 'okip'), $base_name . '[playback]', isset($target_motion['playback']) ? $target_motion['playback'] : array(), $target, 'playback');
    }
    okip_admin_motion_stage_fields(__('Salida', 'okip'), $base_name . '[exit]', isset($target_motion['exit']) ? $target_motion['exit'] : array(), $target, 'exit');
    echo '</fieldset>';
}
