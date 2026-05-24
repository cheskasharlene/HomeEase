-- Migration: rename Cleaner / Cleaning to House Cleaner
-- Apply to the live database to normalize stored service labels and icon keys.

START TRANSACTION;

UPDATE services
SET name = 'House Cleaner'
WHERE name IN ('Cleaner', 'Cleaning', 'cleaner', 'cleaning');

UPDATE bookings
SET service = 'House Cleaner'
WHERE service IN ('Cleaner', 'Cleaning', 'cleaner', 'cleaning');

UPDATE booking_requests
SET service = 'House Cleaner'
WHERE service IN ('Cleaner', 'Cleaning', 'cleaner', 'cleaning');

UPDATE service_providers
SET service_category = 'House Cleaner'
WHERE service_category IN ('Cleaner', 'Cleaning', 'cleaner', 'cleaning');

UPDATE notifications
SET icon = 'house_cleaner'
WHERE icon IN ('Cleaner', 'Cleaning', 'cleaner', 'cleaning');

UPDATE provider_notifications
SET icon = 'house_cleaner'
WHERE icon IN ('Cleaner', 'Cleaning', 'cleaner', 'cleaning');

COMMIT;