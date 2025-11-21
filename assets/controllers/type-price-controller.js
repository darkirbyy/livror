import { Controller } from '@hotwired/stimulus';

// Controller that enable/disable the price input field, depending on the type-of-price radio button currently selected
export default class extends Controller {
  static targets = ['choices', 'input'];

  connect() {
    this.changeChoice(this.choicesTarget.querySelector('[checked="checked"]').value);
    this.choicesTarget.addEventListener('change', (event) => {
      this.changeChoice(event.target.value);
    });
  }

  changeChoice(newValue) {
    this.inputTarget.disabled = newValue != 'PAYING';
    newValue != 'PAYING' ? (this.inputTarget.value = '') : null;
  }
}
