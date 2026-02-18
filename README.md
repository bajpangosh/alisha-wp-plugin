# Alisha WP Plugin

WordPress companion plugin for the Alisha Flutter app.

## Features
- App configuration endpoint (`/wp-json/alisha/v1/app-config`)
- Onboarding configuration endpoint (`/wp-json/alisha/v1/onboarding`)
- Admin dashboard for app settings, menus, and onboarding steps
- GitHub-based plugin updater (release workflow)

## Installation
1. Zip this plugin folder.
2. In WordPress admin: **Plugins > Add New > Upload Plugin**.
3. Activate **Alisha App Manager**.
4. Open **Alisha App** menu in WP admin.

## API Access Control
The config/onboarding endpoints require a valid app id.

Default expected app id:
- `com.kloudboy.alisha`

If your mobile app uses a different API app id, override it in WordPress:

```php
add_filter('alisha_expected_app_id', function () {
    return 'com.yourcompany.your-api-app-id';
});
```

## Mobile App Mapping
Use the same app id value in your Flutter build define:

```bash
--dart-define=ALISHA_API_APP_ID=com.yourcompany.your-api-app-id
```
