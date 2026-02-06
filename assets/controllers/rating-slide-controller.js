import { Controller } from '@hotwired/stimulus';

// Controller that handle the custom slider for the rating (bars and label)
export default class extends Controller {
  static targets = ['range', 'label', 'bar', 'container', 'datalist'];

  /////////////////////////////////
  // Initialize ///////////////////
  /////////////////////////////////

  connect() {
    // init values
    this.isDragging = false;
    this.updateDisplay();

    // bind events
    this.rangeTarget.addEventListener('change', () => this.updateDisplay());
    this.containerTarget.addEventListener('mousedown', (event) => this.startDrag(event));
    this.containerTarget.addEventListener('click', (event) => this.handleClick(event));

    // bind methods for event listeners
    this.handleMouseMove = this.handleMouseMove.bind(this);
    this.stopDrag = this.stopDrag.bind(this);
  }

  disconnect() {
    this.removeEventListeners();
  }

  /////////////////////////////////
  // Update visual ////////////////
  /////////////////////////////////

  updateDisplay() {
    const newValue = parseFloat(this.rangeTarget.value);
    this.updateLabel(newValue);
    this.updateBars(newValue);
  }

  updateLabel(newValue) {
    const newOption = [...this.datalistTarget.children].find((option) => {
      return parseFloat(option.getAttribute('value')) == newValue;
    });

    this.labelTarget.textContent = newOption.getAttribute('label');
  }

  updateBars(newValue) {
    const activeBarCount = Math.round(newValue * 10);

    this.barTargets.forEach((bar, index) => {
      index < activeBarCount ? bar.classList.add('active') : bar.classList.remove('active');
      index == activeBarCount - 1 ? bar.classList.add('active-last') : bar.classList.remove('active-last');
    });
  }

  /////////////////////////////////
  // Drag behavior ////////////////
  /////////////////////////////////

  startDrag(event) {
    event.preventDefault();

    this.isDragging = true;
    this.rangeTarget.focus();
    this.containerTarget.classList.add('dragged');
    this.updateValueFromMouse(event);

    document.addEventListener('mousemove', this.handleMouseMove);
    document.addEventListener('mouseup', this.stopDrag);
    document.addEventListener('touchmove', this.handleMouseMove, { passive: false });
    document.addEventListener('touchend', this.stopDrag);
  }

  stopDrag() {
    this.isDragging = false;
    this.containerTarget.classList.remove('dragged');
    this.removeEventListeners();
  }

  removeEventListeners() {
    document.removeEventListener('mousemove', this.handleMouseMove);
    document.removeEventListener('mouseup', this.stopDrag);
    document.removeEventListener('touchmove', this.handleMouseMove);
    document.removeEventListener('touchend', this.stopDrag);
  }

  handleClick(event) {
    if (this.isDragging) return;
    this.updateValueFromMouse(event);
  }

  handleMouseMove(event) {
    if (!this.isDragging) return;
    event.preventDefault();
    this.updateValueFromMouse(event);
  }

  /////////////////////////////////
  // Handle mouse movement ////////
  /////////////////////////////////

  updateValueFromMouse(event) {
    // Get mouse/touch position
    const container = this.containerTarget;
    const rect = container.getBoundingClientRect();
    const clientX = event.touches ? event.touches[0].clientX : event.clientX;

    // Calculate position relative to container
    const x = clientX - rect.left;
    const percentage = Math.max(0, Math.min(1, x / rect.width));

    // Round to nearest 0.1 between 0 and 6
    const value = Math.round(percentage * 6 * 10) / 10;
    const clampedValue = Math.max(0, Math.min(6, value));

    // Update input and trigger change event for form validation
    this.rangeTarget.value = clampedValue.toFixed(1);
    this.rangeTarget.dispatchEvent(new Event('input', { bubbles: true }));

    // Update display
    this.updateDisplay();
  }
}
