import { Controller } from '@hotwired/stimulus';

// Controller that load the content of the attachment modal to display the description and the image
export default class extends Controller {
  static targets = ['form', 'title', 'image'];

  connect() {
    this.element.addEventListener('show.bs.modal', (event) => {
      const button = event.relatedTarget;
      this.titleTarget.innerText = button.getAttribute('data-bs-title');
      this.imageTarget.setAttribute('src', button.getAttribute('data-bs-path'));
    });
  }
}
