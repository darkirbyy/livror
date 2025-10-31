import { Controller } from '@hotwired/stimulus';

// Controller that update the rating when the range cursor is changed
export default class extends Controller {
  static targets = ['range', 'display', 'datalist'];

  connect() {
    this.change(this.rangeTarget.value);
    this.rangeTarget.addEventListener('input', (event) => this.change(event.target.value));
  }

  change(newValue) {
    const newOption = [...this.datalistTarget.children].find((option) => {
      return option.getAttribute('value') == newValue;
    });
    this.displayTarget.innerText = newOption.getAttribute('label');
  }
}
