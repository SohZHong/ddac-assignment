<?php

namespace App\Console\Commands;

use App\Models\Event;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendReminders extends Command
{
    protected $signature = 'reminders:send {--offset= : Override offset minutes}';
    protected $description = 'Send event start reminders based on offset (default 3 minutes)';

    public function handle(): int
    {
        $offset = (int) ($this->option('offset') ?? config('services.serverless.reminder_default_offset_minutes'));
        $now = now();
        $windowStart = $now->copy()->addMinutes($offset - 1);
        $windowEnd = $now->copy()->addMinutes($offset + 1);

        $events = Event::where('status', 'published')
            ->whereBetween('start_datetime', [$windowStart, $windowEnd])
            ->get();

        foreach ($events as $event) {
            // Avoid duplicates using metadata flag
            $meta = $event->metadata ?? [];
            if (!empty($meta['reminder_sent'])) {
                continue;
            }

            Log::info('Reminder sent (demo)', [
                'event_id' => $event->id,
                'title' => $event->title,
                'scheduled_for' => $event->start_datetime->toIso8601String(),
                'offset_minutes' => $offset,
            ]);

            $meta['reminder_sent'] = true;
            $event->metadata = $meta;
            $event->save();
        }

        $this->info('Processed reminders for '.$events->count().' events');
        return self::SUCCESS;
    }
}


