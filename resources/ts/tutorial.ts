export const buildGreeting = (time = new Date()): string =>
  `JavaScript is working! Time: ${time.toLocaleTimeString()}`;

(() => {
  const greetButton = document.getElementById("greet-btn");
  const output = document.getElementById("greet-output");

  if (!greetButton || !output) {
    return;
  }

  greetButton.addEventListener("click", () => {
    output.textContent = buildGreeting();
  });
})();
