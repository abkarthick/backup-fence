jQuery(document).ready(function ($) {
    $('#create-backup').on('click', function (e) {
        e.preventDefault();

        const button = $(this);
        button.prop('disabled', true).text('Creating Backup...');

        $.ajax({
            type: 'POST',
            url: backupFence.ajax_url,
            data: {
                action: 'create_backup',
                nonce: backupFence.nonce,
            },
            success: function (response) {
                if (response.success) {
                    $('#backup-message').html('<div class="updated"><p>Backup created successfully! Files: ' + response.data.db_backup + ' and ' + response.data.zip_backup + '</p></div>');
                    location.reload(); // Reload to show the updated list
                } else {
                    $('#backup-message').html('<div class="error"><p>' + response.data + '</p></div>');
                }
            },
            error: function () {
                $('#backup-message').html('<div class="error"><p>There was an error creating the backup. Please try again.</p></div>');
            },
            complete: function () {
                button.prop('disabled', false).text('Create Backup');
            }
        });
    });
});
