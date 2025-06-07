<?php

namespace App\Http\Controllers;

use App\Helpers\BasicResponse;
use App\Helpers\ParserHelper;
use App\Models\Account;
use App\Models\Contract;
use App\Models\Enum\ContractType;
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

    public function clarityTokenContract(Request $request)
    {
        $this->validate($request, [
            'arguments' => 'required|array',
        ]);

        $arguments = $request->input('arguments');

        try {
            $contract = static::createContract($arguments);
            $contractName = $request->attributes->get('contractName', $arguments['name'] . $arguments['removeWatermark'] ? '' : '-tokenfactory');
        } catch (\Exception $e) {
            Log::debug($e);
            return BasicResponse::send('There was an error when trying to create a contract.', BasicResponse::STATUS_ERROR, 500);
        }

        $data = [
            'name' => $contractName,
            'body' => $contract,
        ];

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
