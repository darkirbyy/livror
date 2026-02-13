import { Controller } from '@hotwired/stimulus';
import { Popover } from 'bootstrap';
import * as bootstrap from 'bootstrap';

/**
 * Stimulus controller that CAN be placed on any tag, to enable any popover nested inside it.
 */
export default class extends Controller {
  connect() {
    // authorize data-turbo-frame on a tag for the sanitizer
    const myDefaultAllowList = bootstrap.Tooltip.Default.allowList;
    myDefaultAllowList.a.push('data-turbo-frame');

    // load each popover
    const popoverTriggerList = this.element.querySelectorAll('[data-bs-toggle="popover"]');
    popoverTriggerList.forEach((item) => {
      new Popover(item, {
        container: item.parentElement,
        trigger: 'focus',
        placement: 'top',
        html: true,
        delay: { show: 0, hide: 100 },
        animation: false,
        customClass: item.classList.contains('app-cell-secondary') ? 'bg-body-secondary' : 'bg-primary bg-gradient',
      });
    });
  }
}
