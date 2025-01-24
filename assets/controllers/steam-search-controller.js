import DynamicButtonController from './dynamic-button-controller.js';
import * as Turbo from '@hotwired/turbo';

export default class extends DynamicButtonController {
  static targets = ['input'];

  onClick() {
    Turbo.visit(this.urlValue + '?steamId=' + this.inputTarget.value, {
      action: 'replace',
    });
  }
}
