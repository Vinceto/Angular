import './style.css'
//import typescriptLogo from './typescript.svg'
//import viteLogo from '/vite.svg'
import { setupCounter } from './counter.ts'
// import './topics/01-basic-types.ts'
// import './topics/02-object-interface.ts'
// import './topics/03-functions.ts'
// import './topics/04-homework-types.ts'
// import './topics/05-basic-destructuring.ts'
import './topics/06-function-destructuring.ts'


function printMessage(message: string): void {
  console.log(message);
}

document.addEventListener('DOMContentLoaded', () => {
  const appDiv = document.querySelector<HTMLDivElement>('#app');
  if (appDiv) {
    appDiv.innerHTML = `
      Hola mundo
      <button id="counter">Counter</button>
    `;
    //console.log('hola mundo');

    const counterButton = document.querySelector<HTMLButtonElement>('#counter');
    if (counterButton) {
      setupCounter(counterButton);
    } else {
      console.error('Counter button not found');
    }

    // Example usage of the new function
    //printMessage('Este es otro mensaje');
  } else {
    console.error('#app element not found');
  }

  
});