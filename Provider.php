<?php

namespace SocialiteProviders\Simkl;

use Illuminate\Support\Arr;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'SIMKL';

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['public'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://simkl.com/oauth/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://api.simkl.com/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.simkl.com/users/settings', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'simkl-api-key' => $this->getConfig('client_id')
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map(
            [
                'id' => $user['account']['id'],
                'name' => $user['user']['name'],
                'avatar' => $user['user']['avatar']
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return Arr::add(
            parent::getTokenFields($code), 'grant_type', 'authorization_code'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'json' => $this->getTokenFields($code),
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    public function user()
    {
        if ($this->hasInvalidState()) {
            throw new InvalidStateException;
        }
    
        $response = $this->getAccessTokenResponse($this->getCode());

        $user = $this->mapUserToObject($this->getUserByToken(
            $token = Arr::get($response, 'access_token')
        ));

        return $user->setToken($token);        
    }

    /**
     * Syncs Simkl's Watch History
     */
    protected function syncUserHistory(string $token, array $data)
    {
        $response = $this->getHttpClient()->post(
            'https://api.simkl.com/sync/history', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'simkl-api-key' => $this->getConfig('client_id')
            ],
            'json' => $data
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }    
}
