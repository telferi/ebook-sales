/**
 * Levél sablonok kezelésével kapcsolatos JavaScript funkcionalitás
 */
(function($) {
    'use strict';
    
    // DOM betöltés után futtatjuk a kódot
    $(document).ready(function() {
        // Sablon törlés kezelése
        $('.delete-template').on('click', function(e) {
            e.preventDefault();
            
            const templateId = $(this).data('id');
            
            if (confirm(ebook_mail_vars.delete_confirm)) {
                $.ajax({
                    url: ebook_mail_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'delete_mail_template',
                        template_id: templateId,
                        nonce: ebook_mail_vars.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            // Sikeres törlés esetén frissítjük az oldalt
                            alert(response.data);
                            window.location.reload();
                        } else {
                            // Hiba esetén megjelenítjük a hibaüzenetet
                            alert(response.data);
                        }
                    },
                    error: function() {
                        // AJAX hiba esetén
                        alert('Hiba történt a kérés feldolgozása közben!');
                    }
                });
            }
        });
    });
    
})(jQuery);
