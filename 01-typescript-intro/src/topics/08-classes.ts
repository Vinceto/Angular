export class Person {

    constructor(public name:string, private address:string = 'No Address') {}

}

// export class Hero extends Person{
//     constructor(
//         public alterEgo: string,
//         public age: number,
//         public realName: string ,
//         public superpower?: string
//     ){
//         super(realName, 'Chicago');
//     }
    
// }

export class Hero {
    constructor(
        public alterEgo: string,
        public age: number,
        public realName: string,
        public person: Person,
        public superpower?: string

    ){    }
}

const tony = new Person ('Tony Stark', 'New York');
const ironman = new Hero('Iron Man', 45, 'Tony Stark', tony,  'Rich');

console.log(ironman)