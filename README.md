# HomeEase

This project is a PHP/JavaScript web application for managing home service requests.

## Onboarding Flow

A modern, mobile‑responsive multi‑step onboarding has been added:

1. **Splash screen** – teal background with a centered white house icon and app name; shown for 2–3 seconds with a circular orange‑accent spinner.
2. **Slide carousel** – three horizontal, swipeable cards with friendly messages and flat vector illustrations. Navigation dots and a large rounded teal button move users through the slides; the button text is “Next” for the first two slides and “Continue” on the third.
3. **User type selection** – after the slides a clean white screen asks “Choose how you want to use HomeEase” with two large rounded buttons (**Homeowner** / **Service Provider**) styled in teal and darker teal respectively. Each button has a bold main label and smaller subtext.

Choosing either option takes the user directly to the existing login/signup page (`index.php`).

The first visit (per session) redirects to `onboarding.php`; subsequent visits go directly to the login screen.  
Styles for the flow are defined in `assets/css/onboarding.css` and behavior in `assets/js/onboarding.js`.
