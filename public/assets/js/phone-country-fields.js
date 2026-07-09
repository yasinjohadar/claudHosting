(function () {
    'use strict';

    function stripLocalPhone(input) {
        if (!input) {
            return;
        }
        input.addEventListener('input', function () {
            input.value = input.value.replace(/\D/g, '').replace(/^0+/, '');
        });
    }

    function normalizeSearch(value) {
        return String(value || '').toLowerCase().trim();
    }

    function resetPanelPosition(picker) {
        var panel = picker.querySelector('.phone-country-picker__panel');
        if (!panel) {
            return;
        }
        panel.classList.remove('is-fixed');
        panel.style.position = '';
        panel.style.top = '';
        panel.style.left = '';
        panel.style.width = '';
        panel.style.right = '';
        panel.style.zIndex = '';
    }

    function positionPanel(picker) {
        var panel = picker.querySelector('.phone-country-picker__panel');
        var trigger = picker.querySelector('.phone-country-picker__trigger');
        if (!panel || !trigger) {
            return;
        }

        var rect = trigger.getBoundingClientRect();
        panel.classList.add('is-fixed');
        panel.style.position = 'fixed';
        panel.style.top = Math.round(rect.bottom + 4) + 'px';
        panel.style.left = Math.round(rect.left) + 'px';
        panel.style.width = Math.round(rect.width) + 'px';
        panel.style.right = 'auto';
        panel.style.zIndex = '9999';
    }

    function closePicker(picker) {
        var panel = picker.querySelector('.phone-country-picker__panel');
        var trigger = picker.querySelector('.phone-country-picker__trigger');
        if (!panel || !trigger) {
            return;
        }
        panel.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
        picker.classList.remove('is-open');
        resetPanelPosition(picker);
    }

    function openPicker(picker) {
        var panel = picker.querySelector('.phone-country-picker__panel');
        var trigger = picker.querySelector('.phone-country-picker__trigger');
        var search = picker.querySelector('.phone-country-picker__search');
        if (!panel || !trigger) {
            return;
        }

        document.querySelectorAll('[data-phone-country-picker].is-open').forEach(function (other) {
            if (other !== picker) {
                closePicker(other);
            }
        });

        panel.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
        picker.classList.add('is-open');
        positionPanel(picker);

        if (search) {
            search.value = '';
            filterOptions(picker, '');
            window.setTimeout(function () {
                search.focus();
            }, 0);
        }

        var selected = picker.querySelector('.phone-country-picker__option.is-selected');
        if (selected && typeof selected.scrollIntoView === 'function') {
            selected.scrollIntoView({ block: 'nearest' });
        }
    }

    function filterOptions(picker, query) {
        var normalized = normalizeSearch(query);
        var options = picker.querySelectorAll('.phone-country-picker__option');
        var visible = 0;

        options.forEach(function (option) {
            var haystack = normalizeSearch(option.getAttribute('data-search'));
            var show = normalized === '' || haystack.indexOf(normalized) !== -1;
            option.hidden = !show;
            if (show) {
                visible += 1;
            }
        });

        var empty = picker.querySelector('.phone-country-picker__empty');
        if (empty) {
            empty.hidden = visible > 0;
        }
    }

    function updateTrigger(picker, option) {
        var trigger = picker.querySelector('.phone-country-picker__trigger');
        var hidden = picker.querySelector('input[type="hidden"]');
        if (!trigger || !hidden || !option) {
            return;
        }

        hidden.value = option.getAttribute('data-value') || '';

        var flag = option.getAttribute('data-flag') || '';
        var code = option.getAttribute('data-value') || '';
        var name = option.getAttribute('data-name') || '';

        trigger.innerHTML =
            '<img src="' + flag + '" alt="" class="phone-country-flag" width="20" height="14">' +
            '<span class="phone-country-picker__trigger-text">' +
            '<span class="phone-country-picker__trigger-code">' + code + '</span>' +
            '<span class="phone-country-picker__trigger-name">' + name + '</span>' +
            '</span>' +
            '<span class="phone-country-picker__chevron" aria-hidden="true"></span>';
    }

    function selectOption(picker, option) {
        if (!option || option.hidden) {
            return;
        }

        picker.querySelectorAll('.phone-country-picker__option').forEach(function (item) {
            var selected = item === option;
            item.classList.toggle('is-selected', selected);
            item.setAttribute('aria-selected', selected ? 'true' : 'false');
        });

        updateTrigger(picker, option);
        closePicker(picker);

        var hidden = picker.querySelector('input[type="hidden"]');
        if (hidden) {
            hidden.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    function initPicker(picker) {
        if (!picker || picker.dataset.phoneCountryInit === '1') {
            return;
        }
        picker.dataset.phoneCountryInit = '1';

        var trigger = picker.querySelector('.phone-country-picker__trigger');
        var panel = picker.querySelector('.phone-country-picker__panel');
        var search = picker.querySelector('.phone-country-picker__search');
        var options = picker.querySelectorAll('.phone-country-picker__option');

        if (!trigger || !panel) {
            return;
        }

        trigger.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            if (picker.classList.contains('is-open')) {
                closePicker(picker);
            } else {
                openPicker(picker);
            }
        });

        if (search) {
            search.addEventListener('input', function () {
                filterOptions(picker, search.value);
            });
            search.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    event.preventDefault();
                    closePicker(picker);
                    trigger.focus();
                }
            });
        }

        options.forEach(function (option) {
            option.addEventListener('click', function (event) {
                event.preventDefault();
                selectOption(picker, option);
            });
        });

        document.addEventListener('click', function (event) {
            if (!picker.contains(event.target)) {
                closePicker(picker);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && picker.classList.contains('is-open')) {
                closePicker(picker);
                trigger.focus();
            }
        });

        var reposition = function () {
            if (picker.classList.contains('is-open')) {
                positionPanel(picker);
            }
        };

        window.addEventListener('resize', reposition);
        window.addEventListener('scroll', reposition, true);
    }

    function initBlock(block) {
        if (!block || block.dataset.phoneCountryReady === '1') {
            return;
        }
        block.dataset.phoneCountryReady = '1';

        block.querySelectorAll('[data-phone-country-picker]').forEach(initPicker);
        stripLocalPhone(block.querySelector('input[type="tel"][data-phone-local]'));
    }

    function initAll(root) {
        (root || document).querySelectorAll('.phone-country-block').forEach(initBlock);
    }

    window.PhoneCountryFields = {
        init: initAll,
        initBlock: initBlock,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initAll(document);
        });
    } else {
        initAll(document);
    }
})();
