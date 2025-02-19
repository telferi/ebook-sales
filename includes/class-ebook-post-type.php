<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Ebook_Post_Type {

    public function __construct() {
        add_action('init', array($this, 'register_ebook_post_type'));
    }

    public function register_ebook_post_type() {
        $labels = array(
            'name'                  => _x('Ebooks', 'Post Type General Name', 'ebook-sales'),
            'singular_name'         => _x('Ebook', 'Post Type Singular Name', 'ebook-sales'),
            'menu_name'             => __('Ebooks', 'ebook-sales'),
            'name_admin_bar'        => __('Ebook', 'ebook-sales'),
            'archives'              => __('Ebook Archives', 'ebook-sales'),
            'attributes'            => __('Ebook Attributes', 'ebook-sales'),
            'parent_item_colon'     => __('Parent Ebook:', 'ebook-sales'),
            'all_items'             => __('All Ebooks', 'ebook-sales'),
            'add_new_item'          => __('Add New Ebook', 'ebook-sales'),
            'add_new'               => __('Add New', 'ebook-sales'),
            'new_item'              => __('New Ebook', 'ebook-sales'),
            'edit_item'             => __('Edit Ebook', 'ebook-sales'),
            'update_item'           => __('Update Ebook', 'ebook-sales'),
            'view_item'             => __('View Ebook', 'ebook-sales'),
            'view_items'            => __('View Ebooks', 'ebook-sales'),
            'search_items'          => __('Search Ebook', 'ebook-sales'),
            'not_found'             => __('Not found', 'ebook-sales'),
            'not_found_in_trash'    => __('Not found in Trash', 'ebook-sales'),
            'featured_image'        => __('Featured Image', 'ebook-sales'),
            'set_featured_image'    => __('Set featured image', 'ebook-sales'),
            'remove_featured_image' => __('Remove featured image', 'ebook-sales'),
            'use_featured_image'    => __('Use as featured image', 'ebook-sales'),
            'insert_into_item'      => __('Insert into ebook', 'ebook-sales'),
            'uploaded_to_this_item' => __('Uploaded to this ebook', 'ebook-sales'),
            'items_list'            => __('Ebooks list', 'ebook-sales'),
            'items_list_navigation' => __('Ebooks list navigation', 'ebook-sales'),
            'filter_items_list'     => __('Filter ebooks list', 'ebook-sales'),
        );
        $args = array(
            'label'                 => __('Ebook', 'ebook-sales'),
            'description'           => __('Ebook post type for selling ebooks', 'ebook-sales'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'thumbnail'),
            'taxonomies'            => array('category', 'post_tag'),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => 'ebook-sales', // Fontos: a saját menünk alá kerüljön
            'menu_position'         => 5,
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
        );
        register_post_type('ebook', $args);
    }
}

new Ebook_Post_Type();
