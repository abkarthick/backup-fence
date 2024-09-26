# Backup Fence Plugin Documentation

## Overview
Backup Fence is a secure WordPress plugin designed to help you create backups of your WordPress database and files easily. It allows administrators to manage backups, ensuring that your website data is safe and easily retrievable.

## Features
- Create a backup of your WordPress database in SQL format.
- Create a compressed zip file containing your WordPress files and the SQL backup.
- Manage existing backups by downloading or deleting them.
- User-friendly interface for easy navigation and backup management.
- Secure your backups by allowing only administrators to access them.

## Installation
1. **Download the Plugin**: Download the `backup-fence` plugin ZIP file from the repository or GitHub.
2. **Install the Plugin**:
   - Log in to your WordPress admin panel.
   - Go to **Plugins > Add New**.
   - Click on **Upload Plugin** and select the downloaded ZIP file.
   - Click **Install Now** and then activate the plugin.
3. **Create Backup Directory**: Upon activation, the plugin will create a `backups` directory in your WordPress uploads folder (`/wp-content/uploads/backups/`) for storing backups.

## Usage
1. **Accessing the Plugin**: 
   - Navigate to **Backup Fence** in the WordPress admin menu.
  
2. **Creating a Backup**:
   - Click on the **Create Backup** button. 
   - The plugin will generate a backup of your database and create a zip file of your WordPress files.
   - A success message will appear once the backup is created.

3. **Managing Existing Backups**:
   - The **Existing Backups** section lists all your backups with options to download or delete.
   - To **download a backup**, click on the **Download ZIP** button next to the desired backup.
   - To **delete a backup**, click on the **Delete** button next to the backup you wish to remove. You will be prompted to confirm the deletion.

## Troubleshooting
- **Warning Messages**: If you encounter warnings such as "Cannot modify header information", this may be due to output being sent before headers are modified. Ensure that no extra whitespace or HTML is present before PHP code that modifies headers.
  
- **No Backups Found**: If no backups are displayed, ensure that backups have been created successfully and that the backups directory has the correct permissions.

## Security
- Only users with administrator roles can access the Backup Fence interface and manage backups, ensuring that sensitive data remains secure.

## Support
For any issues, questions, or feature requests, please open an issue on the GitHub repository or contact the plugin author directly.

## License
Backup Fence is licensed under the GPL v2 or later. Feel free to modify and distribute this plugin according to your needs.

## Changelog
- **Version 1.0**: Initial release of Backup Fence plugin with core backup functionalities.
