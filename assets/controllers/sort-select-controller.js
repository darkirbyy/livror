import { Controller } from '@hotwired/stimulus';
import * as Turbo from '@hotwired/turbo';

export default class extends Controller {
  connect() {
    this.element.addEventListener('change', this.change.bind(this));
  }

  change(event) {
    Turbo.visit(event.target.value, { action: 'replace' });
  }
}
