<?php
/**
 * Plugin Name: Weekly Data Updater
 * Description: A plugin to fetch, process, and display weekly data.
 * Version: 1.3
 */

require ABSPATH . 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Schedule the event on plugin activation
function schedule_my_custom_event() {
    if (!wp_next_scheduled('my_custom_event')) {
        wp_schedule_event(time(), 'weekly', 'my_custom_event');
    }
}
register_activation_hook(__FILE__, 'schedule_my_custom_event');

// Hook the function to the custom event
add_action('my_custom_event', 'update_table_data');

// Calculate the fuel surcharge percentage based on the price
function calculateFuelSurchargePercentage($price) {
    $surchargePercentage = 0;
    if ($price >= 180.9 && $price <= 181.8) {
        $surchargePercentage = 26;
    } elseif ($price > 181.8) {
        $surchargePercentage = 26 + (floor($price - 180.9) * 0.25);
    } elseif ($price < 180.9) {
        $surchargePercentage = (floor(($price - 69.9) / 2) * 0.25) + 12;
    }

    return number_format($surchargePercentage, 2);
}

// Create a new table to store the weekly data upon plugin activation
function create_history_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'weekly_data';

    $sql = "CREATE TABLE $table_name (
        id INT AUTO_INCREMENT PRIMARY KEY,
        week_ending DATE,
        price DECIMAL(10, 2),
        ltl DECIMAL(5, 2),
        tl DECIMAL(5, 2)
    ) {$wpdb->get_charset_collate()};";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'create_history_table');

// Update the weekly data table with new data from an Excel file
function update_table_data() {
    $url = 'https://charting.kalibrate.com/WPPS/Diesel/Retail%20(Incl.%20Tax)/WEEKLY/2025/Diesel_Retail%20(Incl.%20Tax)_WEEKLY_2025.xlsx';

    $response = wp_remote_get($url);
    if (is_wp_error($response)) {
        error_log('Failed to download the Excel file.');
        return;
    }
    $file_content = wp_remote_retrieve_body($response);

    $file_path = plugin_dir_path(__FILE__) . 'data.xlsx';
    if (file_put_contents($file_path, $file_content) === false) {
        error_log('Failed to save the Excel file locally.');
        return;
    }
    
    try {
        $spreadsheet = IOFactory::load($file_path);
    } catch (Exception $e) {
        error_log('Error loading spreadsheet: ' . $e->getMessage());
        return;
    }
    $worksheet = $spreadsheet->getActiveSheet();

    global $wpdb;
    $table_name = $wpdb->prefix . 'weekly_data';

    $highestColumn = $worksheet->getHighestColumn();
    $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

    for ($col = 2; $col <= $highestColumnIndex; $col++) {
        $priceCell = $worksheet->getCellByColumnAndRow($col, 5);
        $dateCell = $worksheet->getCellByColumnAndRow($col, 3);

        if (!empty($priceCell->getValue())) {
            $week_ending = new DateTime($dateCell->getValue());
            $week_ending->modify('+3 days');

            $price = $priceCell->getValue();
            $ltl = calculateFuelSurchargePercentage($price);
            $tl = number_format($ltl + 15, 2);

            // Check if the entry for this week already exists
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE week_ending = %s",
                $week_ending->format('Y-m-d')
            ));

            if ($existing > 0) {
                // Update the existing record
                $wpdb->update(
                    $table_name,
                    ['price' => $price, 'ltl' => $ltl, 'tl' => $tl], // values
                    ['week_ending' => $week_ending->format('Y-m-d')], // where
                    ['%f', '%s', '%s'], // value formats
                    ['%s'] // where formats
                );
            } else {
                // Insert a new record
                $wpdb->insert(
                    $table_name,
                    [
                        'week_ending' => $week_ending->format('Y-m-d'),
                        'price' => $price,
                        'ltl' => $ltl,
                        'tl' => $tl
                    ],
                    ['%s', '%f', '%s', '%s']
                );
            }
        }
    }
}
// Define the shortcode to display the table
function table_shortcode() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'weekly_data';
    
    $results = $wpdb->get_results("SELECT DISTINCT *  FROM $table_name ORDER BY week_ending DESC LIMIT 6", ARRAY_A);
    if (empty($results)) {
        return "<p style='color: white;'>No data available.</p>";
    }
    
    // Find the nearest past date to today
    $currentDate = new DateTime();
    $nearestPastDate = null;
    foreach ($results as $result) {
        $date = new DateTime($result['week_ending']);
        if ($date <= $currentDate) {
            if ($nearestPastDate === null || $date > $nearestPastDate) {
                $nearestPastDate = $date;
            }
        }
    }
    
    $htmlTable = '<table style="width:100%;border-collapse: collapse; color: white;">';
    $htmlTable .= '<tr><th>Date</th><th>LTL</th><th>TL</th></tr>';
    
    foreach ($results as $row) {
        $weekEnding = new DateTime($row['week_ending']);
        $displayDate = $weekEnding->format('M jS'); // Format date without the year for display
        
        // Determine if this row is "Current"
        $isCurrent = $weekEnding == $nearestPastDate ? "<strong>Current</strong><br>" : ""; // "Current" indicator
        
        // Update table row display as per requirements
        $htmlTable .= "<tr><td>{$isCurrent}{$displayDate}</td><td>{$row['ltl']}%</td><td>{$row['tl']}%</td></tr>";
    }
    
    $htmlTable .= '</table>';
    return $htmlTable;
}
add_shortcode('my_table', 'table_shortcode');
