import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = ['image', 'placeholder'];

  connect() {
    if (this.imageTarget.complete) {
      this.isLoaded();
    } else {
      this.imageTarget.classList.add('placeholder');
      this.imageTarget.addEventListener('load', () => {
        this.isLoaded();
      });
    }
  }

  isLoaded() {
    this.imageTarget.classList.remove('placeholder');
  }
}
