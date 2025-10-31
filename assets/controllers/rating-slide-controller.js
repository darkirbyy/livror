import { Controller } from '@hotwired/stimulus';

// Controller that update the rating when the range cursor is changed
export default class extends Controller {
  static targets = ['range', 'display'];

  connect() {
    this.change(this.rangeTarget.value);
    this.rangeTarget.addEventListener('input', (event) => this.change(event.target.value));
  }

  change(newValue) {
    this.displayTarget.innerText = newValue;
  }
}
