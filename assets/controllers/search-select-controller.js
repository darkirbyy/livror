import { Controller } from '@hotwired/stimulus';
import * as Turbo from '@hotwired/turbo';

// Controller that trigger a refresh when choosing a sorting field in the corresponding select
export default class extends Controller {
  static targets = ['item'];

  connect() {
    super.connect();
    this.element.addEventListener('autocomplete:connect', this._onConnect.bind(this));
  }

  disconnect() {
    super.disconnect();
    this.element.removeEventListener('autocomplete:connect', this._onConnect.bind(this));
  }

  _onConnect(event) {
    // prevent the game from being really selected in the input field
    event.detail.tomSelect.on('change', () => {
      event.detail.tomSelect.clear();
    });

    // for each game, visit the selected game page on click
    event.detail.tomSelect.on('load', () => {
      this.itemTargets.forEach((item) => {
        item.addEventListener('click', (event) => {
          event.preventDefault();
          Turbo.visit(item.getAttribute('data-search-select-url'));
        });
      });
    });
  }
}
