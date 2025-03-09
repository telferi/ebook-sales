jQuery(document).ready(function($) {
    $('#ebook_file').on('change', function() {
        var titleField = $('#title');
        if ($.trim(titleField.val()) === '') {
            var file = this.files[0];
            if (file) {
                var filename = file.name;
                var baseName = filename.replace(/\.[^/.]+$/, "");
                var newTitle = baseName.charAt(0).toUpperCase() + baseName.slice(1);
                titleField.val(newTitle);
            }
        }
    });
});