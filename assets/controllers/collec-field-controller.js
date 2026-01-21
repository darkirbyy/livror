import { Controller } from '@hotwired/stimulus';

/**
 * Stimulus controller that MUST be placed on the row of collection type field, to enable add/remove buttons.
 */
export default class extends Controller {
  static targets = ['add', 'remove', 'focus', 'collec'];

  initialize() {
    this.prototype = this.element.querySelector('[data-prototype]').getAttribute('data-prototype');
    this.index = 0;
  }

  connect() {
    this.addTarget.addEventListener('click', this.addToCollec.bind(this));
    this.removeTargets.forEach((removeTarget) => {
      removeTarget.addEventListener('click', (event) => {
        this.removeFromCollec(event, removeTarget);
      });
      this.index++;
    });
  }

  addToCollec(event) {
    event.preventDefault();

    const newItemHTML = this.prototype.replace(/__name__/g, this.index);
    this.collecTarget.insertAdjacentHTML('beforeend', newItemHTML);

    const newRemoveTarget = this.collecTarget.lastElementChild.querySelector('[data-collec-field-target="remove"]');
    newRemoveTarget.addEventListener('click', (event) => {
      this.removeFromCollec(event, newRemoveTarget);
    });

    const newFocusTarget = this.collecTarget.lastElementChild.querySelector('[data-collec-field-target="focus"]');
    if (newFocusTarget) {
      newFocusTarget.focus();
    } else {
      event.currentTarget.blur();
    }

    this.index++;
  }

  removeFromCollec(event, removeTarget) {
    event.preventDefault();
    removeTarget.closest('div').remove();
  }
}
