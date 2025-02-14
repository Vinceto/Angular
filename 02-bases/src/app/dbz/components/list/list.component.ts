import { Component, EventEmitter, Input, Output } from '@angular/core';
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

  @Output()
  // public onDelete: EventEmitter<number> = new EventEmitter();
  // onDeleteCharacter(index:number): void {
  //   this.onDelete.emit(index);
  // }
  public onDelete: EventEmitter<string> = new EventEmitter();

  deleteCharacterById(id?:string):void {
    if (!id) return;
    this.onDelete.emit(id)
  }

}
