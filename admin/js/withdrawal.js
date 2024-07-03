$(document).ready(function() {
    $(document).on('click', '#update-btn', function() {
        var withdrawalId = $(this).closest('tr').find('input[name="withdrawal_id"]').val();
        
        $.ajax({
            url: 'query/payment.php',
            type: 'POST',
            data: { withdrawal_id: withdrawalId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                    }).then(function() {
                        // window.location.reload(); 
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message,
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', status, error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while updating withdrawal request.',
                });
            }
        });
    });
});
