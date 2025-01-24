import { Controller } from '@hotwired/stimulus';

export default class DynamicButtonController extends Controller {
  static targets = ['button', 'remove', 'spinner'];
  static values = { url: String, lazy: Boolean };

  connect() {
    if (this.lazyValue) {
      this.onVisible(this.buttonTarget, this.prepare.bind(this));
    }
    this.buttonTarget.addEventListener('click', (event) => {
      event.preventDefault();
      this.prepare();
    });
  }

  onVisible(element, callback) {
    new IntersectionObserver((entries, observer) => {
      entries.forEach((entry) => {
        if (entry.intersectionRatio > 0) {
          callback();
          observer.disconnect();
        }
      });
    }).observe(element);
    if (!callback) return new Promise((r) => (callback = r));
  }

  prepare() {
    this.removeTargets.forEach((item) => {
      item.remove();
    });
    this.spinnerTarget.innerHTML =
      '<span class="fa fa-spin fa-spinner"></span>';
    this.onClick();
  }

  // Override this method to customize the click (and lazy load if enabled) behavior
  onClick() {
    throw new Error('The onClick method must be implemented');
  }
}
