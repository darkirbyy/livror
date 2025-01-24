import { Controller } from '@hotwired/stimulus';

// Abstract controller to create dynamic buttons, that will display a spinner while loading/fetching new content
// Put the controller on a parent div, with theses targets and values :
// - target button : element that should trigger the onClick
// - targets remove : elements that should be removed when onClick is triggered
// - target spinner : element that should be emptied and replaced with the spinner when onClick is triggered
// - value url : not use but already there for the onClick method
// - value lazy : also trigger the onClick when the div is visible
export default class DynamicButtonController extends Controller {
  static targets = ['button', 'remove', 'spinner'];
  static values = { url: String, lazy: Boolean };

  // Override this method to customize the click (and lazy load if enabled) behavior
  onClick() {
    throw new Error('The onClick method must be implemented');
  }

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
}
