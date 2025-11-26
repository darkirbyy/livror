import DynamicButtonController from './dynamic-button-controller.js';
import * as Turbo from '@hotwired/turbo';

// Controller that extends the dynamic button controller to fetch steam data from the API without submitting the form
export default class extends DynamicButtonController {
  static targets = ['input'];

  connect() {
    super.connect();
    this.element.addEventListener('autocomplete:pre-connect', this._onPreConnect.bind(this));
    this.element.addEventListener('autocomplete:connect', this._onConnect.bind(this));
  }

  disconnect() {
    super.disconnect();
    this.element.removeEventListener('autocomplete:connect', this._onConnect.bind(this));
    this.element.removeEventListener('autocomplete:pre-connect', this._onPreConnect.bind(this));
  }

  _onPreConnect(event) {
    // Allow to create an option, but do not persist it
    event.detail.options.create = true;
    event.detail.options.createOnBlur = true;
    event.detail.options.persist = false;
  }

  _onConnect(event) {
    // Add bootsrap classes to circumvent input-group css limitation
    event.detail.tomSelect.control.classList.add('rounded-start');
    event.detail.tomSelect.wrapper.classList.add('rounded-start');
  }

  onClick() {
    Turbo.visit(this.urlValue + '?steamId=' + this.inputTarget.value, {
      action: 'replace',
    });
  }
}
