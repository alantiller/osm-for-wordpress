<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div <?php echo wp_kses_data( get_block_wrapper_attributes() ); ?>>
    <?php echo wp_kses( OSM_Blocks::render( 'waiting_list', $attributes ), OSM_Output::allowed_html() ); ?>
</div>
