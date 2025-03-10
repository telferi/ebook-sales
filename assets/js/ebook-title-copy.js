/// filepath: /home/telferenc/GitMunkamenetek/ebook-sales/assets/js/ebook-title-copy.js
jQuery(document).ready(function($) {
    // Amikor változik a kiválasztott ebook fájl, ha a title mező üres, akkor töltsük be a fájlnév alapján
    $('#ebook_file').on('change', function() {
        var titleField = $('#title');
        if (this.files.length > 0 && $.trim(titleField.val()) === '') {
            var file = this.files[0];
            var filename = file.name;
            var baseName = filename.replace(/\.[^/.]+$/, "");
            var newTitle = baseName.charAt(0).toUpperCase() + baseName.slice(1);
            titleField.val(newTitle);
        }
    });
});