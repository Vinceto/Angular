import { NgModule } from "@angular/core";
import { CounterComponent } from "./components/counter/counter.component";

@NgModule({
  declarations: [],
  imports: [
    CounterComponent
  ],
  exports:[
    CounterComponent
  ]
})
export class CounterModule {}
