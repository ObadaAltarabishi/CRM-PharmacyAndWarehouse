<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListFeedbacksRequest;
use App\Http\Requests\StoreFeedbackRequest;
use App\Models\Feedback;
use Illuminate\Http\JsonResponse;

class FeedbackController extends Controller
{
    // POST /api/pharmacy/feedback
    public function storeFromPharmacy(StoreFeedbackRequest $request): JsonResponse
    {
        $pharmacy = $request->user();

        $feedback = Feedback::create([
            'content' => $request->validated()['content'],
            'pharmacy_id' => $pharmacy->id,
        ]);

        return response()->json([
            'message' => 'Feedback sent.',
            'feedback' => $feedback,
        ], 201);
    }

    // POST /api/warehouse/feedback
    public function storeFromWarehouse(StoreFeedbackRequest $request): JsonResponse
    {
        $warehouse = $request->user();

        $feedback = Feedback::create([
            'content' => $request->validated()['content'],
            'warehouse_id' => $warehouse->id,
        ]);

        return response()->json([
            'message' => 'Feedback sent.',
            'feedback' => $feedback,
        ], 201);
    }

    // GET /api/feedbacks (super_admin only)
    public function index(ListFeedbacksRequest $request): JsonResponse
    {
        $feedbacks = Feedback::query()
            ->with(['pharmacy:id,pharmacy_name', 'warehouse:id,warehouse_name'])
            ->latest()
            ->get();

        $response = $feedbacks->map(function (Feedback $feedback) {
            if ($feedback->pharmacy) {
                return [
                    'id' => $feedback->id,
                    'sender_type' => 'pharmacy',
                    'sender_id' => $feedback->pharmacy->id,
                    'sender_name' => $feedback->pharmacy->pharmacy_name,
                    'content' => $feedback->content,
                    'created_at' => $feedback->created_at,
                ];
            }

            if ($feedback->warehouse) {
                return [
                    'id' => $feedback->id,
                    'sender_type' => 'warehouse',
                    'sender_id' => $feedback->warehouse->id,
                    'sender_name' => $feedback->warehouse->warehouse_name,
                    'content' => $feedback->content,
                    'created_at' => $feedback->created_at,
                ];
            }

            return [
                'id' => $feedback->id,
                'sender_type' => 'unknown',
                'sender_id' => null,
                'sender_name' => null,
                'content' => $feedback->content,
                'created_at' => $feedback->created_at,
            ];
        });

        return response()->json($response);
    }

    // GET /api/feedbacks/{feedback} (super_admin only)
    public function show(ListFeedbacksRequest $request, Feedback $feedback): JsonResponse
    {
        $feedback->load(['pharmacy:id,pharmacy_name', 'warehouse:id,warehouse_name']);

        if ($feedback->pharmacy) {
            return response()->json([
                'id' => $feedback->id,
                'sender_type' => 'pharmacy',
                'sender_id' => $feedback->pharmacy->id,
                'sender_name' => $feedback->pharmacy->pharmacy_name,
                'content' => $feedback->content,
                'created_at' => $feedback->created_at,
            ]);
        }

        if ($feedback->warehouse) {
            return response()->json([
                'id' => $feedback->id,
                'sender_type' => 'warehouse',
                'sender_id' => $feedback->warehouse->id,
                'sender_name' => $feedback->warehouse->warehouse_name,
                'content' => $feedback->content,
                'created_at' => $feedback->created_at,
            ]);
        }

        return response()->json([
            'id' => $feedback->id,
            'sender_type' => 'unknown',
            'sender_id' => null,
            'sender_name' => null,
            'content' => $feedback->content,
            'created_at' => $feedback->created_at,
        ]);
    }
}
