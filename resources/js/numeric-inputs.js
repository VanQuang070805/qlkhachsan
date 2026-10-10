const selector = 'input[type="number"], input[data-digits-only]';

const rulesFor = input => {
    const decimal = input.type === 'number' && (input.step === 'any' || Number(input.step || 1) % 1 !== 0);
    return input.hasAttribute('data-digits-only') || !decimal ? /^\d*$/ : /^\d*(?:\.\d*)?$/;
};

const candidateValue = (input, inserted) => {
    let start = input.value.length;
    let end = start;
    try {
        if (input.selectionStart !== null) {
            start = input.selectionStart;
            end = input.selectionEnd;
        }
    } catch { /* Number inputs do not expose text selection in every browser. */ }
    return input.value.slice(0, start) + inserted + input.value.slice(end);
};

document.addEventListener('beforeinput', event => {
    const input = event.target;
    if (!(input instanceof HTMLInputElement) || !input.matches(selector) || !event.data) return;
    if (!rulesFor(input).test(candidateValue(input, event.data))) event.preventDefault();
}, true);

document.addEventListener('paste', event => {
    const input = event.target;
    if (!(input instanceof HTMLInputElement) || !input.matches(selector)) return;
    const pasted = event.clipboardData?.getData('text');
    const candidate = input.type === 'number' ? pasted : candidateValue(input, pasted ?? '');
    if (pasted != null && !rulesFor(input).test(candidate)) event.preventDefault();
}, true);

document.addEventListener('input', event => {
    const input = event.target;
    if (!(input instanceof HTMLInputElement) || !input.matches(selector)) return;
    if (rulesFor(input).test(input.value)) return;
    if (input.hasAttribute('data-digits-only')) {
        let caret = null;
        try { caret = input.selectionStart; } catch { /* Number inputs have no text caret. */ }
        const digitsBeforeCaret = input.value.slice(0, caret ?? input.value.length).replace(/\D/g, '').length;
        input.value = input.value.replace(/\D/g, '');
        if (caret !== null) input.setSelectionRange(digitsBeforeCaret, digitsBeforeCaret);
    } else {
        input.value = '';
    }
}, true);
