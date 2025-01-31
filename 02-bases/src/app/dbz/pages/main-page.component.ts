import { Component } from '@angular/core';
import { DbzModule } from "../dbz.module";
import { ListComponent } from "../components/list/list.component";
import { AddCharacterComponent } from "../components/add-character/add-character.component"
import { Character } from '../interfaces/character.interface';

@Component({
  selector: 'app-dbz-main-page',
  imports: [DbzModule,ListComponent,AddCharacterComponent],
  templateUrl: './main-page.component.html'
})

export class MainPageComponent {

  public characters: Character[] = [{
    name: 'Krillin',
    power: 1000
  },
  {
    name: 'Goku',
    power: 9500
  },
  {
    name: 'Vegeta',
    power: 7500
  }];

  onNewCharacter(character:Character):void{
    this.characters.push(character);
  }

  OnDeleteCharacter(character:Character):void{
    const index = this.characters.findIndex(c => c.name === character.name && c.power === character.power);
    if (index !== -1) {
      this.characters.splice(index, 1);
    }
  }
}
