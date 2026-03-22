jQuery(document).ready(function($) {
    // Dynamic form switching
    $('#tae-type-selector').on('change', function() {
        const type = $(this).val();
        $('.tae-type-fields').hide();
        $(`#tae-${type}-fields`).fadeIn();
    });

    // Handle ticket form submission
    $('#tae-add-ticket-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $msg = $('#tae-form-msg');
        const $btn = $form.find('button');
        
        $msg.html('<p style="color:#666;">Processing your listing...</p>');
        $btn.prop('disabled', true);
        
        // Collect data
        const type = $('#tae-type-selector').val();
        const data = {
            event_type: type,
            price: $form.find('input[name="price"]').val(),
            quantity: $form.find('input[name="quantity"]').val(),
            seat_info: $form.find('input[name="seat_info"]').val(),
        };

        if (type === 'movie') {
            data.title = $form.find('input[name="title"]').val();
            data.venue = $form.find('input[name="venue"]').val();
            data.date = $form.find('input[name="date"]').val();
            data.time = $form.find('input[name="time"]').val();
        } else {
            data.event_id = $form.find('select[name="event_id"]').val();
        }

        $.ajax({
            url: tae_ajax.rest_url + '/tickets',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', tae_ajax.nonce);
            },
            data: data,
            success: function(response) {
                $msg.html('<p style="color:#1e8e3e; font-weight:700;">Success! Your ticket is pending approval.</p>');
                $form[0].reset();
                $('.tae-type-fields').hide();
                $('#tae-movie-fields').show();
            },
            error: function(xhr) {
                const err = xhr.responseJSON ? xhr.responseJSON.message : 'Upload failed. Check fields.';
                $msg.html('<p style="color:#d93025; font-weight:700;">Error: ' + err + '</p>');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
});
