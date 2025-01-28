import { Component } from '@angular/core';

@Component({
  selector: 'app-counter',
  template: `
              <h1>{{title}}</h1>
              <hr>
              <p>Counter: {{counter}}</p>
              <button (click)="increaseBy(1)">+1</button>
              <button (click)="resetCounter()">reset</button>
              <button (click)="decreaseBy(2)">-2</button>
            `
})

export class CounterComponent {
  constructor() { }
  public title:string = 'Mi primer contador';
  public counter:number = 0;
  increaseBy(value:number):void{
    this.counter+=value;
  };
  decreaseBy(value:number):void{
    this.counter-=value;
  };
  resetCounter():void{
    this.counter = 0;
  };
}
