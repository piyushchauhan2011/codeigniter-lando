export const buildGreeting = (time = new Date()): string =>
  `JavaScript is working! Time: ${time.toLocaleTimeString()}`;

if (typeof document !== "undefined") {
  const greetButton = document.getElementById("greet-btn");
  const output = document.getElementById("greet-output");

  if (!greetButton || !output) {
    // Not on the hello page or markup not ready yet — nothing to wire up.
  } else {
    greetButton.addEventListener("click", () => {
      output.textContent = buildGreeting();
    });
  }
}

