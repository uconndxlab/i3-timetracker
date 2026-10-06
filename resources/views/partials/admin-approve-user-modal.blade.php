<div class="shift-modal-backdrop d-none" id="approveUserModalBackdrop" aria-hidden="true"></div>
<div class="shift-modal d-none" id="approveUserModal" role="dialog" aria-modal="true" aria-labelledby="approveUserModalTitle">
    <div class="shift-modal__dialog">
        <div class="shift-modal__header">
            <div class="shift-modal__number" aria-hidden="true"></div>
            <h2 class="shift-modal__title" id="approveUserModalTitle">Approve User</h2>
            <button type="button" class="shift-modal__close" id="approveUserModalClose" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form id="approveUserForm" novalidate>
            <div class="shift-modal__body">
                <div id="approveUserModalErrors" class="alert alert-danger d-none py-2 small"></div>

                <div class="shift-modal__field">
                    <label class="shift-modal__field-label" for="approveUserNetid">NetID:</label>
                    <div class="shift-modal__field-control">
                        <input type="text"
                               class="form-control"
                               id="approveUserNetid"
                               name="netid"
                               maxlength="64"
                               placeholder="jdoe001"
                               autocomplete="off"
                               required>
                    </div>
                </div>

                <div class="shift-modal__field">
                    <label class="shift-modal__field-label" for="approveUserName">Name:</label>
                    <div class="shift-modal__field-control">
                        <input type="text"
                               class="form-control"
                               id="approveUserName"
                               name="name"
                               maxlength="255"
                               placeholder="Jane Doe"
                               required>
                    </div>
                </div>

                <div class="shift-modal__field">
                    <label class="shift-modal__field-label" for="approveUserEmail">Email:</label>
                    <div class="shift-modal__field-control">
                        <input type="email"
                               class="form-control"
                               id="approveUserEmail"
                               name="email"
                               maxlength="255"
                               placeholder="Optional — defaults to netid@uconn.edu">
                    </div>
                </div>
            </div>

            <div class="shift-modal__footer">
                <button type="submit" class="shift-modal__submit w-100">Approve Access</button>
            </div>
        </form>
    </div>
</div>
