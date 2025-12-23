<?php

namespace Imarc\Millyard\Services;

/**
 * Service for tracking UTM parameters in sessions or cookies.
 *
 * This service automatically uses sessions if enabled, otherwise falls back to cookies.
 * UTM parameters are captured from URL query strings and persisted for the user's visit.
 */
class UtmTracker
{
    /**
     * UTM parameters to track
     *
     * @var array<string>
     */
    protected array $utmParameters;

    /**
     * Cookie name for storing UTM parameters
     */
    private const COOKIE_NAME = 'utm_params';

    /**
     * Cookie expiration time (30 days)
     */
    private const COOKIE_EXPIRY = 30 * DAY_IN_SECONDS;

    /**
     * Initialize the UtmTracker service
     *
     * @param array<string>|null $utmParameters Optional custom list of UTM parameters to track.
     *                                          If null, uses the default standard UTM parameters.
     */
    public function __construct(?array $utmParameters = null)
    {
        $this->utmParameters = $utmParameters ?? [
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_term',
            'utm_content',
        ];
    }

    /**
     * Check if sessions are enabled and active
     */
    private function isSessionAvailable(): bool
    {
        return config('sessions.enabled') && session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Capture UTM parameters from the URL and store them
     *
     * @return bool True if UTM parameters were found and stored, false otherwise
     */
    public function capture(): bool
    {
        $hasUtmParams = false;
        $utmData = [];

        // Check each UTM parameter
        foreach ($this->utmParameters as $param) {
            if (isset($_GET[$param]) && ! empty($_GET[$param])) {
                $value = sanitize_text_field($_GET[$param]);
                if (! empty($value)) {
                    $utmData[$param] = $value;
                    $hasUtmParams = true;
                }
            }
        }

        // If we found UTM parameters, store them
        if ($hasUtmParams) {
            $utmData['captured_at'] = time();
            $this->store($utmData);

            return true;
        }

        return false;
    }

    /**
     * Store UTM data in session or cookie
     *
     * @param array $utmData The UTM data to store
     */
    private function store(array $utmData): void
    {
        if ($this->isSessionAvailable()) {
            // Store in session
            if (! isset($_SESSION['utm'])) {
                $_SESSION['utm'] = [];
            }
            $_SESSION['utm'] = array_merge($_SESSION['utm'], $utmData);
        } else {
            // Store in cookie
            $cookieValue = json_encode($utmData);
            setcookie(
                self::COOKIE_NAME,
                $cookieValue,
                [
                    'expires' => time() + self::COOKIE_EXPIRY,
                    'path' => config('sessions.path', '/'),
                    'domain' => config('sessions.domain', ''),
                    'secure' => config('sessions.secure', false),
                    'httponly' => config('sessions.httponly', true),
                    'samesite' => config('sessions.samesite', 'Lax'),
                ]
            );
            // Also set in $_COOKIE for immediate access
            $_COOKIE[self::COOKIE_NAME] = $cookieValue;
        }
    }

    /**
     * Get UTM parameters from session or cookie
     *
     * @param string|null $key Optional specific UTM parameter key (e.g., 'utm_source', 'utm_campaign').
     *                         If null, returns all UTM parameters.
     * @param mixed $default Default value to return if the key is not found.
     * @return mixed The UTM parameter value(s) or default if not found.
     */
    public function get(?string $key = null, $default = null)
    {
        $utmData = $this->retrieve();

        if (empty($utmData)) {
            return $default;
        }

        if ($key === null) {
            // Return all UTM parameters (excluding metadata like 'captured_at')
            $utmParams = $utmData;
            unset($utmParams['captured_at']);

            return $utmParams;
        }

        return $utmData[$key] ?? $default;
    }

    /**
     * Retrieve UTM data from session or cookie
     *
     * @return array The UTM data array, or empty array if not found
     */
    private function retrieve(): array
    {
        if ($this->isSessionAvailable()) {
            return $_SESSION['utm'] ?? [];
        }

        // Retrieve from cookie
        if (isset($_COOKIE[self::COOKIE_NAME])) {
            $decoded = json_decode(stripslashes($_COOKIE[self::COOKIE_NAME]), true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * Clear stored UTM parameters
     */
    public function clear(): void
    {
        if ($this->isSessionAvailable()) {
            unset($_SESSION['utm']);
        } else {
            // Clear cookie
            setcookie(
                self::COOKIE_NAME,
                '',
                [
                    'expires' => time() - 3600,
                    'path' => config('sessions.path', '/'),
                    'domain' => config('sessions.domain', ''),
                    'secure' => config('sessions.secure', false),
                    'httponly' => config('sessions.httponly', true),
                    'samesite' => config('sessions.samesite', 'Lax'),
                ]
            );
            unset($_COOKIE[self::COOKIE_NAME]);
        }
    }

    /**
     * Get all available UTM parameter keys
     *
     * @return array<string> Array of UTM parameter names
     */
    public function getParameterKeys(): array
    {
        return $this->utmParameters;
    }

    /**
     * Add additional UTM parameters to track
     *
     * @param string|array<string> $parameters UTM parameter name(s) to add
     * @return self
     */
    public function addParameters(string|array $parameters): self
    {
        $paramsToAdd = is_array($parameters) ? $parameters : [$parameters];
        $this->utmParameters = array_unique(array_merge($this->utmParameters, $paramsToAdd));

        return $this;
    }

    /**
     * Set the UTM parameters to track (replaces existing list)
     *
     * @param array<string> $parameters UTM parameter names to track
     * @return self
     */
    public function setParameters(array $parameters): self
    {
        $this->utmParameters = array_unique($parameters);

        return $this;
    }
}
