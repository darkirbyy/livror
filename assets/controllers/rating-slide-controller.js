import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = ['range', 'display', 'bar', 'barsContainer', 'datalist'];

  connect() {
    this.isDragging = false;
    this.updateBars();

    // Bind methods for event listeners
    this.handleMouseMove = this.handleMouseMove.bind(this);
    this.stopDrag = this.stopDrag.bind(this);
  }

  disconnect() {
    this.removeEventListeners();
  }

  updateDisplay() {
    const value = parseFloat(this.rangeTarget.value);

    // Format display
    this.displayTarget.textContent = value.toFixed(1);

    // Update bars
    this.updateBars();
  }

  updateBars() {
    const value = parseFloat(this.rangeTarget.value);
    const activeBarCount = Math.round(value * 10); // 0 to 60

    this.barTargets.forEach((bar, index) => {
      if (index < activeBarCount) {
        bar.classList.add('active');
      } else {
        bar.classList.remove('active');
      }
    });
  }

  handleClick(event) {
    if (this.isDragging) return;
    this.updateValueFromMouse(event);
  }

  startDrag(event) {
    event.preventDefault();
    this.isDragging = true;
    this.updateValueFromMouse(event);

    // Add event listeners
    document.addEventListener('mousemove', this.handleMouseMove);
    document.addEventListener('mouseup', this.stopDrag);
    document.addEventListener('touchmove', this.handleMouseMove, { passive: false });
    document.addEventListener('touchend', this.stopDrag);
  }

  handleMouseMove(event) {
    if (!this.isDragging) return;
    event.preventDefault();
    this.updateValueFromMouse(event);
  }

  stopDrag() {
    this.isDragging = false;
    this.removeEventListeners();
  }

  removeEventListeners() {
    document.removeEventListener('mousemove', this.handleMouseMove);
    document.removeEventListener('mouseup', this.stopDrag);
    document.removeEventListener('touchmove', this.handleMouseMove);
    document.removeEventListener('touchend', this.stopDrag);
  }

  updateValueFromMouse(event) {
    const container = this.barsContainerTarget;
    const rect = container.getBoundingClientRect();

    // Get mouse/touch position
    const clientX = event.touches ? event.touches[0].clientX : event.clientX;

    // Calculate position relative to container
    const x = clientX - rect.left;
    const percentage = Math.max(0, Math.min(1, x / rect.width));

    // Convert to rating (0 to 6)
    const rawValue = percentage * 6;

    // Round to nearest 0.1
    const value = Math.round(rawValue * 10) / 10;

    // Clamp between 0 and 6
    const clampedValue = Math.max(0, Math.min(6, value));

    // Update input
    this.rangeTarget.value = clampedValue.toFixed(1);

    // Trigger change event for form validation
    this.rangeTarget.dispatchEvent(new Event('input', { bubbles: true }));
    this.rangeTarget.dispatchEvent(new Event('change', { bubbles: true }));

    // Update display
    this.updateDisplay();
  }
}
