<?php

namespace App\Http\Controllers\Api\Student;

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
        return ApiResponse::success(
            TicketResource::collection($this->ticketService->listForStudent($request->user())),
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
            new TicketResource($ticket->load(['messages.sender', 'student', 'assignee'])),
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
