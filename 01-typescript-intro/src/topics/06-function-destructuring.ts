interface Product { 
    id: number;
    name: string;
    description: string;
    price: number;

}

const phone: Product = {    
    id: 1,
    name: 'Samsung Galaxy S21',
    description: 'The best phone ever',
    price: 1000
};

const tablet: Product = {
    id: 2,
    name: 'Samsung Galaxy Tab S7',
    description: 'The best tablet ever',
    price: 800
};

interface TaxCalculationOptions {
    product: Product[];
    tax: number;
}

function taxCalculator(options: TaxCalculationOptions): number[] {
    let total = 0;
    options.product.forEach( (product) => {
        total += product.price;
    });
    return [total, total * options.tax];
}

const shoppingCart: Product[] = [phone, tablet];
const tax = 0.16;


const result = taxCalculator({
    product: shoppingCart,
    tax
}
    
);


//aplicar la desestructuracion en todo el ejercicio
console.log('Total ', result[0])
console.log('Tax ', result[1])

export {};