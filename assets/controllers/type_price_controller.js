import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = ['choiceFields', 'inputField'];

  connect() {
    this.changeChoice(
      this.choiceFieldsTarget.querySelector('[checked="checked"]').value
    );
    this.choiceFieldsTarget.addEventListener('change', (event) => {
      this.changeChoice(event.target.value);
    });
  }

  changeChoice(newValue) {
    this.inputFieldTarget.disabled = newValue != 'paying';
    newValue != 'paying' ? (this.inputFieldTarget.value = '') : null;
  }
}
