import { Controller } from '@hotwired/stimulus';
import * as Turbo from '@hotwired/turbo';

export default class extends Controller {
  static targets = ['inputField', 'buttonField'];
  static values = { url: String };

  connect() {
    const icon = this.buttonFieldTarget.querySelector('.bi');
    this.buttonFieldTarget.addEventListener('click', (event) => {
      event.preventDefault();
      icon.classList.remove('bi-steam', 'bi');
      icon.classList.add('fa-solid', 'fa-spinner', 'fa-spin');
      Turbo.visit(this.urlValue + '?steamId=' + this.inputFieldTarget.value, {
        action: 'replace',
      });
    });
  }
}
