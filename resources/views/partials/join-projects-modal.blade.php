<div class="join-modal-backdrop d-none" id="joinProjectsModalBackdrop" aria-hidden="true"></div>
<div class="join-modal d-none" id="joinProjectsModal" role="dialog" aria-modal="true" aria-labelledby="joinProjectsModalTitle">
    <div class="join-modal__dialog">
        <div class="join-modal__header">
            <h2 class="join-modal__title" id="joinProjectsModalTitle">Join Active Projects</h2>
            <button type="button" class="join-modal__close" id="joinProjectsModalClose" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="join-modal__body">
            <div id="joinProjectsModalErrors" class="alert alert-danger d-none py-2 small"></div>

            <input type="search"
                   class="join-modal__search"
                   id="joinProjectsSearch"
                   placeholder="Search Projects"
                   autocomplete="off">

            <div class="join-modal__list" id="joinProjectsList" role="listbox" aria-multiselectable="true"></div>
        </div>

        <div class="join-modal__footer">
            <button type="button" class="join-modal__submit" id="joinProjectsSubmit" disabled>
                Join <span id="joinProjectsJoinCount">[0]</span> Project(s)
            </button>
            <p class="join-modal__leaving d-none" id="joinProjectsLeaving">
                YOU ARE LEAVING <span id="joinProjectsLeaveCount">[0]</span> PROJECTS
            </p>
        </div>
    </div>
</div>
