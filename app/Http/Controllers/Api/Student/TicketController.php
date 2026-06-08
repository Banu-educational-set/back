<?php

namespace App\Http\Controllers\Api\Student;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\StoreTicketMessageRequest;
use App\Http\Requests\Ticket\StoreTicketRequest;
use App\Http\Resources\TicketMessageResource;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\TicketService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(private readonly TicketService $ticketService) {}

    public function index(Request $request): JsonResponse
    {
        $typeInput = $request->string('type')->toString();
        if ($typeInput !== '' && ! in_array($typeInput, TicketType::values(), true)) {
            return ApiResponse::error(
                'Invalid type. Allowed: '.implode(', ', TicketType::values()).'.',
                ['type' => ['Invalid value.']],
                422,
            );
        }
        $type = $typeInput !== '' ? TicketType::from($typeInput) : null;

        $priorityInput = $request->string('priority')->toString();
        if ($priorityInput !== '' && ! in_array($priorityInput, TicketPriority::values(), true)) {
            return ApiResponse::error(
                'Invalid priority. Allowed: '.implode(', ', TicketPriority::values()).'.',
                ['priority' => ['Invalid value.']],
                422,
            );
        }
        $priority = $priorityInput !== '' ? TicketPriority::from($priorityInput) : null;

        $statusInput = $request->string('status')->toString();
        if ($statusInput !== '' && ! in_array($statusInput, TicketStatus::values(), true)) {
            return ApiResponse::error(
                'Invalid status. Allowed: '.implode(', ', TicketStatus::values()).'.',
                ['status' => ['Invalid value.']],
                422,
            );
        }
        $status = $statusInput !== '' ? TicketStatus::from($statusInput) : null;

        return ApiResponse::success(
            TicketResource::collection($this->ticketService->listForStudent($request->user(), $type, $priority, $status)),
        );
    }

    public function stats(Request $request): JsonResponse
    {
        $typeInput = $request->string('type')->toString();
        if ($typeInput !== '' && ! in_array($typeInput, TicketType::values(), true)) {
            return ApiResponse::error(
                'Invalid type. Allowed: '.implode(', ', TicketType::values()).'.',
                ['type' => ['Invalid value.']],
                422,
            );
        }
        $type = $typeInput !== '' ? TicketType::from($typeInput) : null;

        return ApiResponse::success(
            $this->ticketService->statsForStudent($request->user(), $type),
        );
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        $ticket = $this->ticketService->createForStudent($request->user(), $request->validated());

        return ApiResponse::success(new TicketResource($ticket), 'Ticket created.', 201);
    }

    public function show(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        return ApiResponse::success(
            new TicketResource($ticket->load(['messages.sender', 'student', 'assignee', 'media'])),
        );
    }

    public function postMessage(StoreTicketMessageRequest $request, Ticket $ticket): JsonResponse
    {
        $message = $this->ticketService->postMessage(
            $ticket,
            $request->user(),
            $request->validated('message'),
        );

        return ApiResponse::success(
            new TicketMessageResource($message->load('sender')),
            'Message posted.',
            201,
        );
    }
}
