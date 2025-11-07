import { Controller } from '@hotwired/stimulus';
import * as Turbo from '@hotwired/turbo';

// Controller that trigger a refresh when choosing a sorting field in the corresponding select
export default class extends Controller {
  connect() {
    this.element.addEventListener('change', this.change.bind(this));
  }

  change(event) {
    Turbo.visit(event.target.value, { frame: 'main-section' });
  }
}
