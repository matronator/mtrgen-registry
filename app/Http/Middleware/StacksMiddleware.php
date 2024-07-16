<?php

namespace App\Http\Middleware;

use App\Helpers\BasicResponse;
use App\Helpers\ErrorCode;
use App\Models\AccessToken;
use App\Models\Account;
use App\Models\User;
use Closure;
use DateTime;
use Illuminate\Http\Request;
use Matronator\C32check\Address;
use Matronator\C32check\Encoding;

class StacksMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $stxAddress = $request->input('stx_address');
        $stxTestnetAddress = $request->input('stx_testnet_address');
        $btcAddress = $request->input('btc_address');
        $idAddress = $request->input('id_address');
        
        if (!$stxAddress || !$idAddress || !$btcAddress) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized access. Please connect with your wallet.', 'error' => ErrorCode::NO_WALLET->value], 401);
        }

        $address = Address::fromC32Address($stxAddress);
        $b58 = $address->toBase58Address();

        if ($b58 !== $btcAddress) {
            return response()->json(['status' => 'error', 'message' => 'STX address doesn\'t match the BTC address.']);
        }

        $account = Account::query()->where('id_address', '=', $idAddress)->where('stx_address', '=', $stxAddress)->first();
        if (!$account) {
            $account = Account::query()->create([
                'id_address' => $idAddress,
                'stx_address' => $stxAddress,
                'stx_testnet_address' => $stxTestnetAddress,
                'btc_address' => $btcAddress,
                'registered_at' => new DateTime(),
            ]);
        }

        $request->attributes->set('account', $account);

        return $next($request);
    }
}
