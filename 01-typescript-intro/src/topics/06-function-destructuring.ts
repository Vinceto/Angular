export interface Products { 
    id: number;
    name: string;
    description: string;
    price: number;

}

const phone: Products = {    
    id: 1,
    name: 'Samsung Galaxy S21',
    description: 'The best phone ever',
    price: 1000
};

const tablet: Products = {
    id: 2,
    name: 'Samsung Galaxy Tab S7',
    description: 'The best tablet ever',
    price: 800
};

export interface TaxCalculationOptions {
    products: Products[];
    tax: number;
}

export function taxCalculator(options: TaxCalculationOptions): [number,number] {
    const {tax,products} = options;
    let total = 0;
    products.forEach( ({price}) => {
        total += price;
    });
    return [total, total * tax];
}

const shoppingCart: Products[] = [phone, tablet];
const tax = 0.16;


const [total,taxTotal] = taxCalculator({
    products: shoppingCart,
    tax
}
    
);


//aplicar la desestructuracion en todo el ejercicio
// console.log('Total ', total)
// console.log('Tax ', taxTotal)

export {};