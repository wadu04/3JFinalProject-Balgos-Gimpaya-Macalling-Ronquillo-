USE booking;

-- Reset all schedules to have full available seats
UPDATE schedules SET available_seats = 60;

-- Clear any existing bookings and booked seats
TRUNCATE TABLE booked_seats;
TRUNCATE TABLE bookings;

-- Verify the update
SELECT id, departure_time, bus_class_id, available_seats 
FROM schedules 
ORDER BY departure_time, bus_class_id;
