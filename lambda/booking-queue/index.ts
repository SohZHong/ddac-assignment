import { SQSEvent } from 'aws-lambda';
import { Client } from 'pg';

// Lambda handler
export const handler = async (event: SQSEvent) => {
    const client = new Client({
        host: process.env.DB_HOST,
        user: process.env.DB_USERNAME,
        password: process.env.DB_PASSWORD,
        database: process.env.DB_DATABASE,
        port: Number(process.env.DB_PORT),
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
            `INSERT INTO bookings (schedule_id, patient_id, start_time, end_time, status, created_at)
             VALUES ($1,$2,$3,$4,$5,NOW())`,
            [booking.schedule_id, booking.patient_id, booking.start_time, booking.end_time, booking.status],
        );

        // Optional: Notify patient via SNS
        // await sns.send(
        //     new PublishCommand({
        //         TopicArn: process.env.NOTIFY_TOPIC_ARN,
        //         Message: JSON.stringify({
        //             type: 'booking_confirmation',
        //             recipient: booking.patient_email,
        //             message: 'Your booking has been confirmed.',
        //         }),
        //         Subject: 'Booking Confirmation',
        //     }),
        // );
    }

    await client.end();
};
