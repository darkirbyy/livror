import { startStimulusApp } from '@symfony/stimulus-bridge';

// Registers Stimulus controllers from controllers.json and in the controllers/ directory
export const app = startStimulusApp(
  require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.[jt]sx?$/
  )
);

document.addEventListener("turbo:load", function() {
  const params = new URLSearchParams(window.location.search);
  if (params.get('anchor')) {
    const element = document.getElementById(params.get('anchor'));
    if (element) element.scrollIntoView({behavior: 'instant'});
  }
});