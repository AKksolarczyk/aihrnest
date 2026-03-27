<?php

declare(strict_types=1);

namespace App\Workspace\Infrastructure\Hrnest;

use JsonException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class HrnestClient
{
    public function __construct(
        #[Autowire('%env(HRNEST_API_BASE_URL)%')]
        private readonly string $baseUrl,
        #[Autowire('%env(HRNEST_API_PEOPLE_PATH)%')]
        private readonly string $peoplePath,
        #[Autowire('%env(HRNEST_API_LOGIN)%')]
        private readonly string $login,
        #[Autowire('%env(HRNEST_API_KEY)%')]
        private readonly string $apiKey,
        #[Autowire('%env(int:HRNEST_API_TIMEOUT)%')]
        private readonly int $timeout,
        #[Autowire('%env(HRNEST_API_AUTH_MODE)%')]
        private readonly string $authMode,
    ) {
    }

    /**
     * @return list<HrnestPerson>
     */
    public function fetchPeople(?string $pathOverride = null, ?string $deskField = null): array
    {
        if ($this->login === '' || $this->apiKey === '') {
            throw new HrnestApiException('Brakuje konfiguracji HRNEST_API_LOGIN lub HRNEST_API_KEY.');
        }

        $path = $pathOverride ?? $this->peoplePath;

        if ($path === '') {
            throw new HrnestApiException('Brakuje sciezki endpointu HRNEST_API_PEOPLE_PATH.');
        }

        return $this->mapPeople($this->request($path), $deskField);
    }

    /**
     * @return list<HrnestPerson>
     */
    public function searchPeopleByEmail(string $email, ?string $deskField = null): array
    {
        if ($this->login === '' || $this->apiKey === '') {
            throw new HrnestApiException('Brakuje konfiguracji HRNEST_API_LOGIN lub HRNEST_API_KEY.');
        }

        $normalizedEmail = mb_strtolower(trim($email));

        if ($normalizedEmail === '') {
            throw new HrnestApiException('Brakuje adresu email do wyszukania w HRnest.');
        }

        if ($this->peoplePath === '') {
            throw new HrnestApiException('Brakuje sciezki endpointu HRNEST_API_PEOPLE_PATH.');
        }

        return $this->mapPeople($this->request($this->buildSearchByEmailPath($normalizedEmail)), $deskField);
    }

    /**
     * @return array<int, mixed>
     */
    private function request(string $path): array
    {
        $authMode = strtolower(trim($this->authMode));
        $url = $this->buildUrl($path, $authMode);
        $headers = [
            'Accept: application/json',
            'User-Agent: AIhrnest2/1.0',
        ];

        if (in_array($authMode, ['all', 'basic'], true)) {
            $headers[] = 'Authorization: Basic '.base64_encode($this->login.':'.$this->apiKey);
        }

        if (in_array($authMode, ['all', 'headers'], true)) {
            $headers[] = 'X-HRNEST-LOGIN: '.$this->login;
            $headers[] = 'X-HRNEST-KEY: '.$this->apiKey;
            $headers[] = 'login: '.$this->login;
            $headers[] = 'key: '.$this->apiKey;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => $this->timeout,
                'ignore_errors' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        $statusCode = $this->resolveStatusCode($http_response_header ?? []);

        if ($body === false) {
            throw new HrnestApiException(sprintf('Nie udalo sie pobrac danych z HRnest: %s', $url));
        }

        if ($statusCode >= 400) {
            throw new HrnestApiException(sprintf('HRnest zwrocil blad HTTP %d dla %s.', $statusCode, $url));
        }

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new HrnestApiException('Odpowiedz HRnest nie jest poprawnym JSON-em.', 0, $exception);
        }

        if (!is_array($decoded)) {
            throw new HrnestApiException('Odpowiedz HRnest ma nieoczekiwany format.');
        }

        return $decoded;
    }

    private function buildUrl(string $path, string $authMode): string
    {
        $url = rtrim($this->baseUrl, '/').'/'.ltrim($path, '/');

        if ($authMode !== 'query') {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.http_build_query([
            'login' => $this->login,
            'key' => $this->apiKey,
        ]);
    }

    private function buildSearchByEmailPath(string $email): string
    {
        $path = trim($this->peoplePath);

        if (str_contains($path, 'search.email=')) {
            return preg_replace('/search\.email=[^&]*/', 'search.email='.rawurlencode($email), $path) ?? $path;
        }

        if (str_contains($path, 'search.email')) {
            return preg_replace('/search\.email\b/', 'search.email='.rawurlencode($email), $path, 1) ?? $path;
        }

        $separator = str_contains($path, '?') ? '&' : '?';

        return $path.$separator.'search.email='.rawurlencode($email);
    }

    /**
     * @return list<HrnestPerson>
     */
    private function mapPeople(array $response, ?string $deskField = null): array
    {
        $records = $this->extractRecords($response);
        $people = [];

        foreach ($records as $index => $record) {
            if (!is_array($record)) {
                continue;
            }

            $email = $this->firstString($record, ['email', 'work_email', 'workEmail', 'business_email', 'businessEmail']);
            $name = $this->resolveName($record);

            if ($email === null || $name === null) {
                continue;
            }

            $people[] = new HrnestPerson(
                $this->resolveExternalId($record, $index),
                $name,
                $email,
                $this->firstString($record, ['team', 'team_name', 'teamName', 'department', 'department_name', 'departmentName']) ?? 'HRnest',
                $this->resolveDeskId($record, $deskField),
            );
        }

        return $people;
    }

    /**
     * @param array<int, mixed> $response
     * @return array<int, mixed>
     */
    private function extractRecords(array $response): array
    {
        if (array_is_list($response)) {
            return $response;
        }

        foreach (['data', 'employees', 'users', 'items', 'results'] as $key) {
            $candidate = $response[$key] ?? null;

            if (is_array($candidate) && array_is_list($candidate)) {
                return $candidate;
            }
        }

        foreach (['data', 'employee', 'user', 'item', 'result'] as $key) {
            $candidate = $response[$key] ?? null;

            if (is_array($candidate) && !array_is_list($candidate)) {
                return [$candidate];
            }
        }

        if ($this->looksLikePersonRecord($response)) {
            return [$response];
        }

        return [];
    }

    /**
     * @param array<string, mixed> $record
     */
    private function looksLikePersonRecord(array $record): bool
    {
        return $this->resolveName($record) !== null
            || $this->firstString($record, ['email', 'work_email', 'workEmail', 'business_email', 'businessEmail']) !== null;
    }

    /**
     * @param array<string, mixed> $record
     */
    private function resolveExternalId(array $record, int $index): string
    {
        return $this->firstString($record, ['id', 'employee_id', 'employeeId', 'user_id', 'userId']) ?? sprintf('hrnest-%d', $index + 1);
    }

    /**
     * @param array<string, mixed> $record
     */
    private function resolveName(array $record): ?string
    {
        $name = $this->firstString($record, ['name', 'full_name', 'fullName']);

        if ($name !== null) {
            return $name;
        }

        $firstName = $this->firstString($record, ['first_name', 'firstName']) ?? '';
        $lastName = $this->firstString($record, ['last_name', 'lastName']) ?? '';
        $fullName = trim($firstName.' '.$lastName);

        return $fullName !== '' ? $fullName : null;
    }

    /**
     * @param array<string, mixed> $record
     */
    private function resolveDeskId(array $record, ?string $deskField): ?string
    {
        if ($deskField === null || trim($deskField) === '') {
            return null;
        }

        $value = $this->readDotPath($record, $deskField);

        if (!is_scalar($value)) {
            return null;
        }

        $deskId = trim((string) $value);

        return $deskId !== '' ? $deskId : null;
    }

    /**
     * @param array<string, mixed> $record
     * @param list<string> $keys
     */
    private function firstString(array $record, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $record[$key] ?? null;

            if (!is_scalar($value)) {
                continue;
            }

            $normalized = trim((string) $value);

            if ($normalized !== '') {
                return $normalized;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $record
     */
    private function readDotPath(array $record, string $path): mixed
    {
        $current = $record;

        foreach (explode('.', $path) as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }

            $current = $current[$segment];
        }

        return $current;
    }

    /**
     * @param list<string> $responseHeaders
     */
    private function resolveStatusCode(array $responseHeaders): int
    {
        foreach ($responseHeaders as $headerLine) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $headerLine, $matches) === 1) {
                return (int) $matches[1];
            }
        }

        return 0;
    }
}
