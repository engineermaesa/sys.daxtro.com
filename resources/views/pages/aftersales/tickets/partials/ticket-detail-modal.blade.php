{{-- TICKET DETAIL MODAL --}}
<div class="modal fade" id="ticketDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-2xl! overflow-hidden border-0!">
            <div class="modal-header border-0! items-start px-6! pt-6! pb-4!">
                <div>
                    <h5 id="ticketDetailCode" class="modal-title text-[#115640] text-xl font-bold mb-0">TKT-0000-000</h5>
                    <p id="ticketDetailSubtitle" class="text-[#757575] text-sm mb-0"></p>
                </div>
                <button type="button" class="close cursor-pointer opacity-100! text-[#1E1E1E]!" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" class="text-2xl font-light leading-none">&times;</span>
                </button>
            </div>
            <div class="modal-body px-6! pb-6! pt-0!">
                <hr class="border-[#D9D9D9] mb-4">
                <label class="uppercase text-[11px] text-[#757575] font-semibold tracking-wide">Problem Description</label>
                <p id="ticketDetailDescription" class="font-medium mt-1 mb-4"></p>

                <p class="mb-0">
                    <span class="text-[#1E1E1E] font-semibold">Problem:</span>
                    <span id="ticketDetailCategory" class="text-[#1E1E1E]"></span>
                    &nbsp;&middot;&nbsp;
                    <span id="ticketDetailPriority"></span>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    function openTicketDetail(ticket) {
        const customer = ticket.customer || {};
        const machine = ticket.customer_product || {};

        $('#ticketDetailCode').text(ticket.ticket_code || '-');
        $('#ticketDetailSubtitle').text(`${customer.name || '-'} · ${machine.product?.name || '-'}`);
        $('#ticketDetailDescription').text(ticket.description || '-');
        $('#ticketDetailCategory').text(CATEGORY_LABEL[ticket.category] || ticket.category || '-');
        $('#ticketDetailPriority').html(priorityBadge(ticket.priority));

        $('#ticketDetailModal').modal('show');
    }
</script>
