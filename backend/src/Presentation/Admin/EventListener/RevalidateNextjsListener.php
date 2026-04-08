<?php

declare(strict_types=1);

namespace App\Presentation\Admin\EventListener;

use App\Domain\Article\Entity\Article;
use App\Domain\StaticPage\Entity\StaticPage;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Event\EntityLifecycleEventInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityDeletedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityUpdatedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class RevalidateNextjsListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $revalidateUrl,
        private readonly string $revalidationSecret,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AfterEntityPersistedEvent::class => 'onEntityChange',
            AfterEntityUpdatedEvent::class => 'onEntityChange',
            AfterEntityDeletedEvent::class => 'onEntityChange',
        ];
    }

    public function onEntityChange(EntityLifecycleEventInterface $event): void
    {
        if ($this->revalidateUrl === '' || $this->revalidationSecret === '') {
            return;
        }

        $entity = $event->getEntityInstance();

        if ($entity instanceof Article) {
            $categorySlug = $entity->getCategory()?->getSlug();
            $this->notifyNextJs('article', $entity->getSlug()->getValue(), $categorySlug);

            return;
        }

        if ($entity instanceof StaticPage) {
            $this->notifyNextJs('static-page', $entity->getSlug()->getValue());
        }
    }

    private function notifyNextJs(string $type, string $slug, ?string $categorySlug = null): void
    {
        try {
            $payload = [
                'secret' => $this->revalidationSecret,
                'type' => $type,
                'slug' => $slug,
            ];
            if ($categorySlug !== null && $categorySlug !== '') {
                $payload['categorySlug'] = $categorySlug;
            }

            $response = $this->httpClient->request('POST', $this->revalidateUrl, [
                'timeout' => 10,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $status = $response->getStatusCode();
            if ($status < 200 || $status >= 300) {
                $this->logger->warning('Next.js revalidation returned error status', [
                    'type' => $type,
                    'slug' => $slug,
                    'status' => $status,
                    'body' => $response->getContent(false),
                ]);
            }
        } catch (\Throwable $e) {
            $this->logger->warning('Next.js revalidation request failed', [
                'type' => $type,
                'slug' => $slug,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
