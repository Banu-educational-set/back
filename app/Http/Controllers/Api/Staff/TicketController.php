<?php

namespace App\Http\Controllers\Api\Staff;

use App\Enums\TicketStatus;
use App\Enums\TicketTargetRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\StoreTicketMessageRequest;
use App\Http\Requests\Ticket\UpdateTicketStatusRequest;
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
        $scopeValue = $request->route()?->defaults['staffScope'] ?? null;
        $scope = $scopeValue ? TicketTargetRole::tryFrom((string) $scopeValue) : null;

        return ApiResponse::success(
            TicketResource::collection(
                $this->ticketService->listForStaff($request->user(), $scope),
            ),
        );
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

    public function updateStatus(UpdateTicketStatusRequest $request, Ticket $ticket): JsonResponse
    {
        $updated = $this->ticketService->updateStatus(
            $ticket,
            $request->user(),
            TicketStatus::from($request->validated('status')),
        );

        return ApiResponse::success(new TicketResource($updated), 'Ticket status updated.');
    }
}
