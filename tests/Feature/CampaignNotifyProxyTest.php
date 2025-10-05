<?php

use Illuminate\Support\Facades\Http;

it('forwards to API Gateway and returns upstream status/body', function () {
    Http::fake([
        'https://16qqrjiewk.execute-api.us-east-1.amazonaws.com/prod/campaigns/123/notify' => Http::response(['ok' => true], 200),
    ]);

    $res = $this->postJson('/api/campaigns/123/notify', [
        'channels' => ['in_app'],
    ]);

    $res->assertStatus(200)->assertJson(['ok' => true]);
});


