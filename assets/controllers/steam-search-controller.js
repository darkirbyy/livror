import DynamicButtonController from './dynamic-button-controller.js';
import * as Turbo from '@hotwired/turbo';

// Controller that extends the dynamic button controller to fetch steam data from the API without submitting the form
export default class extends DynamicButtonController {
  static targets = ['input'];

  onClick() {
    Turbo.visit(this.urlValue + '?steamId=' + this.inputTarget.value, {
      action: 'replace',
    });
  }
}
