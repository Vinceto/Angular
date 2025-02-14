/*
    ===== Código de TypeScript =====
    implementar la ingterfaz superhero

*/
interface   Address{
    calle: string,
    pais: string,
    ciudad: string
}
interface SuperHero{
    name : string,
    age : number,
    street : Address,
    showAddress: () => string;
}

const superHeroe: SuperHero = {
    name: 'Spiderman',
    age: 30,
    street: {
        calle: 'Main St',
        pais: 'USA',
        ciudad: 'NY'
    },
    showAddress() {
        return this.name + ', ' + this.street.ciudad + ', ' + this.street.pais;
    }
}


const address = superHeroe.showAddress();
console.log( address );




export {};