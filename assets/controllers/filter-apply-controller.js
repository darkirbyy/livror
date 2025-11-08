import { Controller } from '@hotwired/stimulus';
import * as Turbo from '@hotwired/turbo';

// Controller that fetch the checkboxes and apply the filter
export default class extends Controller {
  static targets = ['checkbox', 'button'];
  static values = { url: String, key: String };

  connect() {
    this.buttonTarget.addEventListener('click', this.apply.bind(this));
  }

  apply() {
    // Parse the url and create the filter key and values from the targets
    const url = new URL(this.urlValue, window.location.origin);
    const filterKey = 'filters[' + this.keyValue + ']';
    const filterValues = this.checkboxTargets.filter((checkbox) => checkbox.checked).map((checkbox) => checkbox.value);

    // Remove the old filter values and add the new ones
    const allKeys = [...url.searchParams.keys()];
    allKeys.filter((key) => key.startsWith(filterKey)).forEach((key) => url.searchParams.delete(key));
    filterValues.forEach((value, index) => url.searchParams.append(filterKey + '[' + index + ']', value));

    // Navigate to the url with the turbo-frame
    Turbo.visit(url.toString(), { frame: 'main-section' });
  }
}
