import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static values = { url: String };

  connect() {
    this.element.addEventListener('click', this.loadMore.bind(this));
  }

  loadMore() {
    alert(this.urlValue);
  }
}
