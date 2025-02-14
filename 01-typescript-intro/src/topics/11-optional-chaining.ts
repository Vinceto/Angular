export interface Passenger{
    name: string,
    children?: string[];
}

const passenger1: Passenger = {
    name : 'Fernando'
}

const passenger2: Passenger = {
    name: 'Eliana',
    children: ['Halley'],
}

const returnChildenNumber = ( passenger: Passenger ) =>{
    const howManyChildren = passenger.children?.length || 0;

    console.log(passenger.name, howManyChildren);
}

returnChildenNumber(passenger1);