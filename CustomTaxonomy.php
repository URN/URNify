<?php

class CustomTaxonomy {
    private $name;
    private $slug;
    private $plural_name;
    private $plural_slug;
    private $options = array();

    public function get_name() {
        return $this->name;
    }

    public function get_plural_name() {
        return $this->plural_name;
    }

    public function get_slug() {
        return $this->slug;
    }

    public function get_plural_slug() {
        return $this->plural_slug;
    }

    public function get_options() {
        return $this->options;
    }

    public function __construct($name, $plural_name, $icon, $menu_position, $options) {
        $this->name = $name;
        $this->plural_name = $plural_name;
        $this->slug = strtolower($name);
        $this->plural_slug = strtolower($plural_name);
        $this->options = $options;

        // Initialise shows when WP has started loading
        add_action('init', array($this, 'create_taxonomy'));

        // Add a link to Shows under the dashboard admin menu
        add_action('admin_menu', function () use ($icon, $menu_position) {
            add_menu_page($this->get_plural_name(), $this->get_plural_name(), 'add_users',
                          'edit-tags.php?taxonomy=' . $this->get_plural_slug(), null,
                          $icon, $menu_position);
        });

        // Display shows and podcasts that the user is part of under their profile
        add_action('show_user_profile', array($this, 'display_membership'));
        add_action('edit_user_profile', array($this, 'display_membership'));

        // Save any changes to show/podcast membership after any updates to profile
        // pages are made
        add_action('personal_options_update', array($this, 'save_memberships'));
        add_action('edit_user_profile_update', array($this, 'save_memberships'));

        // Fix menu item bug
        // http://justintadlock.com/archives/2011/10/20/custom-user-taxonomies-in-wordpress#comment-568682
        // https://gist.github.com/codekipple/2420042
        add_filter('parent_file', array($this, 'fix_taxonomy_menu_jumping'));
    }

    public function create_taxonomy() {
        $args = array(
            'hierarchical'      => true,
            'show_in_menu'      => false,
            'labels'            => $this->get_custom_taxonomy_lables($this->get_name()),
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => $this->get_slug())
        );

        // Create a new taxonomy to manage Shows
        register_taxonomy($this->get_plural_slug(), array('user', 'post'), $args);

        // Add all custom show options to the 'add show' page
        add_action($this->get_plural_slug() . '_add_form_fields', function($term) {
            foreach ($this->options as $option) {
                echo $option->get_add_form_output();
            }
        });

        // Add all custom show options to the 'edit show' page
        add_action($this->get_plural_slug() . '_edit_form_fields', function($term) {
            foreach ($this->options as $option) {
                $current_value = get_option($this->get_slug() . '_' . $term->term_id .'_custom_option_' . $option->get_slug());
                echo $option->get_edit_form_output($current_value);
            }
        });

        // Display custom show options on add and edit show pages
        add_action('create_' . $this->get_plural_slug(), array($this, 'save_custom_options'));
        add_action('edited_' . $this->get_plural_slug(), array($this, 'save_custom_options'));
    }

    // Save the show's custom options (e.g Facebook link) to the database.
    public function save_custom_options($term_id) {
        foreach($this->options as $option) {
            $slug = $option->get_slug();

            if (isset($_POST[$slug])) {
                error_log($_POST);
                $option->save($this->get_slug(), $term_id, array_map('stripslashes_deep', $_POST));
            }
            else {
                $option->save($this->get_slug(), $term_id, null);
            }
        }
    }

    // Get an array of required labels for a new taxonomy
    // $name must be singular and capitalised, eg. Show or Podcast, not shows or Podcasts
    private function get_custom_taxonomy_lables($name) {
        $lower_case = strtolower($name);

        $labels = array(
            "name"              => "${name}s",
            "singular_name"     => "${name}",
            "search_items"      => "Search ${name}s",
            "all_items"         => "All ${name}s",
            "parent_item"       => "Parent ${name}",
            "parent_item_colon" => "Parent ${name}:",
            "edit_item"         => "Edit ${name}",
            "add_new_item"      => "Add New ${name}",
            "new_item_name"     => "New ${name} name",
            "view_item"         => "View ${name}",
            "update_item"       => "Update ${name}",
            "menu_name"         => "${name}s",
            "popular_items"         => null,
            "add_or_remove_items"   => "Add or remove ${lower_case}s",
            "choose_from_most_used" => "Choose from the most used ${lower_case}s",
            "not_found"             => "No ${lower_case}s found"
        );

        return $labels;
    }

    // Get an array containing the IDs of all the terms (shows/podcasts) the user
    // is a member of
    private function get_users_taxonomy_ids($user_id, $taxonomy) {
        $terms = wp_get_object_terms($user_id, $taxonomy);
        $term_ids = array();
        foreach ($terms as $term) {
            $term_ids[] = $term->term_id;
        }
        return $term_ids;
    }

    // Display a list of taxonomy terms (shows/podcasts etc.) that the user is a member of
    public function display_membership($user) {
        $taxonomy = $this->get_plural_slug();
        $users_term_ids = $this->get_users_taxonomy_ids($user->ID, $taxonomy);
        $all_terms = get_terms($taxonomy, array('hide_empty'=>false));
        echo "<h3>" . ucfirst($taxonomy) . "</h3>";

        if (current_user_can('editor') || current_user_can('administrator')) {
            // For every term (show/podcast), print a label and checkbox of user membership
            foreach ($all_terms as $term) {
                $term_id = $term->term_id;
                $is_member_of_taxonomy = in_array($term->term_id, $users_term_ids); ?>
                <input type="checkbox"
                       id="user-<?php echo $taxonomy; ?>-<?php echo $term_id ?>"
                       <?php if($is_member_of_taxonomy) echo 'checked=checked';?>
                       name="user_<?php echo $taxonomy; ?>[]"
                       value="<?php echo $term_id;?>">
                <?php
                echo "<label for=\"user-${taxonomy}-${term_id}\">{$term->name}</label>";
                echo '<br>';
            }
            if (count($all_terms) == 0) {
                echo '<i>No shows have been created</i>';
            }
        }
        else {
            // If the user isn't an editor or admin, only show the shows/podcasts that
            // their a member of, and make it un-editable (no checkboxes)
            echo '<ul>';
            $hasMembership = false;
            foreach( $all_terms as $term ) {
                if(in_array( $term->term_id, $users_term_ids )) {
                    echo "<li>{$term->name}</li>";
                    $hasMembership = true;
                }
            }
            if (!$hasMembership || count($all_terms) == 0) {
                echo "<li><i>Not a member of any ${taxonomy}</i></li>";
            }
            echo '</ul>';
        }
    }

    // Save POSTED memberships of shows/podcasts to the database
    public function save_memberships($user_id) {
        if ( current_user_can('editor') || current_user_can('administrator') ) {
            $user_show_terms = isset($_POST['user_shows']) ? $_POST['user_shows'] : array();

            $show_terms = array_unique(array_map('intval', $user_show_terms));

            wp_set_object_terms($user_id, $show_terms, 'shows', false);

            //make sure you clear the term cache
            clean_object_term_cache($user_id, 'shows');
        }
    }

    // The highlighted 'current item' doesn't stick on Shows like it should
    public function fix_taxonomy_menu_jumping($parent_file = '') {
        global $pagenow;

        if (!empty($_GET['taxonomy']) && $pagenow == 'edit-tags.php') {
            if ($_GET['taxonomy'] == 'shows') {
                $parent_file = 'edit-tags.php?taxonomy=shows';
            }
            else if ($_GET['taxonomy'] == 'podcasts') {
                $parent_file = 'edit-tags.php?taxonomy=podcasts';
            }
        }

        return $parent_file;
    }
}
