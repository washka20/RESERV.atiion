<?php

declare(strict_types=1);

namespace App\Modules\Identity\Interface\Api\Controller;

use App\Modules\Identity\Application\Command\RegisterUser\RegisterUserCommand;
use App\Modules\Identity\Application\Command\RegisterUser\RegisterUserHandler;
use App\Modules\Identity\Application\Query\GetUserProfile\GetUserProfileHandler;
use App\Modules\Identity\Application\Query\GetUserProfile\GetUserProfileQuery;
use App\Modules\Identity\Application\Service\AuthService;
use App\Modules\Identity\Domain\Exception\InvalidCredentialsException;
use App\Modules\Identity\Interface\Api\Request\LoginRequest;
use App\Modules\Identity\Interface\Api\Request\RefreshRequest;
use App\Modules\Identity\Interface\Api\Request\RegisterRequest;
use App\Modules\Identity\Interface\Api\Resource\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuthController
{
    public function __construct(
        private readonly RegisterUserHandler $register,
        private readonly AuthService $auth,
        private readonly GetUserProfileHandler $profile,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $userId = $this->register->handle(new RegisterUserCommand(
            email: (string) $request->string('email'),
            plaintextPassword: (string) $request->string('password'),
            firstName: (string) $request->string('first_name'),
            lastName: (string) $request->string('last_name'),
            middleName: $request->input('middle_name'),
        ));

        $pair = $this->auth->issueForUserId($userId);
        $dto = $this->profile->handle(new GetUserProfileQuery($userId->toString()));

        return $this->envelope([
            'user' => $dto !== null ? UserResource::fromDTO($dto) : null,
            'access_token' => $pair->accessToken,
            'refresh_token' => $pair->refreshToken,
            'expires_in' => $pair->expiresIn,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $pair = $this->auth->login(
                (string) $request->string('email'),
                (string) $request->string('password'),
            );
        } catch (InvalidCredentialsException) {
            return $this->error('INVALID_CREDENTIALS', 'Invalid email or password', 401);
        }

        return $this->envelope([
            'access_token' => $pair->accessToken,
            'refresh_token' => $pair->refreshToken,
            'expires_in' => $pair->expiresIn,
            'token_type' => 'Bearer',
        ]);
    }

    public function refresh(RefreshRequest $request): JsonResponse
    {
        try {
            $pair = $this->auth->refresh((string) $request->string('refresh_token'));
        } catch (InvalidCredentialsException) {
            return $this->error('INVALID_REFRESH', 'Invalid or expired refresh token', 401);
        }

        return $this->envelope([
            'access_token' => $pair->accessToken,
            'refresh_token' => $pair->refreshToken,
            'expires_in' => $pair->expiresIn,
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->input('refresh_token');
        if (is_string($token) && $token !== '') {
            $this->auth->logout($token);
        }

        return response()->json(null, 204);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return $this->error('UNAUTHORIZED', 'Unauthorized', 401);
        }

        $dto = $this->profile->handle(new GetUserProfileQuery((string) $user->getAuthIdentifier()));
        if ($dto === null) {
            return $this->error('NOT_FOUND', 'User not found', 404);
        }

        return $this->envelope(UserResource::fromDTO($dto));
    }

    /**
     * @param  mixed  $data
     */
    private function envelope($data, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'error' => null,
            'meta' => null,
        ], $status);
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function error(string $code, string $message, int $status, array $details = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details !== [] ? $details : null,
            ],
            'meta' => null,
        ], $status);
    }
}
