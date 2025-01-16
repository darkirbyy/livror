import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = ['imageDiv'];

  connect() {
    this.imageDivTargets.forEach((imageDiv) => {
      const image = imageDiv.querySelector('img');
      const icon = imageDiv.querySelector('div');

      image.addEventListener('load', () => {
        image.classList.remove('placeholder');
      });
      image.addEventListener('error', () => {
        image.remove();
        icon.classList.remove('d-none');
      });
    });
  }
}
