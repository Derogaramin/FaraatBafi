$(document).on('click', '.update-status', function() {
    const id = $(this).data('id');
    const status = $(this).data('status');
    if (confirm('آیا مطمئن هستید؟')) {
        $.post('/weaver/update-status', { work_order_id: id, status: status }, function(res) {
            if (res.status === 'success') location.reload();
        });
    }
});

