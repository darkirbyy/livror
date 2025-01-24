import DynamicButtonController from './dynamic-button-controller.js';

export default class extends DynamicButtonController {
  onClick() {
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
