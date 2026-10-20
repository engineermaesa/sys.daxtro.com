{{-- DELETE TICKET (A8, FR-E13..E16, E45): DELETE /api/aftersales/tickets/{id} body { reason } --}}
<div class="modal fade" id="ticketDeleteModal" tabindex="-1" role="dialog" aria-labelledby="ticketDeleteTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content rounded-2xl! overflow-hidden border-0!">
            <div class="modal-header border-0! pb-0!">
                <h5 class="modal-title font-bold text-[#1E1E1E]" id="ticketDeleteTitle">Delete ticket</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-[#757575] text-sm mb-3">The ticket will be removed from All Ticket, technician reports, and rating. This cannot be undone from the app.</p>

                <div id="ticketDeleteWarning" class="hidden rounded-md text-xs mb-3" style="padding: 10px 14px; background:#FFF3CD; color:#8A6D00;"></div>
                <div id="ticketDeleteInfo" class="hidden rounded-md text-xs mb-3" style="padding: 10px 14px; background:#EEF4FF; color:#2F5BD3;"></div>

                <label for="ticketDeleteReason" class="font-semibold text-sm mb-1">Reason <span style="color:#EC221F;">*</span></label>
                <textarea id="ticketDeleteReason" rows="3" maxlength="1000" class="w-full border border-[#D9D9D9] rounded-lg px-3 py-2" placeholder="Why is this ticket being deleted?"></textarea>
                <p id="ticketDeleteFeedback" class="text-xs mt-2 mb-0" style="color:#EC221F;"></p>
            </div>
            <div class="modal-footer border-0! pt-0!">
                <button type="button" class="bg-white border border-[#D9D9D9] rounded-lg px-4 py-2" data-dismiss="modal">Cancel</button>
                <button type="button" id="ticketDeleteConfirm" class="text-white rounded-lg px-4 py-2" style="background:#900B09;">Delete</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        let ticketId = null;
        let saving = false;

        function errMsg(xhr, fallback) {
            const body = xhr.responseJSON || {};
            return Object.values(body.errors || {}).flat()[0] || body.message || fallback;
        }

        function reset() {
            $('#ticketDeleteReason').val('');
            $('#ticketDeleteFeedback').text('');
            $('#ticketDeleteWarning, #ticketDeleteInfo').addClass('hidden').text('');
            $('#ticketDeleteConfirm').prop('disabled', false).text('Delete');
        }

        // buka modal: ambil detail tiket dulu untuk menentukan warning/info (kontrak 3.3)
        $(document).on('click', '.btn-ticket-delete', function () {
            const t = $(this).data('ticket') || {};
            if (!t.id) return;
            ticketId = t.id;
            reset();
            $('#ticketDeleteTitle').text('Delete ticket ' + (t.ticket_code || ''));

            $.ajax({
                url: `/api/aftersales/tickets/${t.id}`,
                method: 'GET',
                dataType: 'json',
                headers: { Accept: 'application/json' },
                success: function (response) {
                    const d = response.data || {};
                    const docs = d.documents || {};
                    if (docs.agreement === 'saved' || docs.service_form === 'saved' || d.survey_filled) {
                        $('#ticketDeleteWarning').removeClass('hidden').text(
                            'Documents are already saved or the survey is already filled. Emails that were already sent cannot be recalled.'
                        );
                    }
                    if (Number(d.stock_held) > 0) {
                        $('#ticketDeleteInfo').removeClass('hidden').text(
                            `Spareparts held by this ticket (${Number(d.stock_held)} pcs) return to stock.`
                        );
                    }
                },
                error: function (xhr) {
                    $('#ticketDeleteFeedback').text(errMsg(xhr, 'Failed to load ticket details.'));
                },
                complete: function () {
                    $('#ticketDeleteModal').modal('show');
                }
            });
        });

        $('#ticketDeleteConfirm').on('click', function () {
            if (saving || !ticketId) return;

            const reason = $('#ticketDeleteReason').val().trim();
            if (!reason) {
                $('#ticketDeleteFeedback').text('Reason is required.');
                $('#ticketDeleteReason').trigger('focus');
                return;
            }

            saving = true;
            $('#ticketDeleteConfirm').prop('disabled', true).text('Deleting...');
            $('#ticketDeleteFeedback').text('');

            $.ajax({
                url: `/api/aftersales/tickets/${ticketId}`,
                method: 'DELETE',
                contentType: 'application/json',
                dataType: 'json',
                headers: { Accept: 'application/json' },
                data: JSON.stringify({ reason: reason }),
                success: function (response) {
                    $('#ticketDeleteModal').modal('hide');
                    Swal.fire({ title: response.message || 'Ticket deleted successfully', icon: 'success', timer: 1500, showConfirmButton: false });
                    loadTickets();
                },
                error: function (xhr) {
                    $('#ticketDeleteFeedback').text(errMsg(xhr, 'Failed to delete ticket.'));
                },
                complete: function () {
                    saving = false;
                    $('#ticketDeleteConfirm').prop('disabled', false).text('Delete');
                }
            });
        });

        // jangan tutup modal saat request berjalan
        $('#ticketDeleteModal').on('hide.bs.modal', function (e) {
            if (saving) e.preventDefault();
        });
    })();
</script>
@endpush
