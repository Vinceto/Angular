export function setupCounter(element: HTMLButtonElement) {
  let counter = 0;
  element.addEventListener('click', () => {
    counter += 1;
    element.innerText = `Counter: ${counter}`;
  });
}
