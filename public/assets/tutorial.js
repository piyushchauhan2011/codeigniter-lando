(() => {
    const greetButton = document.getElementById('greet-btn');
    const output = document.getElementById('greet-output');

    if (!greetButton || !output) {
        return;
    }

    greetButton.addEventListener('click', () => {
        output.textContent = `JavaScript is working! Time: ${new Date().toLocaleTimeString()}`;
    });
})();
