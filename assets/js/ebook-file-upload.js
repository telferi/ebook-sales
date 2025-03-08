jQuery(document).ready(function($) {
    $('#ebook_file_save').on('click', function(e) {
        e.preventDefault();

        // Ellenőrizd, hogy a WordPress title mezője (#title) nincs-e üresen
        var titleField = $('#title');
        var ebookInput = $('#ebook_file')[0];
        if (ebookInput.files.length === 0) {
            alert('Kérjük, válassza ki az ebook fájlt!');
            return;
        }
        var file = ebookInput.files[0];
        // Ha a title mező üres (csak whitespace), akkor a fájl nevét használjuk címként
        if ($.trim(titleField.val()) === '') {
            var filename = file.name;
            var baseName = filename.replace(/\.[^/.]+$/, "");
            var newTitle = baseName.charAt(0).toUpperCase() + baseName.slice(1);
            titleField.val(newTitle);
        }

        // Ellenőrizzük a borító kép kiválasztása
        var coverInput = $('#cover_image')[0];
        if (coverInput.files.length === 0) {
            alert('Kérjük, válassza ki a borító képet!');
            return;
        }
        var coverFile = coverInput.files[0];

        // Összeállítjuk az AJAX formData-t
        var formData = new FormData();
        formData.append('ebook_file', file);
        formData.append('cover_image', coverFile);
        formData.append('post_id', ebook_post_data.post_id); // A post_id-t dinamikusan az enqueueolt változóból olvassuk
        formData.append('action', 'save_ebook_file_ajax');
        formData.append('ebook_file_nonce', ebook_post_data.nonce);

        $.ajax({
            url: ebook_post_data.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#ebook_file_message').html('<span style="color:green;">' + response.data.message + '</span>');
                } else {
                    $('#ebook_file_message').html('<span style="color:red;">' + response.data.message + '</span>');
                }
            },
            error: function() {
                $('#ebook_file_message').html('<span style="color:red;">Fájl feltöltési hiba</span>');
            }
        });
    });
});