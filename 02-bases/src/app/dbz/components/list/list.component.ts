import { Component, Input, output, OutputEmitterRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Character } from '../../interfaces/character.interface';

@Component({
  selector: 'dbz-list',
  templateUrl: './list.component.html',
  styleUrl: './list.component.css',
  imports: [CommonModule],
})
export class ListComponent {
  @Input()
  public characterList: Character[] = [
    {
      name: 'Trunks',
      power: 10
    }
  ]

  // public onDeleteCharacter: EventEmitter<Character> = new EventEmitter();
  //onDeleteId => va a emitir el Index value:number
  emitDeleteCharacter(index:number): void {
    this.emitDeleteCharacter(index);
  }


}
