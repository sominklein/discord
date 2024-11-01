<?php

namespace NotificationChannels\Discord;

use Exception;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Arr;
use NotificationChannels\Discord\Exceptions\CouldNotSendNotification;

class Discord
{
    /**
     * Discord API base URL.
     *
     * @var string
     */
    protected $baseUrl = 'https://discord.com/api';

    /**
     * API HTTP client.
     *
     * @var \GuzzleHttp\Client
     */
    protected $httpClient;

    /**
     * Discord API token.
     *
     * @var string
     */
    protected $token;

    /**
     * @param \GuzzleHttp\Client $http
     * @param string $token
     */
    public function __construct(HttpClient $http, $token)
    {
        $this->httpClient = $http;
        $this->token = $token;
    }

    /**
     * Send a message to a Discord channel.
     *
     * @param string $channel
     * @param array $data
     *
     * @return array
     */
    public function send($channel, array $data)
    {
        return $this->request('POST', 'channels/'.$channel.'/messages', $data);
    }
    
    /**
     * Send a message to a Discord channel.
     *
     * @param string $channel
     * @param string $messageId
     *
     * @return array
     */
    public function deleteMessage($channel, $messageId)
    {
        return $this->request('DELETE', 'channels/'.$channel.'/messages/'.$messageId, []);
    }

/**
     * Send a message to a Discord channel.
     *
     * @param string $channel
     *
     * @return array
     */
    public function getMessages( $channel )
    {
        return $this->request('GET', 'channels/'.$channel.'/messages', []);
    }
    /**
     * Get a private channel with another Discord user from their snowflake ID.
     *
     * @param string $guildId
     * @param string $userId
     *
     * @return bool
     */
    public function getHasJoinedGuild( $guildId, $userId)
    {
        try
        {
            $result = $this->request('GET', 'guilds/'.$guildId.'/members/'.$userId, []);
            return isset($result['user']);
        }
        catch (Exception $ex)
        {
            return false;
        }
        return true;
    }

    /**
     * Get a private channel with another Discord user from their snowflake ID.
     *
     * @param string $userId
     *
     * @return string
     */
    public function getPrivateChannel($userId)
    {
        return $this->request('POST', 'users/@me/channels', ['recipient_id' => $userId])['id'];
    }

    /**
     * Perform an HTTP request with the Discord API.
     *
     * @param string $verb
     * @param string $endpoint
     * @param array $data
     *
     * @return array
     *
     * @throws \NotificationChannels\Discord\Exceptions\CouldNotSendNotification
     */
    protected function request($verb, $endpoint, array $data)
    {
        $url = rtrim($this->baseUrl, '/').'/'.ltrim($endpoint, '/');

        try {
            $requestData = [
                'headers' => [
                    'Authorization' => 'Bot '.$this->token,
                ]];
            if ( $data != null )
            {
                $requestData['json'] = $data;
            }
            $response = $this->httpClient->request($verb, $url, $requestData);
        } catch (RequestException $exception) {
            if ($response = $exception->getResponse()) {
                throw CouldNotSendNotification::serviceRespondedWithAnHttpError($response, $response->getStatusCode(), $exception);
            }

            throw CouldNotSendNotification::serviceCommunicationError($exception);
        } catch (Exception $exception) {
            throw CouldNotSendNotification::serviceCommunicationError($exception);
        }

        $body = json_decode($response->getBody(), true);

        if (Arr::has($body, 'code') && Arr::get($body, 'code', 0) > 0) {
            throw CouldNotSendNotification::serviceRespondedWithAnApiError($body, $body['code']);
        }

        return $body;
    }
}
