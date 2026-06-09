document.addEventListener('DOMContentLoaded', () => {
    const counters = document.querySelectorAll('[data-char-counter]');
    counters.forEach((counter) => {
        const targetId = counter.getAttribute('data-char-counter');
        const target = document.getElementById(targetId);
        if (!target) {
            return;
        }

        const update = () => {
            counter.textContent = `${target.value.length} characters`;
        };

        target.addEventListener('input', update);
        update();
    });
});
