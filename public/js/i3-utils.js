window.I3 = window.I3 || {};

I3.formatHours = (hours) => Number(hours || 0).toFixed(2);

I3.QUARTER_HOUR_MINUTES = 15;

I3.snapHoursToQuarter = (hours) => {
    if (Number.isNaN(hours)) {
        return 0.25;
    }

    const quarters = Math.round(hours * 4);

    return Math.max(0.25, quarters / 4);
};

I3.snapMinutesToQuarter = (minutes) => {
    const parsed = parseInt(minutes, 10);
    if (Number.isNaN(parsed)) {
        return I3.QUARTER_HOUR_MINUTES;
    }

    return Math.max(I3.QUARTER_HOUR_MINUTES, Math.round(parsed / I3.QUARTER_HOUR_MINUTES) * I3.QUARTER_HOUR_MINUTES);
};

I3.isQuarterHourHours = (hours) => {
    if (Number.isNaN(hours) || hours < 0.25) {
        return false;
    }

    return Math.abs((hours * 4) - Math.round(hours * 4)) < 0.001;
};

I3.isQuarterHourMinutes = (minutes) => {
    const parsed = parseInt(minutes, 10);

    return !Number.isNaN(parsed)
        && parsed >= I3.QUARTER_HOUR_MINUTES
        && parsed % I3.QUARTER_HOUR_MINUTES === 0;
};

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
    newestFirst = true,
}) => {
    if (!toggleEl || !menuEl || !periods?.length) {
        return;
    }

    const menuIndices = periods.map((_, index) => index);
    if (newestFirst) {
        menuIndices.reverse();
    }

    let selectedIndex = activeIndex;

    const syncMenuWidth = () => {
        menuEl.style.width = `${toggleEl.offsetWidth}px`;
    };

    const renderMenu = () => {
        menuEl.innerHTML = menuIndices.map((index) => {
            const period = periods[index];
            const isSelected = index === selectedIndex;
            const isCurrent = period.is_current_week === true;
            const classes = [
                isSelected ? 'is-selected' : '',
                isCurrent ? 'is-current-period' : '',
            ].filter(Boolean).join(' ');

            const checkIcon = isSelected
                ? '<i class="bi bi-check-lg dashboard-period-menu__check" aria-hidden="true"></i>'
                : '<span class="dashboard-period-menu__check-spacer" aria-hidden="true"></span>';

            return `
            <li>
                <button type="button" role="option" class="${classes}" data-period-index="${index}" ${isSelected ? 'aria-selected="true"' : ''}>
                    ${checkIcon}
                    <span class="dashboard-period-menu__label">${period[labelKey]}${isCurrent ? ' <span class="dashboard-period-menu__current">Current</span>' : ''}</span>
                </button>
            </li>
        `;
        }).join('');

        menuEl.querySelectorAll('[data-period-index]').forEach((btn) => {
            btn.addEventListener('click', () => {
                menuEl.classList.add('d-none');
                toggleEl.setAttribute('aria-expanded', 'false');
                const nextIndex = parseInt(btn.dataset.periodIndex, 10);
                const allowed = onSelect?.(nextIndex, periods[nextIndex]);
                if (allowed === false) {
                    return;
                }

                selectedIndex = nextIndex;
                renderMenu();
            });
        });
    };

    toggleEl.addEventListener('click', () => {
        const isOpen = !menuEl.classList.contains('d-none');
        if (!isOpen) {
            syncMenuWidth();
        }
        menuEl.classList.toggle('d-none', isOpen);
        toggleEl.setAttribute('aria-expanded', String(!isOpen));
    });

    if (typeof ResizeObserver !== 'undefined') {
        new ResizeObserver(syncMenuWidth).observe(toggleEl);
    } else {
        window.addEventListener('resize', syncMenuWidth);
    }

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.dashboard-period-select')) {
            menuEl.classList.add('d-none');
            toggleEl.setAttribute('aria-expanded', 'false');
        }
    });

    renderMenu();
    syncMenuWidth();

    return { renderMenu, syncMenuWidth };
};
