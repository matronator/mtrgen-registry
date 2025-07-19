<?php

namespace App\Http\Controllers;

use App\Helpers\BasicResponse;
use App\Helpers\ParserHelper;
use App\Models\Account;
use App\Models\Contract;
use App\Models\Enum\ContractType;
use Appwrite\Client;
use Appwrite\ID;
use Appwrite\InputFile;
use Appwrite\Permission;
use Appwrite\Role;
use Appwrite\Services\Storage;
use Appwrite\Services\Tokens;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Matronator\Mtrgen\Template\Generator;

class GeneratorController extends Controller
{
    public function generate(Request $request)
    {
        $this->validate($request, [
            'template' => 'required|string',
            'params' => 'required|array',
        ]);

        $template = $request->input('template');
        $params = $request->input('params');

        $file = static::virtualFile($template);

        $fileObject = Generator::parseAnyFile($file, $params);

        return response()->json([
            'template' => $template,
            'generated' => $fileObject->contents,
            'filename' => $fileObject->filename,
        ], 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function clarityTokenContract(Request $request, Client $client)
    {
        $this->validate($request, [
            'arguments' => 'required|array',
        ]);

        $arguments = $request->input('arguments');
        $contractName = $request->attributes->get('contractName', $arguments['name'] . ($arguments['removeWatermark'] ? '' : '-tokenfactory'));
        $originalName = $request->attributes->get('originalName', $arguments['name'] . ($arguments['removeWatermark'] ? '' : ' by TokenFactory'));
        $tokenMetadataArray = [];

        if (!$arguments['selfHostMetadata']) {
            $tokenMetadata = $arguments['tokenMetadata'];
            $tokenMetadataArray = [
                'name' => $originalName,
                'description' => $tokenMetadata['description'] .
                    ($arguments['removeWatermark'] ? '' : ' - Token created by Token Factory (https://factory.matronator.cz)'),
                'image' => $tokenMetadata['image'],
            ];

            $storage = new Storage($client);
            $bucketId = env('APPWRITE_BUCKET_ID');
            $projectId = env('APPWRITE_PROJECT_ID');
            $endpoint = env('APPWRITE_ENDPOINT');

            // Create a metadata file and upload it to Appwrite storage
            $metadataFile = $storage->createFile(
                $bucketId,
                ID::unique(),
                InputFile::withData(
                    json_encode(
                        $tokenMetadataArray,
                        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                    ),
                    'application/json',
                    $contractName . '-' . ID::unique() . '.json'),
                [Permission::read(Role::any())]
            );

            $tokens = new Tokens($client);

            // Create a file token to allow public access to the metadata file
            $fileToken = $tokens->createFileToken($bucketId, $metadataFile['$id']);

            // Get the URI to access the metadata file using the file token
            $metadataFileUrl = "$endpoint/storage/buckets/$bucketId/files/{$metadataFile['$id']}/view?project=$projectId&token={$fileToken['secret']}";

            $arguments['tokenUri'] = $metadataFileUrl;
        }

        try {
            $contract = static::createContract($arguments);
        } catch (\Exception $e) {
            Log::debug($e);
            return BasicResponse::send('There was an error when trying to create a contract.', BasicResponse::STATUS_ERROR, 500);
        }

        $data = [
            'name' => $contractName,
            'originalName' => $originalName,
            'body' => $contract,
        ];

        if (!$arguments['selfHostMetadata']) {
            $data['tokenUri'] = $arguments['tokenUri'];
            $data['tokenMetadata'] = $tokenMetadataArray;
        }

        return response()->json($data, 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function deployContract(Request $request)
    {
        $this->validate($request, [
            'id_address' => 'required|string|alpha_num',
            'stx_address' => 'required|string|alpha_num',
            'stx_testnet_address' => 'nullable|string|alpha_num',
            'btc_address' => 'required|string|alpha_num',
            'arguments' => 'required|array',
            'network' => 'required|in:mainnet,testnet',
            'contract_address' => 'required|string|alpha_num',
        ]);

        $arguments = $request->input('arguments');
        $network = $request->input('network');
        $address = $request->input('contract_address');

        try {
            $contract = static::createContract($arguments);
        } catch (\Exception $e) {
            Log::debug($e);
            return BasicResponse::send('There was an error when trying to create a contract.', BasicResponse::STATUS_ERROR, 500);
        }

        $account = $request->attributes->get('account');

        Contract::query()->create([
            'account_id' => $account->id,
            'network' => $network,
            'address' => $address,
            'contract' => $contract,
        ]);

        return BasicResponse::send('Success!', BasicResponse::STATUS_SUCCESS, 200);
    }

    public static function virtualFile(string $content): string
    {
       return 'data://text/plain,' . urlencode($content);
    }

    public static function createContract(array $arguments): string
    {
        $template = ParserHelper::getTemplate(ParserHelper::getFilenameFromType(ContractType::TOKEN));
        $parsed = trim(ParserHelper::parse($template, $arguments));
        while (str_contains($parsed, "\n\n\n")) {
            $parsed = str_replace("\n\n\n", "\n\n", $parsed);
        }
        return $parsed;
    }
}
