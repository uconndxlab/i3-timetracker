window.I3 = window.I3 || {};

I3.formatHours = (hours) => Number(hours || 0).toFixed(2);

I3.escapeHtml = (value) => String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');

I3.isStatusOn = (value) => value === true || value === 1 || value === '1';

I3.renderStatusIcon = (value) => (
    `<span class="i3-check ${I3.isStatusOn(value) ? 'is-checked' : ''}" aria-hidden="true">
        <i class="bi bi-check-lg"></i>
    </span>`
);

I3.formatChartDayLabel = (day) => {
    if (!day?.date) {
        return day?.key || day?.label || '';
    }

    const parts = day.date.split('-');
    return `${Number(parts[1])}/${Number(parts[2])}`;
};

I3.initPeriodPicker = ({
    toggleEl,
    menuEl,
    periods,
    activeIndex = 0,
    onSelect,
    labelKey = 'label',
}) => {
    if (!toggleEl || !menuEl || !periods?.length) {
        return;
    }

    const renderMenu = () => {
        menuEl.innerHTML = periods.map((period, index) => `
            <li>
                <button type="button" role="option" data-period-index="${index}" ${index === activeIndex ? 'aria-selected="true"' : ''}>
                    ${period[labelKey]}
                </button>
            </li>
        `).join('');

        menuEl.querySelectorAll('[data-period-index]').forEach((btn) => {
            btn.addEventListener('click', () => {
                menuEl.classList.add('d-none');
                toggleEl.setAttribute('aria-expanded', 'false');
                onSelect(parseInt(btn.dataset.periodIndex, 10), periods[parseInt(btn.dataset.periodIndex, 10)]);
            });
        });
    };

    toggleEl.addEventListener('click', () => {
        const isOpen = !menuEl.classList.contains('d-none');
        menuEl.classList.toggle('d-none', isOpen);
        toggleEl.setAttribute('aria-expanded', String(!isOpen));
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.dashboard-period-select')) {
            menuEl.classList.add('d-none');
            toggleEl.setAttribute('aria-expanded', 'false');
        }
    });

    renderMenu();

    return { renderMenu };
};
