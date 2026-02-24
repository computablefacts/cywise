<?php

namespace App\Http\Procedures;

use App\Http\Requests\JsonRpcRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Sajya\Server\Procedure;

class IdoxProcedure extends Procedure
{
    public static string $name = 'idox';

    #[RpcMethod(
        description: "List the user's workspaces.",
        params: [],
        result: [
            'workspaces' => 'A list of workspaces.',
        ],
        ai_examples: [
            "if the request is 'list IDOX workspaces', the input should be {}",
            "if the request is 'list FusionLive workspaces', the input should be {}",
        ],
        ai_result: "
            J'ai trouvé {{ count(\$result['workspaces']) }} espaces de travail :
            @foreach(\$result['workspaces'] as \$w)
            - L'espace de travail '{{ \$w['name'] }}' a été créé le {{ \$w['creation_date'] }} par {{ \$w['created_by'] }} et a pour identifiant {{ \$w['id'] }}. Il est associé à la société {{ \$w['company'] }} et a pour statut '{{ \$w['status'] }}'.
            @endforeach
        ",
    )]
    public function workspaces(JsonRpcRequest $request): array
    {
        $token = $this->token($username, $password);
        $result = $this->post('/pws/users', "<listworkspaces mode=\"detail\"><authentication token=\"{$token}\"/></listworkspaces>");

        if ($result['code'] != 0) {
            return [
                'workspaces' => [],
            ];
        }

        $el = new IdoxXmlElement($result['data'], [
            'workspaces' => [
                'xpath' => '/status/workspaces/workspace',
                'many' => true
            ],
        ]);

        return [
            'workspaces' => array_map(function (\SimpleXMLElement $w) {
                return [
                    'id' => (int)(IdoxXmlElement::attr($w, 'id') ?? 0),
                    'company_id' => (int)(IdoxXmlElement::attr($w, 'company_id') ?? 0),
                    'company' => (string)(IdoxXmlElement::attr($w, 'company') ?? ''),
                    'name' => (string)(IdoxXmlElement::attr($w, 'name') ?? ''),
                    'status' => (string)(IdoxXmlElement::attr($w, 'status') ?? ''),
                    'uri' => (string)(IdoxXmlElement::attr($w, 'uri') ?? ''),
                    'creation_date' => (string)(IdoxXmlElement::attr($w, 'creationdate') ?? ''),
                    'created_by' => (string)(IdoxXmlElement::attr($w, 'initiator') ?? ''),
                    'is_default' => (IdoxXmlElement::attr($w, 'is_default') === 'true'),
                    'is_inbox_enabled' => (IdoxXmlElement::attr($w, 'isInboxEnabled') === 'true'),
                    'is_dcl_enabled' => (IdoxXmlElement::attr($w, 'isDLCEnabled') === 'true'),
                    'is_br_enabled' => (IdoxXmlElement::attr($w, 'isBREnabled') === 'true'),
                ];
            }, $el->workspaces),
        ];
    }

    private function token(string $username, string $password): string
    {
        $result = $this->authenticate($username, $password);
        return $result ? $result->session : '';
    }

    private function authenticate(string $username, string $password): ?IdoxXmlElement
    {
        $result = $this->post('/pws/users', "<authenticate login=\"{$username}\" password=\"{$password}\" />");
        if ($result['code'] === 0) {
            return new IdoxXmlElement($result['data'], [
                'userid' => '/status/result/userid',
                'partyid' => '/status/result/partyid',
                'companyid' => '/status/result/companyid',
                'session' => '/status/result/session',
                'languagecode' => '/status/result/languagecode',
                'language' => '/status/result/language',
                'dateformat' => '/status/result/dateformat',
                'datetimeformat' => '/status/result/datetimeformat',
                'firstname' => '/status/result/firstname',
                'lastname' => '/status/result/lastname',
            ]);
        }
        return null;
    }

    private function post(string $endpoint, string $payload): array
    {
        try {
            $response = Http::withBody($payload, 'text/plain;charset=UTF-8')->post("https://uk.fusion.live{$endpoint}");
        } catch (ConnectionException $e) {
            Log::error($e->getMessage());
            return [];
        }

        $xml = simplexml_load_string($response->getBody()->getContents());
        // Log::debug($xml->asXML());

        if (((string)$xml['state'] ?? '') === 'failure') {
            return [
                'code' => (int)($xml->xpath('/status/result/@code')[0] ?? -1),
                'data' => 'XML parsing error #1 : ' . $xml->asXML(),
            ];
        }
        if (((string)$xml['state'] ?? '') === 'success') {
            return [
                'code' => 0,
                'data' => $xml,
            ];
        }
        return [
            'code' => -1,
            'data' => 'XML parsing error #2 : ' . $xml->asXML(),
        ];
    }
}
