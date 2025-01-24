import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = ['button'];
  static values = { url: String };

  connect() {
    this.buttonTarget.addEventListener('click', this.loadMore.bind(this));
  }

  loadMore() {
    fetch(this.urlValue, {
      method: 'GET',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
      .then((response) => response.text())
      .then((html) => {
        this.element.outerHTML = html;
      });
  }
}
