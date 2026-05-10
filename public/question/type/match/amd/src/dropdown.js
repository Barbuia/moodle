/**
 * Custom dropdown component for qtype_match.
 *
 * Replaces native <select> elements so that MathJax and other Moodle text
 * filters can render inside the answer choices.
 *
 * @module     qtype_match/dropdown
 * @copyright  2026 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const SELECTORS = {
    wrapper: '.qtype_match_dropdown_wrapper',
    button: '.qtype_match_dropdown_button',
    panel: '.qtype_match_dropdown_panel',
    option: '.qtype_match_dropdown_option',
};

const CLASSES = {
    open: 'qtype_match_dropdown_open',
    active: 'active',
    highlighted: 'qtype_match_dropdown_highlighted',
};

/**
 * Initialise all custom dropdowns within the given root element.
 *
 * @param {string} rootid The id of the root container element.
 * @param {boolean} readonly Whether the question is in readonly state.
 */
export const init = (rootid, readonly) => {
    if (readonly) {
        return;
    }
    const root = document.getElementById(rootid);
    if (!root) {
        return;
    }
    root.querySelectorAll(SELECTORS.wrapper).forEach((wrapper) => {
        initDropdown(wrapper);
    });

    // Single document-level listener to close dropdowns on outside click.
    document.addEventListener('click', (e) => {
        if (!e.target.closest(SELECTORS.wrapper)) {
            closeAllDropdowns();
        }
    });
};

/**
 * Set up a single dropdown wrapper element.
 *
 * @param {HTMLElement} wrapper The .qtype_match_dropdown_wrapper element.
 */
const initDropdown = (wrapper) => {
    const button = wrapper.querySelector(SELECTORS.button);
    const panel = wrapper.querySelector(SELECTORS.panel);
    const hiddenInput = wrapper.querySelector('input[type="hidden"]');

    if (!button || !panel || !hiddenInput || button.disabled) {
        return;
    }

    button.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const isOpen = wrapper.classList.contains(CLASSES.open);
        closeAllDropdowns();
        if (!isOpen) {
            openDropdown(wrapper, panel, button);
        }
    });

    panel.addEventListener('click', (e) => {
        const option = e.target.closest(SELECTORS.option);
        if (!option) {
            return;
        }
        selectOption(wrapper, panel, button, hiddenInput, option);
        closeDropdown(wrapper, button);
        button.focus();
    });

    wrapper.addEventListener('keydown', (e) => {
        handleKeydown(e, wrapper, panel, button, hiddenInput);
    });
};

/**
 * Open a dropdown panel.
 *
 * @param {HTMLElement} wrapper
 * @param {HTMLElement} panel
 * @param {HTMLElement} button
 */
const openDropdown = (wrapper, panel, button) => {
    wrapper.classList.add(CLASSES.open);
    button.setAttribute('aria-expanded', 'true');

    const activeOption = panel.querySelector(`.${CLASSES.active}`);
    if (activeOption) {
        activeOption.scrollIntoView({block: 'nearest'});
    }

    // Re-trigger MathJax rendering inside the panel.
    if (typeof window.MathJax !== 'undefined' && window.MathJax.typesetPromise) {
        window.MathJax.typesetPromise([panel]);
    }
};

/**
 * Close a specific dropdown.
 *
 * @param {HTMLElement} wrapper
 * @param {HTMLElement} button
 */
const closeDropdown = (wrapper, button) => {
    wrapper.classList.remove(CLASSES.open);
    button.setAttribute('aria-expanded', 'false');
};

/**
 * Close every open dropdown on the page.
 */
const closeAllDropdowns = () => {
    document.querySelectorAll(`${SELECTORS.wrapper}.${CLASSES.open}`).forEach((w) => {
        const btn = w.querySelector(SELECTORS.button);
        closeDropdown(w, btn);
    });
};

/**
 * Select an option and update the hidden input and button display.
 *
 * @param {HTMLElement} wrapper
 * @param {HTMLElement} panel
 * @param {HTMLElement} button
 * @param {HTMLInputElement} hiddenInput
 * @param {HTMLElement} option
 */
const selectOption = (wrapper, panel, button, hiddenInput, option) => {
    // Deselect all options.
    panel.querySelectorAll(SELECTORS.option).forEach((opt) => {
        opt.classList.remove(CLASSES.active);
        opt.classList.remove(CLASSES.highlighted);
        opt.setAttribute('aria-selected', 'false');
    });

    // Select the chosen option.
    option.classList.add(CLASSES.active);
    option.setAttribute('aria-selected', 'true');

    // Update hidden form input.
    hiddenInput.value = option.dataset.value;

    // Update the visible button text.
    button.innerHTML = option.innerHTML;

    // Re-trigger MathJax rendering on the button.
    if (typeof window.MathJax !== 'undefined' && window.MathJax.typesetPromise) {
        window.MathJax.typesetPromise([button]);
    }

    // Fire change event so the Moodle question engine detects the response.
    hiddenInput.dispatchEvent(new Event('change', {bubbles: true}));
};

/**
 * Handle keyboard interaction following WAI-ARIA listbox pattern.
 *
 * @param {KeyboardEvent} e
 * @param {HTMLElement} wrapper
 * @param {HTMLElement} panel
 * @param {HTMLElement} button
 * @param {HTMLInputElement} hiddenInput
 */
const handleKeydown = (e, wrapper, panel, button, hiddenInput) => {
    const isOpen = wrapper.classList.contains(CLASSES.open);
    const options = Array.from(panel.querySelectorAll(SELECTORS.option));
    const highlightedIndex = options.findIndex((opt) => opt.classList.contains(CLASSES.highlighted));
    const activeIndex = options.findIndex((opt) => opt.classList.contains(CLASSES.active));
    const currentIndex = highlightedIndex >= 0 ? highlightedIndex : activeIndex;

    switch (e.key) {
        case 'Enter':
        case ' ':
            e.preventDefault();
            if (!isOpen) {
                closeAllDropdowns();
                openDropdown(wrapper, panel, button);
            } else {
                if (currentIndex >= 0) {
                    selectOption(wrapper, panel, button, hiddenInput, options[currentIndex]);
                }
                closeDropdown(wrapper, button);
                button.focus();
            }
            break;

        case 'ArrowDown':
            e.preventDefault();
            if (!isOpen) {
                closeAllDropdowns();
                openDropdown(wrapper, panel, button);
            } else {
                highlightOption(options, Math.min(currentIndex + 1, options.length - 1));
            }
            break;

        case 'ArrowUp':
            e.preventDefault();
            if (isOpen) {
                highlightOption(options, Math.max(currentIndex - 1, 0));
            }
            break;

        case 'Escape':
            if (isOpen) {
                e.preventDefault();
                clearHighlights(options);
                closeDropdown(wrapper, button);
                button.focus();
            }
            break;

        case 'Tab':
            if (isOpen) {
                clearHighlights(options);
                closeDropdown(wrapper, button);
            }
            break;

        case 'Home':
            if (isOpen) {
                e.preventDefault();
                highlightOption(options, 0);
            }
            break;

        case 'End':
            if (isOpen) {
                e.preventDefault();
                highlightOption(options, options.length - 1);
            }
            break;
    }
};

/**
 * Visually highlight an option at a given index for keyboard navigation.
 * Does not change the 'active' (selected) state.
 *
 * @param {HTMLElement[]} options All option elements in the panel.
 * @param {number} index The index to highlight.
 */
const highlightOption = (options, index) => {
    options.forEach((opt) => {
        opt.classList.remove(CLASSES.highlighted);
    });
    if (options[index]) {
        options[index].classList.add(CLASSES.highlighted);
        options[index].scrollIntoView({block: 'nearest'});
    }
};

/**
 * Remove all keyboard navigation highlights from options.
 *
 * @param {HTMLElement[]} options All option elements in the panel.
 */
const clearHighlights = (options) => {
    options.forEach((opt) => {
        opt.classList.remove(CLASSES.highlighted);
    });
};
