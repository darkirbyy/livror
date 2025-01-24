import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = ['choices', 'input'];

  connect() {
    this.changeChoice(
      this.choicesTarget.querySelector('[checked="checked"]').value
    );
    this.choicesTarget.addEventListener('change', (event) => {
      this.changeChoice(event.target.value);
    });
  }

  changeChoice(newValue) {
    this.inputTarget.disabled = newValue != 'paying';
    newValue != 'paying' ? (this.inputTarget.value = '') : null;
  }
}
