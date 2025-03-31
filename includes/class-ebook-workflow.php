<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Ebook Workflow kezelőosztály
 * 
 * Ez az osztály kezeli az automatikus munkafolyamatokat:
 * - Események: mi váltja ki a workflow-t (pl. ebook vásárlás, feliratkozás)
 * - Műveletek: mit csináljon a rendszer (pl. email küldés, jogosultság adás)
 * - Eredmények: mi legyen a végeredmény (pl. szerepkör módosítás)
 */
class Ebook_Workflow {

    // Az adatbázis tábla neve, ahol a workflow-kat tároljuk
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ebook_workflows';

        add_action('admin_init', array($this, 'create_workflow_table'));
        add_action('wp_ajax_save_ebook_workflow', array($this, 'save_workflow'));
        add_action('wp_ajax_delete_ebook_workflow', array($this, 'delete_workflow'));
    }

    /**
     * Létrehozza a workflow adatbázis táblát, ha még nem létezik
     */
    public function create_workflow_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            event varchar(100) NOT NULL,
            event_params longtext,
            action varchar(100) NOT NULL,
            action_params longtext,
            result varchar(100) NOT NULL,
            result_params longtext,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Visszaadja az elérhető események listáját
     * 
     * @return array Események listája
     */
    public function get_available_events() {
        // Ezt a listát később bővíthetjük, akár hook-on keresztül is
        $events = array(
            'ebook_purchase' => __('Ebook vásárlás', 'ebook-sales'),
            'subscription_started' => __('Feliratkozás kezdődött', 'ebook-sales'),
            'user_registration' => __('Felhasználó regisztrált', 'ebook-sales'),
            'form_submission' => __('Űrlap beküldése', 'ebook-sales')
        );

        return apply_filters('ebook_workflow_events', $events);
    }

    /**
     * Visszaadja az elérhető műveletek listáját
     * 
     * @return array Műveletek listája
     */
    public function get_available_actions() {
        // Ezt a listát később bővíthetjük, akár hook-on keresztül is
        $actions = array(
            'send_email' => __('Email küldés', 'ebook-sales'),
            'add_role' => __('Szerepkör hozzáadás', 'ebook-sales'),
            'remove_role' => __('Szerepkör elvétel', 'ebook-sales'),
            'create_user' => __('Felhasználó létrehozás', 'ebook-sales'),
            'update_meta' => __('Meta adat frissítés', 'ebook-sales')
        );

        return apply_filters('ebook_workflow_actions', $actions);
    }

    /**
     * Visszaadja az elérhető eredmények listáját
     * 
     * @return array Eredmények listája
     */
    public function get_available_results() {
        // Ezt a listát később bővíthetjük, akár hook-on keresztül is
        $results = array(
            'role_change' => __('Szerepkör módosítás', 'ebook-sales'),
            'content_access' => __('Tartalom elérés', 'ebook-sales'),
            'download_access' => __('Letöltés elérés', 'ebook-sales'),
            'discount' => __('Kedvezmény jóváírás', 'ebook-sales')
        );

        return apply_filters('ebook_workflow_results', $results);
    }

    /**
     * Visszaadja az összes mentett workflow-t
     * 
     * @return array Mentett workflow-k listája
     */
    public function get_workflows() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY created_at DESC");
    }

    /**
     * Lekéri az adott azonosítójú workflow-t
     * 
     * @param int $id Workflow azonosító
     * @return object|null Workflow adatok vagy null, ha nem található
     */
    public function get_workflow($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id));
    }

    /**
     * Workflow mentése AJAX kérés kezelő
     */
    public function save_workflow() {
        // Ellenőrizzük a nonce-t és a jogosultságokat
        check_ajax_referer('ebook_workflow_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Nincs megfelelő jogosultságod.', 'ebook-sales')));
        }

        $workflow_id = isset($_POST['workflow_id']) ? intval($_POST['workflow_id']) : 0;
        $name = sanitize_text_field($_POST['name']);
        $event = sanitize_text_field($_POST['event']);
        $event_params = isset($_POST['event_params']) ? wp_json_encode($_POST['event_params']) : '';
        $action = sanitize_text_field($_POST['action']);
        $action_params = isset($_POST['action_params']) ? wp_json_encode($_POST['action_params']) : '';
        $result = sanitize_text_field($_POST['result']);
        $result_params = isset($_POST['result_params']) ? wp_json_encode($_POST['result_params']) : '';
        $status = sanitize_text_field($_POST['status'] ?? 'active');

        global $wpdb;

        $data = array(
            'name' => $name,
            'event' => $event,
            'event_params' => $event_params,
            'action' => $action,
            'action_params' => $action_params,
            'result' => $result,
            'result_params' => $result_params,
            'status' => $status
        );

        $format = array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');

        if ($workflow_id > 0) {
            // Frissítünk egy meglévő workflow-t
            $wpdb->update(
                $this->table_name,
                $data,
                array('id' => $workflow_id),
                $format,
                array('%d')
            );
            $message = __('Munkafolyamat sikeresen frissítve!', 'ebook-sales');
        } else {
            // Új workflow létrehozása
            $wpdb->insert(
                $this->table_name,
                $data,
                $format
            );
            $workflow_id = $wpdb->insert_id;
            $message = __('Új munkafolyamat sikeresen létrehozva!', 'ebook-sales');
        }

        wp_send_json_success(array(
            'message' => $message,
            'workflow_id' => $workflow_id
        ));
    }

    /**
     * Workflow törlés AJAX kérés kezelő
     */
    public function delete_workflow() {
        // Ellenőrizzük a nonce-t és a jogosultságokat
        check_ajax_referer('ebook_workflow_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Nincs megfelelő jogosultságod.', 'ebook-sales')));
        }

        $workflow_id = isset($_POST['workflow_id']) ? intval($_POST['workflow_id']) : 0;
        
        if ($workflow_id <= 0) {
            wp_send_json_error(array('message' => __('Érvénytelen workflow azonosító.', 'ebook-sales')));
        }

        global $wpdb;
        $deleted = $wpdb->delete(
            $this->table_name,
            array('id' => $workflow_id),
            array('%d')
        );

        if ($deleted) {
            wp_send_json_success(array(
                'message' => __('Munkafolyamat sikeresen törölve!', 'ebook-sales')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Hiba történt a törlés során.', 'ebook-sales')
            ));
        }
    }

    /**
     * Workflow admin felület renderelése
     */
    public function render_admin_page() {
        $action = isset($_GET['workflow_action']) ? sanitize_text_field($_GET['workflow_action']) : '';
        $workflow_id = isset($_GET['workflow_id']) ? intval($_GET['workflow_id']) : 0;
        
        // CSS és JS a workflow adminhoz
        echo '<style>
            .workflow-form-container { margin-top: 20px; }
            .workflow-table { margin-top: 20px; }
            .workflow-form label { display: block; margin-top: 10px; font-weight: bold; }
            .workflow-form select, .workflow-form input[type="text"] { width: 100%; max-width: 400px; }
            .workflow-params { display: none; margin-top: 10px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd; }
            .workflow-active { color: green; }
            .workflow-inactive { color: red; }
        </style>';
        
        if ($action === 'edit' || $action === 'new') {
            // Szerkesztés/új létrehozása űrlap
            $this->render_workflow_form($workflow_id);
        } else {
            // Workflow-k listája
            $this->render_workflow_list();
        }
        
        // JavaScript a workflow adminhoz
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Esemény, művelet és eredmény kiválasztás kezelése
            $('#workflow-event, #workflow-action, #workflow-result').on('change', function() {
                var type = $(this).attr('id').replace('workflow-', '');
                $('.' + type + '-params').hide();
                $('#' + type + '-params-' + $(this).val()).show();
            });
            
            // A kiválasztott eseményhez tartozó paraméterek megjelenítése betöltéskor
            $('#workflow-event, #workflow-action, #workflow-result').trigger('change');
            
            // AJAX form küldés
            $('#workflow-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = $(this).serialize();
                formData += '&action=save_ebook_workflow&nonce=' + $('#workflow_nonce').val();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            window.location.href = '<?php echo admin_url('admin.php?page=ebook-mailing&tab=workflow'); ?>';
                        } else {
                            alert(response.data.message);
                        }
                    }
                });
            });
            
            // Törlés gomb
            $('.delete-workflow').on('click', function(e) {
                e.preventDefault();
                if (confirm('<?php _e('Biztosan törölni szeretnéd ezt a munkafolyamatot?', 'ebook-sales'); ?>')) {
                    var workflowId = $(this).data('id');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'delete_ebook_workflow',
                            workflow_id: workflowId,
                            nonce: $('#workflow_nonce').val()
                        },
                        success: function(response) {
                            if (response.success) {
                                alert(response.data.message);
                                window.location.reload();
                            } else {
                                alert(response.data.message);
                            }
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Workflow lista renderelése
     */
    private function render_workflow_list() {
        $workflows = $this->get_workflows();
        ?>
        <div class="wrap">
            <h2>
                <?php _e('Munkafolyamatok', 'ebook-sales'); ?>
                <a href="<?php echo admin_url('admin.php?page=ebook-mailing&tab=workflow&workflow_action=new'); ?>" class="page-title-action"><?php _e('Új hozzáadása', 'ebook-sales'); ?></a>
            </h2>
            
            <?php wp_nonce_field('ebook_workflow_nonce', 'workflow_nonce'); ?>
            
            <div class="workflow-table">
                <?php if (empty($workflows)) : ?>
                    <p><?php _e('Még nincsenek létrehozott munkafolyamatok.', 'ebook-sales'); ?></p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('ID', 'ebook-sales'); ?></th>
                                <th><?php _e('Név', 'ebook-sales'); ?></th>
                                <th><?php _e('Esemény', 'ebook-sales'); ?></th>
                                <th><?php _e('Művelet', 'ebook-sales'); ?></th>
                                <th><?php _e('Eredmény', 'ebook-sales'); ?></th>
                                <th><?php _e('Állapot', 'ebook-sales'); ?></th>
                                <th><?php _e('Létrehozva', 'ebook-sales'); ?></th>
                                <th><?php _e('Műveletek', 'ebook-sales'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $events = $this->get_available_events();
                            $actions = $this->get_available_actions();
                            $results = $this->get_available_results();
                            
                            foreach ($workflows as $workflow) : 
                                $event_name = isset($events[$workflow->event]) ? $events[$workflow->event] : $workflow->event;
                                $action_name = isset($actions[$workflow->action]) ? $actions[$workflow->action] : $workflow->action;
                                $result_name = isset($results[$workflow->result]) ? $results[$workflow->result] : $workflow->result;
                                $status_class = $workflow->status === 'active' ? 'workflow-active' : 'workflow-inactive';
                                $status_text = $workflow->status === 'active' ? __('Aktív', 'ebook-sales') : __('Inaktív', 'ebook-sales');
                            ?>
                                <tr>
                                    <td><?php echo esc_html($workflow->id); ?></td>
                                    <td><?php echo esc_html($workflow->name); ?></td>
                                    <td><?php echo esc_html($event_name); ?></td>
                                    <td><?php echo esc_html($action_name); ?></td>
                                    <td><?php echo esc_html($result_name); ?></td>
                                    <td class="<?php echo $status_class; ?>"><?php echo esc_html($status_text); ?></td>
                                    <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($workflow->created_at))); ?></td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=ebook-mailing&tab=workflow&workflow_action=edit&workflow_id=' . $workflow->id); ?>" class="button button-small"><?php _e('Szerkesztés', 'ebook-sales'); ?></a>
                                        <a href="#" class="button button-small delete-workflow" data-id="<?php echo $workflow->id; ?>"><?php _e('Törlés', 'ebook-sales'); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Workflow űrlap renderelése
     * 
     * @param int $workflow_id Workflow azonosító (0 = új létrehozása)
     */
    private function render_workflow_form($workflow_id = 0) {
        $workflow = null;
        
        if ($workflow_id > 0) {
            $workflow = $this->get_workflow($workflow_id);
            if (!$workflow) {
                echo '<div class="notice notice-error"><p>' . __('A munkafolyamat nem található!', 'ebook-sales') . '</p></div>';
                return;
            }
        }
        
        $events = $this->get_available_events();
        $actions = $this->get_available_actions();
        $results = $this->get_available_results();
        
        $title = $workflow_id > 0 ? __('Munkafolyamat szerkesztése', 'ebook-sales') : __('Új munkafolyamat létrehozása', 'ebook-sales');
        $button_text = $workflow_id > 0 ? __('Frissítés', 'ebook-sales') : __('Létrehozás', 'ebook-sales');
        
        // Paraméterek kinyerése
        $event_params = $workflow_id > 0 && !empty($workflow->event_params) ? json_decode($workflow->event_params, true) : array();
        $action_params = $workflow_id > 0 && !empty($workflow->action_params) ? json_decode($workflow->action_params, true) : array();
        $result_params = $workflow_id > 0 && !empty($workflow->result_params) ? json_decode($workflow->result_params, true) : array();
        
        ?>
        <div class="wrap">
            <h2><?php echo $title; ?></h2>
            
            <div class="workflow-form-container">
                <form id="workflow-form" class="workflow-form">
                    <?php wp_nonce_field('ebook_workflow_nonce', 'workflow_nonce'); ?>
                    <input type="hidden" name="workflow_id" value="<?php echo esc_attr($workflow_id); ?>">
                    
                    <div class="form-field">
                        <label for="workflow-name"><?php _e('Név', 'ebook-sales'); ?>:</label>
                        <input type="text" id="workflow-name" name="name" value="<?php echo $workflow ? esc_attr($workflow->name) : ''; ?>" required>
                    </div>
                    
                    <div class="form-field">
                        <label for="workflow-status"><?php _e('Állapot', 'ebook-sales'); ?>:</label>
                        <select id="workflow-status" name="status">
                            <option value="active" <?php selected($workflow ? $workflow->status : 'active', 'active'); ?>><?php _e('Aktív', 'ebook-sales'); ?></option>
                            <option value="inactive" <?php selected($workflow ? $workflow->status : 'active', 'inactive'); ?>><?php _e('Inaktív', 'ebook-sales'); ?></option>
                        </select>
                    </div>
                    
                    <div class="form-field">
                        <label for="workflow-event"><?php _e('Esemény', 'ebook-sales'); ?>:</label>
                        <select id="workflow-event" name="event" required>
                            <option value=""><?php _e('Válassz eseményt', 'ebook-sales'); ?></option>
                            <?php foreach ($events as $key => $label) : ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($workflow ? $workflow->event : '', $key); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Itt jönnek majd az esemény-specifikus paraméter mezők -->
                    <div id="event-params-ebook_purchase" class="event-params workflow-params">
                        <h4><?php _e('Ebook vásárlás esemény paraméterei', 'ebook-sales'); ?></h4>
                        <div class="form-field">
                            <label for="event-param-amount"><?php _e('Minimum összeg', 'ebook-sales'); ?>:</label>
                            <input type="number" id="event-param-amount" name="event_params[minimum_amount]" value="<?php echo isset($event_params['minimum_amount']) ? esc_attr($event_params['minimum_amount']) : '0'; ?>" min="0" step="0.01">
                        </div>
                    </div>
                    
                    <!-- További esemény paraméter blokkok más eseményekhez -->
                    
                    <div class="form-field">
                        <label for="workflow-action"><?php _e('Művelet', 'ebook-sales'); ?>:</label>
                        <select id="workflow-action" name="action" required>
                            <option value=""><?php _e('Válassz műveletet', 'ebook-sales'); ?></option>
                            <?php foreach ($actions as $key => $label) : ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($workflow ? $workflow->action : '', $key); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Itt jönnek majd a művelet-specifikus paraméter mezők -->
                    <div id="action-params-send_email" class="action-params workflow-params">
                        <h4><?php _e('Email küldés paraméterei', 'ebook-sales'); ?></h4>
                        <div class="form-field">
                            <label for="action-param-template"><?php _e('Email sablon', 'ebook-sales'); ?>:</label>
                            <select id="action-param-template" name="action_params[email_template]">
                                <option value=""><?php _e('Válassz sablont', 'ebook-sales'); ?></option>
                                <?php 
                                global $wpdb;
                                $templates = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}ebook_mail_templates");
                                foreach ($templates as $template) :
                                ?>
                                    <option value="<?php echo esc_attr($template->id); ?>" <?php selected(isset($action_params['email_template']) ? $action_params['email_template'] : '', $template->id); ?>><?php echo esc_html($template->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- További művelet paraméter blokkok más műveletekhez -->
                    
                    <div class="form-field">
                        <label for="workflow-result"><?php _e('Eredmény', 'ebook-sales'); ?>:</label>
                        <select id="workflow-result" name="result" required>
                            <option value=""><?php _e('Válassz eredményt', 'ebook-sales'); ?></option>
                            <?php foreach ($results as $key => $label) : ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($workflow ? $workflow->result : '', $key); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Itt jönnek majd az eredmény-specifikus paraméter mezők -->
                    <div id="result-params-role_change" class="result-params workflow-params">
                        <h4><?php _e('Szerepkör módosítás paraméterei', 'ebook-sales'); ?></h4>
                        <div class="form-field">
                            <label for="result-param-role"><?php _e('Új szerepkör', 'ebook-sales'); ?>:</label>
                            <select id="result-param-role" name="result_params[role]">
                                <option value=""><?php _e('Válassz szerepkört', 'ebook-sales'); ?></option>
                                <?php 
                                $editable_roles = get_editable_roles();
                                foreach ($editable_roles as $role => $details) :
                                    if ($role === 'administrator') continue;
                                ?>
                                    <option value="<?php echo esc_attr($role); ?>" <?php selected(isset($result_params['role']) ? $result_params['role'] : '', $role); ?>><?php echo esc_html(translate_user_role($details['name'])); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- További eredmény paraméter blokkok más eredményekhez -->
                    
                    <div class="submit-button" style="margin-top: 20px;">
                        <button type="submit" class="button button-primary"><?php echo $button_text; ?></button>
                        <a href="<?php echo admin_url('admin.php?page=ebook-mailing&tab=workflow'); ?>" class="button"><?php _e('Vissza', 'ebook-sales'); ?></a>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Workflow végrehajtása adott eseményre
     * 
     * @param string $event Esemény neve
     * @param array $params Paraméterek
     */
    public function execute_workflows($event, $params = array()) {
        global $wpdb;
        
        // Aktív workflow-k keresése a megadott eseményre
        $workflows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE event = %s AND status = 'active'",
                $event
            )
        );
        
        if (empty($workflows)) {
            return;
        }
        
        foreach ($workflows as $workflow) {
            // Esemény paraméterek ellenőrzése
            $event_params = json_decode($workflow->event_params, true);
            if (!$this->validate_event_params($event_params, $params)) {
                continue; // Ha nem teljesülnek a feltételek, ugorjunk a következő workflow-ra
            }
            
            // Művelet végrehajtása
            $action_params = json_decode($workflow->action_params, true);
            $this->execute_action($workflow->action, $action_params, $params);
            
            // Eredmény beállítása
            $result_params = json_decode($workflow->result_params, true);
            $this->apply_result($workflow->result, $result_params, $params);
        }
    }
    
    /**
     * Esemény paraméterek validálása
     * 
     * @param array $event_params Esemény paraméterek
     * @param array $params Aktuális paraméterek
     * @return bool Érvényesek-e a paraméterek
     */
    private function validate_event_params($event_params, $params) {
        // Itt lehet ellenőrizni az egyes eseménytípusok paramétereit
        // Pl. ebook_purchase eseménynél ellenőrizni, hogy az összeg nagyobb-e a minimum összegnél
        
        // Alap implementáció: mindig true
        return true;
    }
    
    /**
     * Művelet végrehajtása
     * 
     * @param string $action Művelet neve
     * @param array $action_params Művelet paraméterek
     * @param array $params Aktuális paraméterek
     */
    private function execute_action($action, $action_params, $params) {
        switch ($action) {
            case 'send_email':
                $this->action_send_email($action_params, $params);
                break;
            case 'add_role':
                $this->action_add_role($action_params, $params);
                break;
            // További műveletek...
        }
        
        // Lehetőség mások számára, hogy saját műveleteket futtassanak
        do_action('ebook_workflow_execute_action', $action, $action_params, $params);
    }
    
    /**
     * Email küldés művelet
     */
    private function action_send_email($action_params, $params) {
        if (empty($action_params['email_template']) || empty($params['user_email'])) {
            return;
        }
        
        global $wpdb;
        $template = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ebook_mail_templates WHERE id = %d",
            $action_params['email_template']
        ));
        
        if (!$template) {
            return;
        }
        
        $subject = $template->subject;
        $content = $template->content;
        
        // Placeholder-ek cseréje
        foreach ($params as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $subject = str_replace('{{' . $key . '}}', $value, $subject);
                $content = str_replace('{{' . $key . '}}', $value, $content);
            }
        }
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($params['user_email'], $subject, $content, $headers);
    }
    
    /**
     * Szerepkör hozzáadás művelet
     */
    private function action_add_role($action_params, $params) {
        if (empty($action_params['role']) || empty($params['user_id'])) {
            return;
        }
        
        $user = get_user_by('ID', $params['user_id']);
        if ($user) {
            $user->add_role($action_params['role']);
        }
    }
    
    /**
     * Eredmény alkalmazása
     * 
     * @param string $result Eredmény neve
     * @param array $result_params Eredmény paraméterek
     * @param array $params Aktuális paraméterek
     */
    private function apply_result($result, $result_params, $params) {
        switch ($result) {
            case 'role_change':
                $this->result_role_change($result_params, $params);
                break;
            case 'content_access':
                $this->result_content_access($result_params, $params);
                break;
            // További eredmények...
        }
        
        // Lehetőség mások számára, hogy saját eredményeket alkalmazzanak
        do_action('ebook_workflow_apply_result', $result, $result_params, $params);
    }
    
    /**
     * Szerepkör módosítás eredmény
     */
    private function result_role_change($result_params, $params) {
        if (empty($result_params['role']) || empty($params['user_id'])) {
            return;
        }
        
        $user = get_user_by('ID', $params['user_id']);
        if ($user) {
            // Régi szerepkörök eltávolítása, kivéve az administrator-t
            $roles = $user->roles;
            foreach ($roles as $role) {
                if ($role !== 'administrator') {
                    $user->remove_role($role);
                }
            }
            
            // Új szerepkör hozzáadása
            $user->add_role($result_params['role']);
        }
    }
    
    /**
     * Tartalom elérés eredmény
     */
    private function result_content_access($result_params, $params) {
        if (empty($result_params['content_id']) || empty($params['user_id'])) {
            return;
        }
        
        // Itt lehetne implementálni a tartalom hozzáférés logikát
        // Pl. menteni a felhasználó metaadataihoz a tartalom ID-t
        update_user_meta($params['user_id'], '_ebook_access_' . $result_params['content_id'], '1');
    }
}
