import { Products, taxCalculator, TaxCalculationOptions } from './06-function-destructuring';

const shoppingCart: Products[] = [
    {
        id: 0,
        name: 'Nokia',
        description: 'Nokia',
        price: 100,
    },
    {
        id: 0,
        name: 'iPad',
        description: 'iPad',
        price: 150
    }
];

const tax = 0.15;
const [total,totalTax] = taxCalculator({
    products: shoppingCart,
    tax
});

console.log('Total: ', total);
console.log('Tax: ', totalTax);