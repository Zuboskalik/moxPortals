<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

final class PortalActionException extends RuntimeException
{
    public function __construct(
        private readonly string $errorCode,
        string $message,
        private readonly array $context = [],
    ) {
        parent::__construct($message);
    }

    public static function portalAlreadyClosed(string $portalId): self
    {
        return new self(
            'PORTAL_ALREADY_CLOSED',
            'Портал уже закрыт и не может быть изменён.',
            ['portal_id' => $portalId, 'current_status' => 'closed'],
        );
    }

    public static function riskTooHighForObserver(string $portalId, int $riskScore): self
    {
        return new self(
            'RISK_TOO_HIGH_FOR_OBSERVER',
            'Риск CRITICAL слишком высок для отправки наблюдателя.',
            ['portal_id' => $portalId, 'risk_score' => $riskScore, 'risk_level' => 'CRITICAL'],
        );
    }

    public static function evacuationRequired(string $portalId, int $creaturesCount): self
    {
        return new self(
            'EVACUATION_REQUIRED',
            "В портале остались существа ({$creaturesCount}). Подтвердите принудительную эвакуацию (force_evacuate=true).",
            ['portal_id' => $portalId, 'creatures_count' => $creaturesCount],
        );
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function context(): array
    {
        return $this->context;
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
            'context' => $this->context,
        ], 422);
    }

    /**
     * Нарушение бизнес-правила — ожидаемый исход запроса, а не сбой приложения,
     * поэтому не должно засорять логи на уровне ERROR. Laravel вызывает report()
     * и пропускает стандартное логирование, если результат строго не равен false
     * (см. Illuminate\Foundation\Exceptions\Handler::reportThrowable()).
     */
    public function report(): bool
    {
        return true;
    }
}
