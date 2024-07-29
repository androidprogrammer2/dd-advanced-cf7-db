<?php
function ddacf7db_get_unique_form_keys($form_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cf7_data';

    $results = $wpdb->get_results($wpdb->prepare("SELECT submission_data FROM $table_name WHERE form_id = %d", $form_id));
    $keys = [];

    echo "<pre>";
    print_r($results);
    echo "</pre>";
    // exit;
    
    foreach ($results as $row) {
        $data = json_decode($row->submission_data, true);
        if (is_array($data)) {
            $keys = array_merge($keys, array_keys($data));
        }
    }

    return array_unique($keys);
}


function ddacf7db_display_submissions() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cf7_data';

    $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
    $form_id_filter = $form_id ? $wpdb->prepare('WHERE form_id = %d', $form_id) : '';

    $results = $wpdb->get_results("SELECT * FROM $table_name $form_id_filter ORDER BY submitted_at DESC");

    $form_keys = $form_id ? ddacf7db_get_unique_form_keys($form_id) : [];
    ?>
    <div class="wrap">
        <h1><?php _e('CF7 Submissions'); ?></h1>
        <form method="get">
            <input type="hidden" name="page" value="ddacf7db-submissions">
            <select name="form_id" onchange="this.form.submit()">
                <option value=""><?php _e('Select Form' , '');?></option>
                <?php
                // Forms dropdown
                $forms = WPCF7_ContactForm::find(array('include_inactive' => true));
                foreach ($forms as $form) {
                    echo '<option value="' . esc_attr($form->id()) . '"' . selected($form_id, $form->id(), false) . '>' . esc_html($form->title()) . '</option>';
                }
                ?>
            </select>
        </form>

        <?php if ($results) : ?>
            <table class="wp-list-table widefat fixed">
                <thead>
                    <tr>
                        <th><?php _e('ID' ,''); ?></th>
                        <th><?php _e('View' ,''); ?></th>
                        <th><?php _e('Form ID' ,''); ?></th>
                        <?php foreach ($form_keys as $key) : ?>
                            <th><?php echo esc_html($key); ?></th>
                        <?php endforeach; ?>
                        <th><?php _e('User IP' ,''); ?></th>
                        <th><?php _e('Submitted At' ,''); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row) : ?>
                        <tr>
                            <td><?php echo esc_html($row->id); ?></td>
                            <td>
                                <a href="#" class="view-form-data" data-id="<?php echo esc_attr($row->id); ?>" data-form_data="<?php echo esc_attr($row->submission_data); ?>">
                                    <span class="dashicons dashicons-visibility"></span>
                                </a>
                            </td>
                            <td><?php echo esc_html($row->form_id); ?></td>
                            <?php
                            $data = json_decode($row->submission_data, true);
                            foreach ($form_keys as $key) :
                                ?>
                                <td><?php echo isset($data[$key]) ? esc_html(is_array($data[$key]) ? implode(', ', $data[$key]) : $data[$key]) : 'N/A'; ?></td>
                            <?php endforeach; ?>
                            <td><?php echo esc_html($row->user_ip); ?></td>
                            <td><?php echo esc_html($row->submitted_at); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php _e('No submissions found.' , ''); ?></p>
        <?php endif; ?>
    </div>

    <div id="form-data-popup" class="form-data-popup" style="display:none;">
        <div class="form-data-popup-content">
            <span class="form-data-popup-close">&times;</span>
            <h2><?php _e('Form Data' , ''); ?></h2>
            <div id="form-data-content"></div>
        </div>
    </div>
    <?php
}