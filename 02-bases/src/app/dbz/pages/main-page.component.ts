import { Component } from '@angular/core';
import { DbzModule } from "../dbz.module";
import { ListComponent } from "../components/list/list.component";
import { AddCharacterComponent } from "../components/add-character/add-character.component"

@Component({
  selector: 'app-dbz-main-page',
  imports: [DbzModule,ListComponent,AddCharacterComponent],
  templateUrl: './main-page.component.html'
})

export class MainPageComponent {

}
