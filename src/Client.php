<?php declare(strict_types = 1);

namespace EcomailRaynet;

use EcomailRaynet\Exception\EcomailRaynetAnotherError;
use EcomailRaynet\Exception\EcomailRaynetInvalidAuthorization;
use EcomailRaynet\Exception\EcomailRaynetNoEvidenceResult;
use EcomailRaynet\Exception\EcomailRaynetRequestError;
use EcomailRaynet\Exception\EcomailRaynetSaveFailed;
use EcomailRaynet\Exception\EcomailRaynetNotFound;
use EcomailRaynet\Exception\EcomailRaynetInstanceNotFound;
use EcomailRaynet\Exception\EcomailRaynetRequestLimitReached;

class Client
{

    /**
     * REST API user
     *
     * @var string
     */
    private $username;

    /**
     * REST API key
     *
     * @var string
     */
    private $apiKey;

    /**
     * REST API instance name
     *
     * @var string
     */
    private $instanceName;

    public function __construct(string $username, string $apiKey, string $instanceName)
    {
        $this->username = $username;
        $this->apiKey = $apiKey;
        $this->instanceName = $instanceName;
    }

    /**
     * @param \EcomailRaynet\Http\Method $httpMethod
     * @param string $url
     * @param mixed[] $postFields
     * @param string[] $queryParameters
     * @return mixed[]
     * @throws \EcomailRaynet\Exception\EcomailRaynetAnotherError
     * @throws \EcomailRaynet\Exception\EcomailRaynetNotFound
     * @throws \EcomailRaynet\Exception\EcomailRaynetInvalidAuthorization
     * @throws \EcomailRaynet\Exception\EcomailRaynetRequestError
     */
    public function makeRequest(string $httpMethod, string $url, array $postFields = [], array $queryParameters = []): array
    {
        /** @var resource $ch */
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        
        curl_setopt($ch, CURLOPT_HTTPAUTH, TRUE);
		curl_setopt($ch, CURLOPT_USERPWD, sprintf('%s:%s', $this->username, $this->apiKey));
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'X-Instance-Name: ' . $this->instanceName
		]);

        curl_setopt($ch, CURLOPT_URL, 'https://app.raynet.cz/' . $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $httpMethod);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Ecomail.cz Raynet client (https://github.com/Ecomailcz/raynet-client)');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); 
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        if (count($postFields) !== 0) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postFields));
        }

        if (count($queryParameters) !== 0) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($queryParameters));
        }

        $output = curl_exec($ch);
        $result = json_decode($output, true);

        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200 && curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 201) {

            if (curl_getinfo($ch, CURLINFO_HTTP_CODE) === 404) {
                if (isset($result['translatedMessage']) && strpos($result['translatedMessage'], 'Instance not found') === 0) {
                    throw new EcomailRaynetInstanceNotFound();
                }
                throw new EcomailRaynetNotFound();
            }
            // Check authorization
            elseif (curl_getinfo($ch, CURLINFO_HTTP_CODE) === 401) {
                throw new EcomailRaynetInvalidAuthorization();
            } elseif (curl_getinfo($ch, CURLINFO_HTTP_CODE) === 400) {
		if(!isset($result['success'])) {
		    throw new EcomailRaynetRequestError($output);
		} elseif ($result['success'] === 'false') {
                    foreach ($result['results'] as $response) {
                        foreach ($response['errors'] as $error) {
                            throw new EcomailRaynetRequestError($error['message']);
                        }
                    }

                }

            }
        }

        if (!$result) {
            return [];
        }

        if(isset($result['type']) && $result['type'] === 'RequestLimitReached') {
            throw new EcomailRaynetRequestLimitReached();
        }

        if (array_key_exists('success', $result) && !$result['success']) {
            throw new EcomailRaynetAnotherError($result);
        }

        return $result;
    }

}
