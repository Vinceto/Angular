function addNumbers(a: number, b: number):number {
    return a + b;
}

// funciones de flecha o lambda functions
const addNumbersArrow = (a: number, b:number): string => {
    return `${a + b}` ;
}

function multiply(firstNumber:number , secondNumber?:number , base:number = 2){
    return firstNumber * base;
}

// const result = addNumbers(1, 2);
// const resultArrow = addNumbersArrow(3, 4);
// const multiplyResult:number = multiply(5);

//console.log({result,resultArrow, multiplyResult});
interface Character {
    name: string,
    hp: number,
    showHp: () => void;
}
const healCharacter = (character:Character, amount:number) =>{
    character.hp += amount;
}

const strider:Character = {
    name: 'Strider',
    hp: 50,
    showHp(){
        console.log(`HP: ${this.hp}`);
    }
}


healCharacter(strider, 10);
strider.showHp();

export {};