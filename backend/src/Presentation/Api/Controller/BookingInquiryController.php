<?php

declare(strict_types=1);

namespace App\Presentation\Api\Controller;

use App\Application\Command\BookingInquiry\Accept\AcceptBookingInquiryCommand;
use App\Application\Command\BookingInquiry\Decline\DeclineBookingInquiryCommand;
use App\Application\Command\BookingInquiry\MarkRead\MarkBookingInquiriesReadCommand;
use App\Application\Command\BookingInquiry\Reply\ReplyToBookingInquiryCommand;
use App\Application\Command\BookingInquiry\Submit\SubmitBookingInquiryCommand;
use App\Application\Command\CommandBusInterface;
use App\Application\Query\BookingInquiry\GetMyBookingInquiries\GetMyBookingInquiriesQuery;
use App\Application\Query\QueryBusInterface;
use App\Domain\BookingInquiry\Repository\BookingInquiryRepositoryInterface;
use App\Domain\User\Entity\User;
use App\Infrastructure\Recaptcha\RecaptchaVerifier;
use App\Presentation\Api\Response\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api', name: 'api_booking_inquiry_')]
class BookingInquiryController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly RecaptchaVerifier $recaptcha,
        private readonly BookingInquiryRepositoryInterface $bookingInquiryRepository,
    ) {
    }

    #[Route('/booking-inquiry', name: 'submit', methods: ['POST'])]
    public function submit(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(ApiResponse::error('Некорректные данные запроса', 422), 422);
        }

        $propertyId = trim((string) ($data['propertyId'] ?? ''));
        $name = trim((string) ($data['name'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $notes = trim((string) ($data['notes'] ?? ''));
        $guests = isset($data['guests']) && $data['guests'] !== '' ? (int) $data['guests'] : null;
        $checkIn = trim((string) ($data['checkIn'] ?? ''));
        $checkOut = trim((string) ($data['checkOut'] ?? ''));

        if ($propertyId === '' || $name === '' || $phone === '') {
            return $this->json(
                ApiResponse::error('Заполните обязательные поля: имя и телефон', 422),
                422,
            );
        }

        if ($checkIn === '' || $checkOut === '') {
            return $this->json(
                ApiResponse::error('Укажите даты заезда и выезда', 422),
                422,
            );
        }

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return $this->json(
                ApiResponse::error('Неверный формат email', 422),
                422,
            );
        }

        if ($guests === null) {
            return $this->json(
                ApiResponse::error('Укажите количество гостей', 422),
                422,
            );
        }

        if ($guests < 1) {
            return $this->json(
                ApiResponse::error('Количество гостей должно быть не меньше 1', 422),
                422,
            );
        }

        $this->recaptcha->verify(
            (string) ($data['recaptchaToken'] ?? ''),
            $request->getClientIp(),
        );

        $command = new SubmitBookingInquiryCommand(
            propertyId: $propertyId,
            name: $name,
            phone: $phone,
            email: $email !== '' ? $email : null,
            guests: $guests,
            checkIn: $checkIn !== '' ? $checkIn : null,
            checkOut: $checkOut !== '' ? $checkOut : null,
            notes: $notes !== '' ? $notes : null,
            userId: $user !== null ? (string) $user->getId()->getValue() : null,
        );

        $result = $this->commandBus->dispatch($command);

        return $this->json(
            ApiResponse::success($result),
            Response::HTTP_CREATED,
        );
    }

    #[Route('/booking-inquiries', name: 'list', methods: ['GET'])]
    public function list(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $query = new GetMyBookingInquiriesQuery(
            ownerId: (string) $user->getId()->getValue(),
            page: $request->query->getInt('page', 1),
            limit: $request->query->getInt('limit', 20),
        );

        $result = $this->queryBus->ask($query);

        return $this->json(ApiResponse::paginated(
            $result['items'],
            $result['total'],
            $result['page'],
            $result['limit'],
        ));
    }

    #[Route('/booking-inquiries/unread-count', name: 'unread_count', methods: ['GET'])]
    public function unreadCount(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $count = $this->bookingInquiryRepository->countUnreadByOwnerId(
            (string) $user->getId()->getValue(),
        );

        return $this->json(ApiResponse::success(['unreadCount' => $count]));
    }

    #[Route('/booking-inquiries/mark-read', name: 'mark_read', methods: ['POST'])]
    public function markRead(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $command = new MarkBookingInquiriesReadCommand(
            ownerId: (string) $user->getId()->getValue(),
        );

        $this->commandBus->dispatch($command);

        return $this->json(ApiResponse::success(['message' => 'Отмечено как прочитанное']));
    }

    #[Route('/booking-inquiries/{id}/reply', name: 'reply', methods: ['POST'])]
    public function reply(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $data = json_decode($request->getContent(), true);
        $text = trim((string) (is_array($data) ? ($data['text'] ?? '') : ''));

        $result = $this->commandBus->dispatch(new ReplyToBookingInquiryCommand(
            inquiryId: $id,
            ownerId: (string) $user->getId()->getValue(),
            text: $text,
        ));

        return $this->json(ApiResponse::success($result));
    }

    #[Route('/booking-inquiries/{id}/accept', name: 'accept', methods: ['POST'])]
    public function accept(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $data = json_decode($request->getContent(), true);
        $bookCalendar = !is_array($data) || !array_key_exists('bookCalendar', $data)
            ? true
            : (bool) $data['bookCalendar'];

        $result = $this->commandBus->dispatch(new AcceptBookingInquiryCommand(
            inquiryId: $id,
            ownerId: (string) $user->getId()->getValue(),
            bookCalendar: $bookCalendar,
        ));

        return $this->json(ApiResponse::success($result));
    }

    #[Route('/booking-inquiries/{id}/decline', name: 'decline', methods: ['POST'])]
    public function decline(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $result = $this->commandBus->dispatch(new DeclineBookingInquiryCommand(
            inquiryId: $id,
            ownerId: (string) $user->getId()->getValue(),
        ));

        return $this->json(ApiResponse::success($result));
    }
}
