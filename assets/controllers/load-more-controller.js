import DynamicButtonController from './dynamic-button-controller.js';

// Controller that extends the dynamic button controller to fetch and add more results
export default class extends DynamicButtonController {
  onClick() {
    fetch(this.urlValue, {
      method: 'GET',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      redirect: 'manual',
    })
      .then((response) => {
        if (response.type === 'opaqueredirect' || response.redirected || !response.ok) {
          throw new Error('Invalid ou redirect response.');
        }
        return response.text();
      })
      .then((html) => {
        this.element.outerHTML = html;
      })
      .catch(() => {
        this.element.outerHTML =
          '<div class="text-center text-secondary fs-6 fst-italic">Erreur de chargement<br><a href="" class="my-2 btn btn-outline-secondary" data-turbo="false">Recharger la page</a></div>';
      });
  }
}
