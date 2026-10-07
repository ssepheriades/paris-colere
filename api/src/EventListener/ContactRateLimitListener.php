<?php

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final class ContactRateLimitListener
{
    public function __construct(
        #[Autowire(service: 'limiter.contact_api')]
        private readonly RateLimiterFactory $limiter,
    ) {
    }

    #[AsEventListener(event: KernelEvents::REQUEST, priority: 10)]
    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if ('POST' !== $request->getMethod() || '/api/contacts' !== $request->getPathInfo()) {
            return;
        }

        $limit = $this->limiter->create($request->getClientIp() ?? 'unknown')->consume();
        if ($limit->isAccepted()) {
            return;
        }

        $retryAfter = $limit->getRetryAfter()->getTimestamp() - time();
        $event->setResponse(new JsonResponse(
            ['detail' => 'Trop de messages envoyés. Réessayez plus tard.'],
            Response::HTTP_TOO_MANY_REQUESTS,
            ['Retry-After' => (string) max(1, $retryAfter)],
        ));
    }
}
