'use strict';
Object.defineProperty(exports, '__esModule', { value: true });
exports.handler = void 0;
const pg_1 = require('pg');
// Lambda handler
const handler = async (event) => {
    const client = new pg_1.Client({
        host: 'ddac-assignment.cvmcftzo6z2b.us-east-1.rds.amazonaws.com',
        user: 'postgres',
        password: 'wrm2954123',
        database: 'laravel',
    });
    await client.connect();
    for (const record of event.Records) {
        const booking = JSON.parse(record.body);
        // Check again in DB for double booking because SQS prevents race conditions
        const res = await client.query(
            `SELECT COUNT(*) FROM bookings 
       WHERE schedule_id=$1 AND start_time < $2 AND end_time > $3
       AND status IN ('PENDING','CONFIRMED')`,
            [booking.schedule_id, booking.end_time, booking.start_time],
        );
        if (parseInt(res.rows[0].count) > 0) {
            console.log('Slot already booked, rejecting:', booking);
            continue; // skip this booking
        }
        // Insert booking into DB
        await client.query(
            `INSERT INTO bookings (schedule_id, patient_id, start_time, end_time, status)
       VALUES ($1,$2,$3,$4,$5)`,
            [booking.schedule_id, booking.patient_id, booking.start_time, booking.end_time, booking.status],
        );
    }
    await client.end();
};
exports.handler = handler;
