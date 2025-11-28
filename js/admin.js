<<<<<<< HEAD
// admin.js
import { reloadPlayerData } from './player.js';

export function initAdminForms() {
    $(document).on('submit', '#player-edit-form', function(e) {
        e.preventDefault();
        const $form = $(this);

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            success: function() {
                reloadPlayerData();
                $('#edit-message').html('<p style="color:#2ecc71;">Spieler erfolgreich aktualisiert!</p>');
            },
            error: function(xhr, status, error) {
                console.error('Fehler:', status, error);
                $('#edit-message').html('<p style="color:#e74c3c;">Fehler beim Speichern!</p>');
            }
        });
    });
}
=======
// admin.js
import { reloadPlayerData } from './player.js';

export function initAdminForms() {
    $(document).on('submit', '#player-edit-form', function(e) {
        e.preventDefault();
        const $form = $(this);

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            success: function() {
                reloadPlayerData();
                $('#edit-message').html('<p style="color:#2ecc71;">Spieler erfolgreich aktualisiert!</p>');
            },
            error: function(xhr, status, error) {
                console.error('Fehler:', status, error);
                $('#edit-message').html('<p style="color:#e74c3c;">Fehler beim Speichern!</p>');
            }
        });
    });
}
>>>>>>> 971ab47689bd561bd08c6e4d77cea7f516414d66
