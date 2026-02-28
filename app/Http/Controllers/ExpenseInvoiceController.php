<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseInvoiceRequest;
use App\Http\Requests\UpdateExpenseInvoiceRequest;
use App\Models\ExpenseInvoice;
use Illuminate\Http\JsonResponse;

class ExpenseInvoiceController extends Controller
{
    public function index(): JsonResponse
    {
        $pharmacy = request()->user();

        $invoices = ExpenseInvoice::query()
            ->where('pharmacy_id', $pharmacy->id)
            ->latest()
            ->get();

        return response()->json($invoices);
    }

    public function show(ExpenseInvoice $expenseInvoice): JsonResponse
    {
        $pharmacy = request()->user();

        if ($expenseInvoice->pharmacy_id !== $pharmacy->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json($expenseInvoice);
    }

    public function store(StoreExpenseInvoiceRequest $request): JsonResponse
    {
        $pharmacy = $request->user();
        $data = $request->validated();

        $invoice = ExpenseInvoice::create([
            'pharmacy_id' => $pharmacy->id,
            'amount' => $data['amount'],
            'created_by_name' => $data['created_by_name'],
            'description' => $data['description'],
        ]);

        return response()->json([
            'message' => 'Expense invoice created.',
            'expense_invoice' => $invoice,
        ], 201);
    }

    public function update(UpdateExpenseInvoiceRequest $request, ExpenseInvoice $expenseInvoice): JsonResponse
    {
        $pharmacy = $request->user();

        if ($expenseInvoice->pharmacy_id !== $pharmacy->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $data = $request->validated();

        if (array_key_exists('amount', $data)) {
            $expenseInvoice->amount = $data['amount'];
        }
        if (array_key_exists('created_by_name', $data)) {
            $expenseInvoice->created_by_name = $data['created_by_name'];
        }
        if (array_key_exists('description', $data)) {
            $expenseInvoice->description = $data['description'];
        }
        $expenseInvoice->save();

        return response()->json([
            'message' => 'Expense invoice updated.',
            'expense_invoice' => $expenseInvoice,
        ]);
    }

    public function destroy(ExpenseInvoice $expenseInvoice): JsonResponse
    {
        $pharmacy = request()->user();

        if ($expenseInvoice->pharmacy_id !== $pharmacy->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $expenseInvoice->delete();

        return response()->json([
            'message' => 'Expense invoice deleted.',
        ]);
    }

    public function indexForWarehouse(): JsonResponse
    {
        $warehouse = request()->user();

        $invoices = ExpenseInvoice::query()
            ->where('warehouse_id', $warehouse->id)
            ->latest()
            ->get();

        return response()->json($invoices);
    }

    public function showForWarehouse(ExpenseInvoice $expenseInvoice): JsonResponse
    {
        $warehouse = request()->user();

        if ($expenseInvoice->warehouse_id !== $warehouse->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json($expenseInvoice);
    }

    public function storeForWarehouse(StoreExpenseInvoiceRequest $request): JsonResponse
    {
        $warehouse = $request->user();
        $data = $request->validated();

        $invoice = ExpenseInvoice::create([
            'warehouse_id' => $warehouse->id,
            'amount' => $data['amount'],
            'created_by_name' => $data['created_by_name'],
            'description' => $data['description'],
        ]);

        return response()->json([
            'message' => 'Expense invoice created.',
            'expense_invoice' => $invoice,
        ], 201);
    }

    public function updateForWarehouse(UpdateExpenseInvoiceRequest $request, ExpenseInvoice $expenseInvoice): JsonResponse
    {
        $warehouse = $request->user();

        if ($expenseInvoice->warehouse_id !== $warehouse->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $data = $request->validated();

        if (array_key_exists('amount', $data)) {
            $expenseInvoice->amount = $data['amount'];
        }
        if (array_key_exists('created_by_name', $data)) {
            $expenseInvoice->created_by_name = $data['created_by_name'];
        }
        if (array_key_exists('description', $data)) {
            $expenseInvoice->description = $data['description'];
        }
        $expenseInvoice->save();

        return response()->json([
            'message' => 'Expense invoice updated.',
            'expense_invoice' => $expenseInvoice,
        ]);
    }

    public function destroyForWarehouse(ExpenseInvoice $expenseInvoice): JsonResponse
    {
        $warehouse = request()->user();

        if ($expenseInvoice->warehouse_id !== $warehouse->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $expenseInvoice->delete();

        return response()->json([
            'message' => 'Expense invoice deleted.',
        ]);
    }
}
