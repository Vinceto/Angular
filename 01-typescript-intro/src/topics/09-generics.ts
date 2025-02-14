

export function whatsMyType<T>(argument: T) : T{
    return argument;
}

let amIString = whatsMyType<string>('hola mundo');
let amINumber = whatsMyType<number>(100);
let amIrray = whatsMyType<number[]>([1,2,3,4,5,6]);

console.log( amIString.split(' ') )
console.log( amINumber.toFixed() )
console.log( amIrray.join('-') )