window.I3 = window.I3 || {};

I3.formatHours = (hours) => Number(hours || 0).toFixed(2);

I3.escapeHtml = (value) => String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');

I3.renderCheck = ({
    checked = false,
    interactive = true,
    className = '',
    attrs = {},
} = {}) => {
    const classes = ['i3-check', checked ? 'is-checked' : '', className].filter(Boolean).join(' ');
    const icon = '<i class="bi bi-check-lg"></i>';
    const { disabled, ...restAttrs } = attrs;
    const attrPairs = Object.entries(restAttrs)
        .filter(([, value]) => value !== false && value !== null && value !== undefined)
        .map(([key, value]) => (value === '' ? key : `${key}="${I3.escapeHtml(String(value))}"`))
        .join(' ');

    if (!interactive) {
        return `<span class="${classes}" aria-hidden="true">${icon}</span>`;
    }

    const disabledAttr = disabled ? ' disabled' : '';

    return `<button type="button" class="${classes}" aria-pressed="${checked ? 'true' : 'false'}"${disabledAttr}${attrPairs ? ` ${attrPairs}` : ''}>${icon}</button>`;
};

I3.formatChartDayLabel = (day) => {
    if (!day?.date) {
        return day?.key || '';
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
