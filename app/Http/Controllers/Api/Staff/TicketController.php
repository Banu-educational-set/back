<?php

namespace App\Http\Controllers\Api\Staff;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
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
        $typeInput = $request->string('type')->toString();
        if ($typeInput !== '' && ! in_array($typeInput, TicketType::values(), true)) {
            return ApiResponse::error(
                __('errors.invalid_value_allowed', ['label' => __('validation.attributes.type'), 'allowed' => implode('، ', TicketType::values())]),
                ['type' => [__('errors.invalid_value')]],
                422,
            );
        }
        $type = $typeInput !== '' ? TicketType::from($typeInput) : null;

        $priorityInput = $request->string('priority')->toString();
        if ($priorityInput !== '' && ! in_array($priorityInput, TicketPriority::values(), true)) {
            return ApiResponse::error(
                __('errors.invalid_value_allowed', ['label' => __('validation.attributes.priority'), 'allowed' => implode('، ', TicketPriority::values())]),
                ['priority' => [__('errors.invalid_value')]],
                422,
            );
        }
        $priority = $priorityInput !== '' ? TicketPriority::from($priorityInput) : null;

        $statusInput = $request->string('status')->toString();
        if ($statusInput !== '' && ! in_array($statusInput, TicketStatus::values(), true)) {
            return ApiResponse::error(
                __('errors.invalid_value_allowed', ['label' => __('validation.attributes.status'), 'allowed' => implode('، ', TicketStatus::values())]),
                ['status' => [__('errors.invalid_value')]],
                422,
            );
        }
        $status = $statusInput !== '' ? TicketStatus::from($statusInput) : null;

        return ApiResponse::success(
            TicketResource::collection(
                $this->ticketService->listForStaff(
                    $request->user(),
                    $type,
                    $priority,
                    $status,
                    (int) $request->integer('per_page', 20),
                    $request->only(['user_name', 'from_date', 'to_date']),
                ),
            ),
        );
    }

    public function stats(Request $request): JsonResponse
    {
        $typeInput = $request->string('type')->toString();
        if ($typeInput !== '' && ! in_array($typeInput, TicketType::values(), true)) {
            return ApiResponse::error(
                __('errors.invalid_value_allowed', ['label' => __('validation.attributes.type'), 'allowed' => implode('، ', TicketType::values())]),
                ['type' => [__('errors.invalid_value')]],
                422,
            );
        }
        $type = $typeInput !== '' ? TicketType::from($typeInput) : null;

        return ApiResponse::success(
            $this->ticketService->statsForStaff(
                $request->user(),
                $type,
                $request->only(['user_name', 'from_date', 'to_date']),
            ),
        );
    }

    public function show(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        return ApiResponse::success(
            new TicketResource($ticket->load(['messages.sender', 'messages.media', 'student', 'assignee', 'media'])),
        );
    }

    public function postMessage(StoreTicketMessageRequest $request, Ticket $ticket): JsonResponse
    {
        $message = $this->ticketService->postMessage(
            $ticket,
            $request->user(),
            $request->validated('message'),
            $request->validated('media_ids', []),
        );

        return ApiResponse::success(
            new TicketMessageResource($message->load('sender')),
            __('messages.message_posted'),
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

        return ApiResponse::success(new TicketResource($updated), __('messages.ticket_status_updated'));
    }
}
