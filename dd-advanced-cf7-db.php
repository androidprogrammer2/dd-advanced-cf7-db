<?php
/*
Plugin Name: DD Advanced CF7 DB
Description: Collects Contact Form 7 data and stores it in the database. Provides an admin interface to view and filter the data.
Version: 1.0
Author: Your Name
*/

register_activation_hook(__FILE__, 'ddacf7db_create_table');
function ddacf7db_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cf7_data';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        form_id mediumint(9) NOT NULL,
        submission_data longtext NOT NULL,
        user_ip varchar(100) NOT NULL,
        submitted_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

add_action('wpcf7_mail_sent', 'ddacf7db_save_cf7_data');
function ddacf7db_save_cf7_data($contact_form) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cf7_data';

    $submission = WPCF7_Submission::get_instance();
    if ($submission) {
        $form_id = $contact_form->id();
        $submission_data = json_encode($submission->get_posted_data());
        $user_ip = $submission->get_meta('remote_ip');

        $wpdb->insert(
            $table_name,
            array(
                'form_id' => $form_id,
                'submission_data' => $submission_data,
                'user_ip' => $user_ip,
                'submitted_at' => current_time('mysql'),
            )
        );
    }
}


// Create admin menu to view submissions
add_action('admin_menu', 'ddacf7db_admin_menu');
function ddacf7db_admin_menu() {
    add_menu_page(
        'CF7 Submissions',
        'CF7 Submissions',
        'manage_options',
        'ddacf7db-submissions',
        'ddacf7db_display_submissions'
    );
}

// Display submissions in the admin interface
function ddacf7db_display_submissions() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cf7_data';

    $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
    $form_id_filter = $form_id ? $wpdb->prepare('WHERE form_id = %d', $form_id) : '';

    $results = $wpdb->get_results("SELECT * FROM $table_name $form_id_filter ORDER BY submitted_at DESC");

    ?>
    <div class="wrap">
        <h1>CF7 Submissions</h1>
        <form method="get">
            <input type="hidden" name="page" value="ddacf7db-submissions">
            <select name="form_id">
                <option value="">Select Form</option>
                <?php
                $forms = WPCF7_ContactForm::find(array('include_inactive' => true));
                foreach ($forms as $form) {
                    echo '<option value="' . esc_attr($form->id()) . '"' . selected($form_id, $form->id(), false) . '>' . esc_html($form->title()) . '</option>';
                }
                ?>
            </select>
            <input type="submit" value="Filter">
        </form>

        <?php if ($results) : ?>
            <table class="wp-list-table widefat fixed">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Form ID</th>
                        <th>Submission Data</th>
                        <th>User IP</th>
                        <th>Submitted At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row) : ?>
                        <tr>
                            <td><?php echo esc_html($row->id); ?></td>
                            <td><?php echo esc_html($row->form_id); ?></td>
                            <td>
                                <?php
                                $data = json_decode($row->submission_data, true);
                                if (is_array($data)) :
                                ?>
                                    <table class="widefat">
                                        <tbody>
                                            <?php foreach ($data as $key => $value) : ?>
                                                <tr>
                                                    <td><strong><?php echo esc_html($key); ?></strong></td>
                                                    <td><?php echo esc_html(is_array($value) ? implode(', ', $value) : $value); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else : ?>
                                    <?php echo esc_html($row->submission_data); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($row->user_ip); ?></td>
                            <td><?php echo esc_html($row->submitted_at); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>No submissions found.</p>
        <?php endif; ?>
    </div>
    <?php
}

function ddacf7db_register_latest_entries_widget() {
    register_widget('DDACF7DB_Latest_Entries_Widget');
}
add_action('widgets_init', 'ddacf7db_register_latest_entries_widget');

class DDACF7DB_Latest_Entries_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'ddacf7db_latest_entries',
            'Latest CF7 Entries',
            array('description' => __('Displays the latest 10 Contact Form 7 entries.', 'text_domain')) // Args
        );
    }

    public function widget($args, $instance) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7_data';

        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY submitted_at DESC LIMIT 10");

        echo $args['before_widget'];
        echo $args['before_title'] . 'Latest CF7 Entries' . $args['after_title'];

        if ($results) {
            echo '<ul>';
            foreach ($results as $row) {
                $submission_data = json_decode($row->submission_data, true);
                $formatted_data = '';

                if (is_array($submission_data)) {
                    foreach ($submission_data as $key => $value) {
                        $formatted_data .= esc_html($key) . ': ' . (is_array($value) ? implode(', ', $value) : esc_html($value)) . '<br>';
                    }
                } else {
                    $formatted_data = esc_html($row->submission_data);
                }

                echo '<li>';
                echo '<strong>Form ID:</strong> ' . esc_html($row->form_id) . '<br>';
                echo '<strong>Submitted At:</strong> ' . esc_html($row->submitted_at) . '<br>';
                echo '<div>' . $formatted_data . '</div>';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No entries found.</p>';
        }

        echo $args['after_widget'];
    }

    public function form($instance) {
    }

    
    public function update($new_instance, $old_instance) {
        $instance = array();
        return $instance;
    }
}

function ddacf7db_add_dashboard_widget() {
    wp_add_dashboard_widget(
        'ddacf7db_dashboard_widget',          
        'Latest CF7 Entries',                 
        'ddacf7db_display_dashboard_widget' 
    );
}
add_action('wp_dashboard_setup', 'ddacf7db_add_dashboard_widget');

function ddacf7db_display_dashboard_widget() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cf7_data';

    $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY submitted_at DESC LIMIT 10");

    if ($results) {
        echo '<ul>';
        foreach ($results as $row) {
            $submission_data = json_decode($row->submission_data, true);
            $formatted_data = '';

            if (is_array($submission_data)) {
                foreach ($submission_data as $key => $value) {
                    $formatted_data .= esc_html($key) . ': ' . (is_array($value) ? implode(', ', $value) : esc_html($value)) . '<br>';
                }
            } else {
                $formatted_data = esc_html($row->submission_data);
            }

            echo '<li>';
            echo '<strong>Form ID:</strong> ' . esc_html($row->form_id) . '<br>';
            echo '<strong>Submitted At:</strong> ' . esc_html($row->submitted_at) . '<br>';
            echo '<div>' . $formatted_data . '</div>';
            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>No entries found.</p>';
    }
}
