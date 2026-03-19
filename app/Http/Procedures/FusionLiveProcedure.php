<?php

namespace App\Http\Procedures;

use App\Http\Requests\JsonRpcRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Sajya\Server\Procedure;

class FusionLiveProcedure extends Procedure
{
    public static string $name = 'fusionlive';

    #[RpcMethod(
        description: "Retrieve a list of the user's FusionLive workspaces.",
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
        $user = $request->user();
        $username = $user?->fusionlive_username ?? '';
        $password = $user?->fusionlive_password ?? '';

        if (empty($username) || empty($password)) {
            throw new \Exception('Missing FusionLive credentials.');
        }

        $token = $this->token($username, $password);
        $result = $this->post('/pws/users', "<listworkspaces mode=\"detail\"><authentication token=\"{$token}\"/></listworkspaces>");

        if ($result['code'] != 0) {
            return [
                'workspaces' => [],
            ];
        }

        $el = new FusionLiveXmlElement($result['data'], [
            'workspaces' => [
                'xpath' => '/status/workspaces/workspace',
                'many' => true
            ],
        ]);

        return [
            'workspaces' => array_map(function (\SimpleXMLElement $w) {
                return [
                    'id' => (int)(FusionLiveXmlElement::attr($w, 'id') ?? 0),
                    'company_id' => (int)(FusionLiveXmlElement::attr($w, 'company_id') ?? 0),
                    'company' => (string)(FusionLiveXmlElement::attr($w, 'company') ?? ''),
                    'name' => (string)(FusionLiveXmlElement::attr($w, 'name') ?? ''),
                    'status' => (string)(FusionLiveXmlElement::attr($w, 'status') ?? ''),
                    'uri' => (string)(FusionLiveXmlElement::attr($w, 'uri') ?? ''),
                    'creation_date' => (string)(FusionLiveXmlElement::attr($w, 'creationdate') ?? ''),
                    'created_by' => (string)(FusionLiveXmlElement::attr($w, 'initiator') ?? ''),
                    'is_default' => (FusionLiveXmlElement::attr($w, 'is_default') === 'true'),
                    'is_inbox_enabled' => (FusionLiveXmlElement::attr($w, 'isInboxEnabled') === 'true'),
                    'is_dcl_enabled' => (FusionLiveXmlElement::attr($w, 'isDLCEnabled') === 'true'),
                    'is_br_enabled' => (FusionLiveXmlElement::attr($w, 'isBREnabled') === 'true'),
                ];
            }, $el->workspaces),
        ];
    }

    #[RpcMethod(
        description: "List all folders and documents for a given FusionLive workspace.",
        params: [
            'workspace_id' => 'The workspace ID. (integer|required|min:0)',
            'status' => 'An optional document status to filter by. (string|nullable|min:1|max:50)'
        ],
        result: [
            'documents' => 'A list of documents.',
        ],
        ai_examples: [
            "if the request is 'list files in 1458', the input should be {\"workspace_id\":1458}",
            "if the request is 'list files whose status is 'MAJ - Pour mise à jour' in 1458', the input should be {\"workspace_id\":1458,\"status\":\"MAJ - Pour mise à jour\"}",
        ],
        ai_result: "
I found {{ count(\$result['documents']) }} folders and {{ array_sum(array_column(\$result['documents'], 'count')) }} documents.

@foreach(\$result['documents'] as \$folder)

# {{ \$folder['location'] }} ({{ \$folder['count'] }} documents)

@foreach(\$folder['documents'] as \$doc)

## {{ \$doc['file']['name'] ?? \$doc['title'] }}

- **Company Name.** {{ \$doc['company_name'] }}
- **Uploaded At.** {{ \$doc['upload_date'] }}
- **Title.** {{ \$doc['title'] }}
- **Size.** {{ \$doc['size'] }} bytes
- **Reference.** {{ \$doc['reference'] }}
- **Status.** {{ \$doc['status'] }}
- **Revision.** {{ \$doc['revision'] }}
- **Locked.** {{ \$doc['is_locked'] ? 'true' : 'false' }}
- **Link.** {{ \$doc['uri'] }}

@endforeach
@endforeach
        ",
    )]
    public function documents(JsonRpcRequest $request): array
    {
        $params = $request->validate([
            'workspace_id' => 'integer|required|min:0',
            'status' => 'string|nullable|min:1|max:50',
        ]);
        $workspaceId = $params['workspace_id'];
        $status = $params['status'] ?? null;
        $user = $request->user();
        $username = $user?->fusionlive_username ?? '';
        $password = $user?->fusionlive_password ?? '';

        if (empty($username) || empty($password)) {
            throw new \Exception('Missing FusionLive credentials.');
        }

        $token = $this->token($username, $password);
        $documents = collect($this->crawlFolders($token, $workspaceId, 0))
            ->filter(fn(array $location) => ($location['count'] ?? 0) > 0)
            ->map(function (array $location) use ($status) {
                if ($status != null) {
                    $location['documents'] = array_filter($location['documents'], fn(array $doc) => $doc['status'] === $status);
                }
                return $location;
            })
            ->values()
            ->toArray();

        return [
            'documents' => $documents,
        ];
    }

    private function crawlFolders(string $token, int $workspaceId, int $folderId): array
    {
        $documents = [$this->loadDocuments($token, $workspaceId, $folderId)];
        $folders = $this->loadFolders($token, $workspaceId, $folderId);

        foreach ($folders as $folder) {
            $subDocuments = $this->crawlFolders($token, $workspaceId, (int)$folder['id']);
            $documents = array_merge($documents, $subDocuments);
        }
        return $documents;
    }

    private function loadFolders(string $token, int $workspaceId, int $folderId): array
    {
        $payload = $folderId > 0 ?
            "<listfolders mode=\"detail\"><authentication token=\"{$token}\"/><workspace id=\"{$workspaceId}\"/><parent id=\"{$folderId}\"/></listfolders>" :
            "<listfolders mode=\"detail\"><authentication token=\"{$token}\"/><workspace id=\"{$workspaceId}\"/></listfolders>";
        $result = $this->post('/pws/folders', $payload);

        if ($result['code'] != 0) {
            return [];
        }

        $el = new FusionLiveXmlElement($result['data'], [
            'folders' => [
                'xpath' => '/status/folders/folder',
                'many' => true
            ],
        ]);

        return array_map(function (\SimpleXMLElement $f) {
            return [
                'id' => (int)FusionLiveXmlElement::attr($f, 'id'),
                'name' => (string)FusionLiveXmlElement::attr($f, 'name'),
            ];
        }, $el->folders);
    }

    private function loadDocuments(string $token, int $workspaceId, int $folderId): array
    {
        $payload = "<listdocuments mode=\"detail\"><authentication token=\"{$token}\"/><workspace id=\"{$workspaceId}\"/><parent id=\"{$folderId}\"/></listdocuments>";
        $result = $this->post('/pws/folders', $payload);

        if ($result['code'] != 0) {
            return [];
        }

        $el = new FusionLiveXmlElement($result['data'], [
            'location' => '/status/documents/@location',
            'count' => '/status/documents/@count',
            'documents' => [
                'xpath' => '/status/documents/document',
                'many' => true
            ],
        ]);

        $location = (string)$el->location;
        $count = (int)$el->count;

        return [
            'id' => $folderId,
            'location' => $location,
            'count' => $count,
            'documents' => array_map(function (\SimpleXMLElement $d) use ($token, $workspaceId) {

                $attachments = [];
                $docId = (int)FusionLiveXmlElement::attr($d, 'id');
                $hasAttachments = (FusionLiveXmlElement::attr($d, 'hasattachment') === 'true');

                if ($hasAttachments) {
                    $attachments = $this->loadAttachments($token, $workspaceId, $docId);
                }
                return [
                    'id' => $docId,
                    'upload_date' => (string)FusionLiveXmlElement::attr($d, 'uploaded'),
                    'first_version_id' => (int)FusionLiveXmlElement::attr($d, 'firstVersionId'),
                    'reference' => (string)FusionLiveXmlElement::attr($d, 'reference'),
                    'title' => (string)FusionLiveXmlElement::attr($d, 'title'),
                    'revision' => (string)FusionLiveXmlElement::attr($d, 'revision'),
                    'status' => (string)FusionLiveXmlElement::attr($d, 'status'),
                    'version' => (string)FusionLiveXmlElement::attr($d, 'version'),
                    'is_latest' => (FusionLiveXmlElement::attr($d, 'islatest') === 'true'),
                    'has_content' => (FusionLiveXmlElement::attr($d, 'hascontent') === 'true'),
                    'has_link' => (FusionLiveXmlElement::attr($d, 'haslink') === 'true'),
                    'is_link' => (FusionLiveXmlElement::attr($d, 'islink') === 'true'),
                    'is_locked' => (FusionLiveXmlElement::attr($d, 'islocked') === 'true'),
                    'has_markup' => (FusionLiveXmlElement::attr($d, 'hasmarkup') === 'true'),
                    'size' => (int)FusionLiveXmlElement::attr($d, 'size'),
                    'company_name' => (string)FusionLiveXmlElement::attr($d, 'companyname'),
                    'uri' => (string)FusionLiveXmlElement::attr($d, 'uri'),
                    'file' => $d->file ? [
                        'name' => (string)FusionLiveXmlElement::attr($d->file, 'name'),
                        'type' => (string)FusionLiveXmlElement::attr($d->file, 'type'),
                        'mime_type' => (string)FusionLiveXmlElement::attr($d->file, 'mimetype'),
                        'code_format' => (string)FusionLiveXmlElement::attr($d->file, 'contentformatcode'),
                    ] : null,
                    'attachments' => $attachments,
                ];
            }, $el->documents),
        ];
    }

    private function loadAttachments(string $token, int $workspaceId, int $documentId): array
    {
        $payload = "<listattachments mode=\"list\"><authentication token=\"{$token}\"/><workspace id=\"{$workspaceId}\"/><document id=\"{$documentId}\"/></listattachments>";
        $result = $this->post('/pws/folders', $payload);

        if ($result['code'] != 0) {
            return [];
        }

        $el = new FusionLiveXmlElement($result['data'], [
            'documents' => [
                'xpath' => '/status/documents/document',
                'many' => true
            ],
        ]);

        return array_map(function (\SimpleXMLElement $d) use ($token, $workspaceId) {

            $attachments = [];
            $docId = (int)FusionLiveXmlElement::attr($d, 'id');
            $hasAttachments = (FusionLiveXmlElement::attr($d, 'hasattachment') === 'true');

            if ($hasAttachments) {
                $attachments = $this->loadAttachments($token, $workspaceId, $docId);
            }
            return [
                'id' => $docId,
                'first_version_id' => (int)FusionLiveXmlElement::attr($d, 'firstVersionId'),
                'reference' => (string)FusionLiveXmlElement::attr($d, 'reference'),
                'title' => (string)FusionLiveXmlElement::attr($d, 'title'),
                'revision' => (string)FusionLiveXmlElement::attr($d, 'revision'),
                'status' => (string)FusionLiveXmlElement::attr($d, 'status'),
                'version' => (string)FusionLiveXmlElement::attr($d, 'version'),
                'is_latest' => (FusionLiveXmlElement::attr($d, 'islatest') === 'true'),
                'has_content' => (FusionLiveXmlElement::attr($d, 'hascontent') === 'true'),
                'has_link' => (FusionLiveXmlElement::attr($d, 'haslink') === 'true'),
                'is_link' => (FusionLiveXmlElement::attr($d, 'islink') === 'true'),
                'is_locked' => (FusionLiveXmlElement::attr($d, 'islocked') === 'true'),
                'has_markup' => (FusionLiveXmlElement::attr($d, 'hasmarkup') === 'true'),
                'size' => (int)FusionLiveXmlElement::attr($d, 'size'),
                'company_name' => (string)FusionLiveXmlElement::attr($d, 'companyname'),
                'uri' => (string)FusionLiveXmlElement::attr($d, 'uri'),
                'attachments' => $attachments,
            ];
        }, $el->documents);
    }

    private function token(string $username, string $password): string
    {
        $result = $this->authenticate($username, $password);
        return $result ? $result->session : '';
    }

    private function authenticate(string $username, string $password): ?FusionLiveXmlElement
    {
        $result = $this->post('/pws/users', "<authenticate login=\"{$username}\" password=\"{$password}\" />");
        if ($result['code'] === 0) {
            return new FusionLiveXmlElement($result['data'], [
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
