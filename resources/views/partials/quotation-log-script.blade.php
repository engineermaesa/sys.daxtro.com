$(document).on('click', '.btn-quotation-log', function(e) {
    e.preventDefault();
    const tbody = $('#quotationLogModal tbody');
    const showMessage = (message) => tbody.empty().append(
        $('<tr>').append($('<td>').attr('colspan', 4).addClass('text-center p-3').text(message))
    );
    showMessage('Loading...');
    $('#quotationLogModal').modal('show');
    $.get($(this).data('url')).done(function(data) {
        if (!Array.isArray(data) || !data.length) {
            showMessage('No logs');
            return;
        }
        tbody.empty();
        data.forEach(function(item) {
            const revision = item.revision_code || (item.revision_no != null ? 'R' + item.revision_no : 'Unknown Revision');
            const row = $('<tr>').addClass('border-t border-t-[#D9D9D9]');
            [item.logged_at, revision, item.action_label || item.action, item.user].forEach(function(value) {
                row.append($('<td>').addClass('lg:p-3 p-1').text(value || '-'));
            });
            tbody.append(row);
        });
    }).fail(function() {
        showMessage('Failed to load quotation logs. Please try again.');
    });
});
