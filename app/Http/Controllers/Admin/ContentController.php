<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Inertia\Inertia;
use Inertia\Response;

class ContentController extends Controller
{
    /**
     * Show public content management for admins.
     */
    public function index(): Response
    {
        $active = Blog::with('author:id,name')
            ->latest('published_at')
            ->get()
            ->map(fn (Blog $b) => [
                'id' => (string) $b->id,
                'title' => $b->title,
                'status' => (bool) $b->status,
                'published_at' => optional($b->published_at)->toDateTimeString(),
                'author' => [
                    'id' => optional($b->author)->id,
                    'name' => optional($b->author)->name,
                ],
            ]);

        $trashed = Blog::onlyTrashed()
            ->with('author:id,name')
            ->latest('deleted_at')
            ->get()
            ->map(fn (Blog $b) => [
                'id' => (string) $b->id,
                'title' => $b->title,
                'deleted_at' => optional($b->deleted_at)->toDateTimeString(),
                'author' => [
                    'id' => optional($b->author)->id,
                    'name' => optional($b->author)->name,
                ],
            ]);

        return Inertia::render('Admin/Content', [
            'active' => $active,
            'trashed' => $trashed,
        ]);
    }
}


