<?php
/*
Plugin Name: Backup Fence
Description: A secure backup plugin that allows only administrators to download and manage backups.
Version: 1.0
Author: Karthick
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Start session and output buffering
session_start();
ob_start();

// Create a backup directory on plugin activation
function backup_fence_activate()
{
    $upload_dir = wp_upload_dir()['basedir'] . '/backups/';
    if (!file_exists($upload_dir)) {
        wp_mkdir_p($upload_dir);
    }
}
register_activation_hook(__FILE__, 'backup_fence_activate');

// Add admin menu for the plugin
function backup_fence_menu()
{
    add_menu_page('Backup Fence', 'Backup Fence', 'manage_options', 'backup_fence', 'backup_fence_page', 'dashicons-backup', 6);
}
add_action('admin_menu', 'backup_fence_menu');

// Backup function
function backup_fence_create_backup()
{
    global $wpdb;

    // Define backup file names
    $timestamp = current_time('Y-m-d_H-i-s');
    $db_backup_file = 'wp-backup-' . $timestamp . '.sql';
    $zip_backup_file = 'wp-backup-' . $timestamp . '.zip';
    $upload_dir = wp_upload_dir()['basedir'] . '/backups/';

    // Create SQL backup
    $db_backup_path = $upload_dir . $db_backup_file;
    $backup_file = fopen($db_backup_path, 'w');

    if (!$backup_file) {
        $_SESSION['backup_message'] = 'Failed to create SQL backup file.';
        return false;
    }

    // Get all tables and write CREATE TABLE and INSERT statements
    $tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);
    foreach ($tables as $table) {
        $table_name = $table[0];

        // Get the create statement for the table
        $create_table = $wpdb->get_row("SHOW CREATE TABLE $table_name", ARRAY_N);
        fwrite($backup_file, $create_table[1] . ";\n\n");

        // Get all rows from the table
        $rows = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
        foreach ($rows as $row) {
            $values = array_map(function ($value) {
                return "'" . esc_sql($value) . "'";
            }, array_values($row));

            fwrite($backup_file, "INSERT INTO $table_name VALUES (" . implode(", ", $values) . ");\n");
        }
        fwrite($backup_file, "\n\n");
    }

    fclose($backup_file);

    // Create a zip archive of the WordPress files and the SQL file
    $zip = new ZipArchive();
    if ($zip->open($upload_dir . $zip_backup_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        $_SESSION['backup_message'] = 'Failed to create zip file.';
        return false;
    }

    // Add SQL file to the zip
    $zip->addFile($db_backup_path, $db_backup_file);

    // Add WordPress files to zip
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(ABSPATH));
    foreach ($files as $file) {
        if (is_file($file) && strpos($file, '/wp-admin/') === false && strpos($file, '/wp-includes/') === false) { // Exclude sensitive folders
            $zip->addFile(realpath($file), str_replace(ABSPATH, '', $file));
        }
    }

    $zip->close();

    // Store success message
    $_SESSION['backup_message'] = 'Backup created successfully!';

    // Return an array of backup file names
    return [
        'db_backup' => $db_backup_file,
        'zip_backup' => $zip_backup_file,
    ];
}

// Main backup page
function backup_fence_page()
{
    ?>
    <div class="wrap">
        <h1>Backup Fence</h1>
        <div id="backup-message">
            <?php
            // Display backup message
            if (isset($_SESSION['backup_message'])) {
                echo '<div class="notice notice-info"><p>' . esc_html($_SESSION['backup_message']) . '</p></div>';
                unset($_SESSION['backup_message']); // Clear the message after displaying
            }
            ?>
        </div>
        <form method="post" action="">
            <input type="submit" name="create_backup" class="button button-primary my-2" value="Create Backup">
        </form>
        <?php backup_fence_display_backups(); ?>

        <?php
        // Create backup if the button is clicked
        if (isset($_POST['create_backup'])) {
            backup_fence_create_backup();
            // Redirect back to the main page
            wp_redirect(admin_url('admin.php?page=backup_fence'));
            exit; // Always exit after redirection
        }
        ?>
    </div>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.delete-backup').forEach(function (button) {
                button.addEventListener('click', function (e) {
                    if (!confirm('Are you sure you want to delete this backup?')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
    <?php
}

function backup_fence_display_backups()
{
    $backup_dir = wp_upload_dir()['basedir'] . '/backups/';
    $backup_files = glob($backup_dir . '*.{zip,sql}', GLOB_BRACE);

    echo '<h3>Existing Backups</h3>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Backup File Name</th>';  // Column for ZIP backup file name
    echo '<th>Created At</th>';          // Column for creation date
    echo '<th>Action</th>';              // Column for actions
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    // Initialize arrays to store SQL and ZIP file names
    $sql_files = [];
    $zip_files = [];

    // Separate SQL and ZIP files
    foreach ($backup_files as $file) {
        $file_name = basename($file);
        $created_at = date('Y-m-d H:i:s', filemtime($file)); // When the file was created

        if (pathinfo($file_name, PATHINFO_EXTENSION) === 'sql') {
            $sql_files[$file_name] = $created_at;
        } elseif (pathinfo($file_name, PATHINFO_EXTENSION) === 'zip') {
            $zip_files[$file_name] = $created_at;
        }
    }

    // Display the backups
    foreach ($zip_files as $zip_name => $created_at) {
        // Find corresponding SQL name, if it exists
        $sql_name = array_key_first(array_filter($sql_files, function ($key) use ($zip_name) {
            return strpos($key, pathinfo($zip_name, PATHINFO_FILENAME)) === 0;
        }));

        echo '<tr>';
        echo '<td>' . esc_html($zip_name) . '</td>';  // ZIP backup file name
        echo '<td>' . esc_html($created_at) . '</td>'; // Creation date
        echo '<td>';
        echo '<a href="' . esc_url(admin_url('admin.php?page=backup_fence&download=' . $zip_name)) . '" class="button">Download ZIP</a> ';
        echo '<a href="' . esc_url(admin_url('admin.php?page=backup_fence&delete=' . $zip_name)) . '" class="button delete-backup">Delete</a>';
        echo '</td>';
        echo '</tr>';
    }

    // Display a message if no backups found
    if (empty($zip_files)) {
        echo '<tr>';
        echo '<td colspan="3">No backups found.</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
}

// Download backup file
function backup_fence_download_file($file_name)
{
    $file_path = wp_upload_dir()['basedir'] . '/backups/' . $file_name;
    if (file_exists($file_path)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
        exit;
    }
}

// Delete backup file
function backup_fence_delete_file($file_name)
{
    $file_path = wp_upload_dir()['basedir'] . '/backups/' . $file_name;
    if (file_exists($file_path)) {
        unlink($file_path);
        $_SESSION['backup_message'] = 'Backup deleted successfully!';
    } else {
        $_SESSION['backup_message'] = 'Backup file does not exist.';
    }
}

// Handle requests for downloading and deleting backups
function backup_fence_handle_requests()
{
    if (isset($_GET['download'])) {
        backup_fence_download_file($_GET['download']);
    }

    if (isset($_GET['delete'])) {
        backup_fence_delete_file($_GET['delete']);
        wp_redirect(admin_url('admin.php?page=backup_fence'));
        exit;
    }
}
add_action('admin_init', 'backup_fence_handle_requests');
?>