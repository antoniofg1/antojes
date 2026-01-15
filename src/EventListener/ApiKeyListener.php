<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event Listener para validar API Key
 * 
 * Valida que todas las peticiones a /api/* incluyan el header X-API-KEY
 * excepto las rutas públicas como /api/login
 * 
 * La API Key se configura en .env como APP_API_KEY
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 10)]
class ApiKeyListener
{
    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Solo validar rutas que empiecen con /api/
        if (!str_starts_with($path, '/api/')) {
            return;
        }

        // Obtener API Key del header
        $providedKey = $request->headers->get('X-API-KEY');

        // Validar API Key
        if (!$providedKey || $providedKey !== $this->apiKey) {
            $response = new JsonResponse([
                'error' => 'Invalid or missing API Key',
                'message' => 'Please provide a valid X-API-KEY header'
            ], 401);

            $event->setResponse($response);
            return;
        }
    }
}
