<?php
if ( ! defined('ABSPATH') ) {
    exit; // Exit if accessed directly
}

// Regisztráljunk egy képméretet, mely nem vágja le a képet (crop = false),
// így a képarány megmarad.
add_action('after_setup_theme', function() {
    add_image_size('ebook_featured', 800, 9999, false);
});
