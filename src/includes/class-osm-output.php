<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** HTML allowlist shared by Gutenberg output boundaries. */
class OSM_Output {
    /**
     * Preserve the plugin's application controls while stripping executable markup.
     * @return array<string,array<string,bool>> WordPress KSES tags and attributes.
     */
    public static function allowed_html(): array {
        $allowed = wp_kses_allowed_html( 'post' );
        $attributes = [ 'class' => true, 'id' => true, 'role' => true, 'aria-*' => true, 'data-*' => true ];
        foreach ( [ 'form', 'fieldset', 'legend', 'label', 'input', 'button', 'select', 'option', 'textarea' ] as $tag ) {
            $allowed[ $tag ] = array_merge( $attributes, [
                'method' => true, 'action' => true, 'for' => true, 'name' => true, 'type' => true,
                'value' => true, 'required' => true, 'disabled' => true, 'readonly' => true,
                'min' => true, 'max' => true, 'maxlength' => true, 'tabindex' => true,
                'autocomplete' => true, 'checked' => true, 'inputmode' => true, 'pattern' => true,
                'rows' => true, 'selected' => true, 'placeholder' => true,
            ] );
        }
        return $allowed;
    }
}
