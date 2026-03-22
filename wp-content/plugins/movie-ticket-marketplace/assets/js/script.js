jQuery(document).ready(function($) {
    // Handle sell form submission
    $('#mtm-sell-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $msg = $('#mtm-form-msg');
        const $btn = $form.find('button');
        
        $msg.html('<p style="color:#666;">Processing...</p>');
        $btn.prop('disabled', true);
        
        const data = {
            title: $form.find('input[name="title"]').val(),
            location: $form.find('input[name="location"]').val(),
            datetime: $form.find('input[name="datetime"]').val(),
            seat_info: $form.find('input[name="seat_info"]').val(),
            price: $form.find('input[name="price"]').val(),
            quantity: $form.find('input[name="quantity"]').val(),
        };
        
        $.ajax({
            url: mtm_ajax.rest_url + '/movies',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', mtm_ajax.nonce);
            },
            data: data,
            success: function(response) {
                $msg.html('<p style="color:#2ecc71;">Listing created successfully! Awaiting admin approval.</p>');
                $form[0].reset();
            },
            error: function(xhr) {
                const err = xhr.responseJSON ? xhr.responseJSON.message : 'An error occurred';
                $msg.html('<p style="color:#e74c3c;">' + err + '</p>');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
});
