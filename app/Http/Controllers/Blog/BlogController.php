<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;


class BlogController extends Controller
{
    /**
     * Display a listing of blogs.
     */
    public function index(): Response
    {
        $disk = config('filesystems.default');

        $blogs = Blog::with('author:id,name')
            ->where('status', Blog::STATUS_PUBLISHED)
            ->orderByDesc('published_at')
            ->paginate(15)
            ->through(fn ($blog) => [
                'id'            => $blog->id,
                'title'         => $blog->title,
                'slug'          => $blog->slug,
                'cover_image'   => $blog->cover_image ? Storage::disk($disk)->url($blog->cover_image) : null,
                'author'        => [
                    'id'   => optional($blog->author)->id   ?? 1,
                    'name' => optional($blog->author)->name ?? 'Anonymous',
                ],
                'published_at'  => $blog->published_at,
            ]);

        return Inertia::render('Blog/Index', [
            'blogs' => $blogs,
        ]);
    }

    /**
     * Store a newly created blog in storage.
     */
    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'cover_image' => 'nullable|image|max:2048',
            'status' => 'in:' . implode(',', [
                Blog::STATUS_DRAFT,
                Blog::STATUS_PUBLISHED,
            ]),
        ]);

        $validated['slug'] = Str::slug($validated['title']);
        $validated['author_id'] = auth()->id();

        if ($request->hasFile('cover_image')) {
            $disk = config('filesystems.default');
            $validated['cover_image'] = $request->file('cover_image')->store('blogs', $disk);
        }

        if ($validated['status'] === Blog::STATUS_PUBLISHED) {
            $validated['published_at'] = now();
        }

        $blog = Blog::create($validated);

        return Inertia::render('Blog/Show', [
            'blog' => $blog->load('author:id,name'),
        ]);
    }

    /**
     * Display the specified blog.
     */
    public function show(string $id): Response
    {
        $blog = Blog::with('author:id,name')
            ->where('status', Blog::STATUS_PUBLISHED)
            ->findOrFail($id);
        $disk = config('filesystems.default');

        return \Inertia\Inertia::render('Blog/Show', [
            'blog' => [
                'id'           => $blog->id,
                'title'        => $blog->title,
                'slug'         => $blog->slug,
                'cover_image'   => $blog->cover_image ? Storage::disk($disk)->url($blog->cover_image) : null,
                'content'      => $blog->content,
                'author'       => [
                    'id'   => $blog->author?->id ?? 0,
                    'name' => $blog->author?->name ?? 'Anonymous',
                ],
                'published_at' => $blog->published_at,
                'status'       => $blog->status,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $blog = Blog::findOrFail($id);

        // Check if user is authorized to update
        $this->authorize('update', $blog);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes',
            'cover_image' => 'nullable|image|max:2048',
            'status' => 'in:' . implode(',', [
                Blog::STATUS_DRAFT,
                Blog::STATUS_PUBLISHED,
            ]),
        ]);

        if (isset($validated['title'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $disk = config('filesystems.default');

        
        if ($request->hasFile('cover_image')) {
            // Delete image in public folder (Will be replaced later)
            
            Storage::disk($disk)->delete($blog->cover_image);
            $validated['cover_image'] = $request->file('cover_image')->store('blogs', $disk);
        } else {
            unset($validated['cover_image']);
        }

        if (isset($validated['status']) && $validated['status'] === Blog::STATUS_PUBLISHED) {
            $validated['published_at'] = now();
        }

        $blog->update($validated);

        return redirect()->route('healthcare.blog.index');
    }

    /**
     * Publish the selected blog
     */
    public function publish(string $id)
    {
        $blog = Blog::findOrFail($id);

        // Check if user is authorized to update
        $this->authorize('update', $blog);

        $blog->update([
            'status'       => Blog::STATUS_PUBLISHED,
            'published_at' => now()
        ]);

        return response()->json([
            'message'  => 'Blog status updated successfully!',
            'blog' => $blog,
        ], 201);
    }

    /**
     * Make the selected blog a draft
     */
    public function draft(string $id)
    {
        $blog = Blog::findOrFail($id);

        // Check if user is authorized to update
        $this->authorize('update', $blog);

        $blog->update([
            'status'       => Blog::STATUS_DRAFT,
            'published_at' => null
        ]);

        return response()->json([
            'message'  => 'Blog status updated successfully!',
            'blog' => $blog,
        ], 201);
    }

    /**
     * Soft delete the specified resource.
     */
    public function destroy(string $id)
    {
        $blog = Blog::findOrFail($id);

        // Check if user is authorized to soft delete
        $this->authorize('delete', $blog);

        $blog->delete();

        return response()->json([
            'message'  => 'Blog deleted successfully!',
        ], 201);
    }

    /**
     * Restore the soft deleted blog
     */
    public function restore(string $id)
    {
        $blog = Blog::withTrashed()->findOrFail($id);

        // Check if user is authorized to restore
        $this->authorize('restore', $blog);

        $blog->restore();
        return response()->json(['message' => 'Restored successfully']);
    }

    /**
     * Hard delete the specified resource
     */
    public function hardDestroy(string $id)
    {
        $blog = Blog::withTrashed()->findOrFail($id);

        // Check if user is authorized to hard delete
        $this->authorize('forceDelete', $blog);

        $blog->forceDelete();
        return response()->json(['message' => 'Hard Deleted successfully']);
    }
}
