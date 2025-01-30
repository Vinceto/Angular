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
    console.log('Main Page');
    console.log(character);
  }
}
