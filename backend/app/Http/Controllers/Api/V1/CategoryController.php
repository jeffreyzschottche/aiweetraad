<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::withCount('questions')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $categories]);
    }

    public function show(Category $category): JsonResponse
    {
        $category->loadCount('questions');

        $questions = $category->questions()
            ->where('status', 'published')
            ->withCount(['answers' => fn ($query) => $query->where('status', '!=', 'failed')])
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => $category,
            'questions' => $questions,
        ]);
    }
}
