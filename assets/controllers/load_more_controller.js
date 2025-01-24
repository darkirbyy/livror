import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = ['button'];
  static values = { url: String, lazy: Boolean };

  connect() {
    if (this.lazyValue) {
      this.onVisible(this.buttonTarget, this.loadMore.bind(this));
    }
    this.buttonTarget.addEventListener('click', this.loadMore.bind(this));
  }

  onVisible(element, callback) {
    new IntersectionObserver((entries, observer) => {
      entries.forEach((entry) => {
        if (entry.intersectionRatio > 0) {
          callback(element);
          observer.disconnect();
        }
      });
    }).observe(element);
    if (!callback) return new Promise((r) => (callback = r));
  }

  loadMore() {
    this.element.innerHTML =
      '<span class="fa fa-spin fa-spinner text-muted fs-4 my-2"></span>';
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
