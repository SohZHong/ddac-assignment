'use strict';
Object.defineProperty(exports, '__esModule', { value: true });
exports.handler = void 0;
const client_sns_1 = require('@aws-sdk/client-sns');
const pg_1 = require('pg');
const sns = new client_sns_1.SNSClient({ region: process.env.AWS_DEFAULT_REGION });
// Create a single client instance outside handler for reuse across warm starts
const client = new pg_1.Client({
    host: process.env.DB_HOST,
    user: process.env.DB_USERNAME,
    password: process.env.DB_PASSWORD,
    database: process.env.DB_DATABASE,
    ssl: { rejectUnauthorized: false },
});

let clientReady = null;
// Ensure the DB connection is initialized
async function ensureDbConnection() {
    if (!clientReady) {
        clientReady = client.connect();
    }
    return clientReady;
}
async function processRecord(record) {
    const booking = JSON.parse(record.body);
    // Double-check for overlapping bookings
    const res = await client.query(
        `SELECT COUNT(*) FROM bookings 
       WHERE schedule_id=$1 
         AND start_time < $2 
         AND end_time > $3
         AND status IN ('0','1')`,
        [booking.schedule_id, booking.end_time, booking.start_time],
    );
    if (parseInt(res.rows[0].count) > 0) {
        console.log('Slot already booked, rejecting:', booking);
        return;
    }
    try {
        await client.query(
            `INSERT INTO bookings (schedule_id, patient_id, start_time, end_time, status, created_at)
       VALUES ($1,$2,$3,$4,$5,NOW())`,
            [booking.schedule_id, booking.patient_id, booking.start_time, booking.end_time, booking.status ?? 'PENDING'],
        );
        const startTime = new Date(booking.start_time).toLocaleString('en-US', {
            dateStyle: 'medium',
            timeStyle: 'short',
            timeZone: 'Asia/Singapore',
        });

        const endTime = new Date(booking.end_time).toLocaleString('en-US', {
            dateStyle: 'medium',
            timeStyle: 'short',
            timeZone: 'Asia/Singapore',
        });

        const message = `
New Booking Request
          
Patient ID: ${booking.patient_id}
Schedule ID: ${booking.schedule_id}
Start Time: ${startTime}
End Time:   ${endTime}
        `;
        await sns.send(
            new client_sns_1.PublishCommand({
                TopicArn: process.env.DOCTOR_SNS_TOPIC_ARN,
                Message: message,
                Subject: 'New Booking Notification',
            }),
        );
        console.log('Booking confirmed:', booking);
    } catch (err) {
        // Handle race condition: duplicate insert if two Lambdas race
        if (err.code === '23505') {
            console.warn('Duplicate booking ignored:', booking);
        } else {
            throw err; // rethrow other DB errors
        }
    }
}
// Lambda handler
const handler = async (event) => {
    await ensureDbConnection();
    for (const record of event.Records) {
        try {
            await processRecord(record);
        } catch (err) {
            console.error('Error processing record:', record, err);
        }
    }
};
exports.handler = handler;
