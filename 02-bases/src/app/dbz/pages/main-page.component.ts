import { Component } from '@angular/core';
import { DbzModule } from "../dbz.module";
import { ListComponent } from "../components/list/list.component";
import { AddCharacterComponent } from "../components/add-character/add-character.component"
import { DbzService } from '../services/dbz.service';
import { Character } from '../interfaces/character.interface';

@Component({
  selector: 'app-dbz-main-page',
  imports: [DbzModule,ListComponent,AddCharacterComponent],
  templateUrl: './main-page.component.html'
})

export class MainPageComponent {

  constructor( private dbzService: DbzService ){}

  get characters(): Character[] {
    return [...this.dbzService.characters];
  }

  onNewCharacter( character:Character ):void{
    this.dbzService.addCharacter(character);
  }

  onDeleteCharacter(id:string):void{
    this.dbzService.deleteCharacterById(id);
  }

}
function uuid(): string | undefined {
  throw new Error('Function not implemented.');
}

