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

### Homeowner / Service Provider Routing

- Tapping **"I am a Homeowner"** still leads to `index.php` for login/signup. The existing authentication code then redirects a logged‑in homeowner to `home.php` (client dashboard) with no further changes needed.
- Tapping **"I am a Service Provider"** now shows a new placeholder page (`provider/provider_service_provider.php`) with a branded "Service Provider Dashboard — Coming Soon" message. This page uses the soft teal background and a tools icon to maintain visual consistency.

**Provider Login/Signup**

The service provider login form mirrors the homeowner login UI but posts to a separate API endpoint (`api/provider_login.php` / `api/provider_register.php`) connected to a dedicated `service_providers` database table. This table (created automatically if it doesn't exist) stores provider details such as name, email, contact number, category, address, hashed password, optional profile image, availability status and a 4‑digit PIN for extra security. Emails are unique to prevent duplicate accounts. Upon successful authentication the user is redirected to `provider/provider_home.php` (the provider dashboard) instead of `home.php`. The layout, spacing and branding remain identical to keep the experience unified.

**Provider Dashboard**

`provider/provider_home.php` hosts the mobile‑responsive service provider dashboard. It reuses the same color palette, typography, rounded cards and soft shadows as the homeowner interface but reorganizes content for providers. Key sections include:

1. Top header with profile picture, notification icon and an online/offline availability toggle.
2. Quick‑stats chips for pending requests, active jobs, average rating and earnings.
3. Incoming service requests list with accept/decline actions.
4. My Services management panel with edit/delete and add‑new functionality.
5. Schedule overview of upcoming bookings.
6. Earnings summary and recent payment history.
7. Reviews & ratings feed.
8. Fixed bottom navigation with tabs: Dashboard, Requests, Services, Schedule, Profile.

Additional placeholder pages (`provider/provider_requests.php`, `provider/provider_services.php`, `provider/provider_schedule.php`, `provider/provider_profile.php`) provide the structure for future expansion.

The dashboard CSS is largely inherited from `assets/css/home.css`, with provider‑specific tweaks added inline in the page to support new components.

