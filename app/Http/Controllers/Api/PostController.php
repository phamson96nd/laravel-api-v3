<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Post::with('author')
                ->select('id', 'title', 'slug', 'excerpt', 'featured_image', 'status', 'author_id', 'view_count', 'published_at', 'created_at');

            // Filter by status
            if ($request->has('status')) {
                $status = $request->get('status');
                if (in_array($status, ['draft', 'published', 'archived'])) {
                    $query->where('status', $status);
                }
            } else {
                // Default to published posts only
                $query->published();
            }

            // Search by title or content
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                      ->orWhere('content', 'LIKE', "%{$search}%")
                      ->orWhere('excerpt', 'LIKE', "%{$search}%");
                });
            }

            // Sort
            $sortBy = $request->get('sort_by', 'published_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            if (in_array($sortBy, ['published_at', 'created_at', 'view_count', 'title'])) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = min($request->get('per_page', 15), 50); // Max 50 per page
            $posts = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Posts retrieved successfully',
                'data' => $posts->items(),
                'pagination' => [
                    'current_page' => $posts->currentPage(),
                    'last_page' => $posts->lastPage(),
                    'per_page' => $posts->perPage(),
                    'total' => $posts->total(),
                    'from' => $posts->firstItem(),
                    'to' => $posts->lastItem(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve posts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'excerpt' => 'nullable|string|max:500',
                'featured_image' => 'nullable|string|max:255',
                'status' => 'nullable|in:draft,published,archived',
                'author_id' => 'required|exists:users,id',
                'meta_data' => 'nullable|array',
            ]);

            $post = Post::create([
                'title' => $request->title,
                'content' => $request->content,
                'excerpt' => $request->excerpt,
                'featured_image' => $request->featured_image,
                'status' => $request->status ?? 'draft',
                'author_id' => $request->author_id,
                'meta_data' => $request->meta_data,
                'published_at' => $request->status === 'published' ? now() : null,
            ]);

            $post->load('author');

            return response()->json([
                'success' => true,
                'message' => 'Post created successfully',
                'data' => $post
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $post = Post::with('author')->findOrFail($id);
            
            // Increment view count
            $post->incrementViewCount();

            return response()->json([
                'success' => true,
                'message' => 'Post retrieved successfully',
                'data' => $post
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display post by slug.
     */
    public function showBySlug(string $slug): JsonResponse
    {
        try {
            $post = Post::with('author')
                ->where('slug', $slug)
                ->published()
                ->firstOrFail();
            
            // Increment view count
            $post->incrementViewCount();

            return response()->json([
                'success' => true,
                'message' => 'Post retrieved successfully',
                'data' => $post
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $post = Post::findOrFail($id);

            $request->validate([
                'title' => 'sometimes|string|max:255',
                'content' => 'sometimes|string',
                'excerpt' => 'nullable|string|max:500',
                'featured_image' => 'nullable|string|max:255',
                'status' => 'sometimes|in:draft,published,archived',
                'meta_data' => 'nullable|array',
            ]);

            $post->update($request->only([
                'title', 'content', 'excerpt', 'featured_image', 'status', 'meta_data'
            ]));

            // Update published_at if status changed to published
            if ($request->has('status') && $request->status === 'published' && !$post->published_at) {
                $post->update(['published_at' => now()]);
            }

            $post->load('author');

            return response()->json([
                'success' => true,
                'message' => 'Post updated successfully',
                'data' => $post
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $post = Post::findOrFail($id);
            $post->delete();

            return response()->json([
                'success' => true,
                'message' => 'Post deleted successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete post',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
