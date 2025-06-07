<?php

namespace App\Http\Middleware;

use App\Helpers\ParserHelper;
use App\Models\Enum\ChainType;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Env;

class DeployMiddleware
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
        $arguments = $request->input('arguments');
        $chain = $request->input('chain', ChainType::MAIN->value);

        $serviceAddress = match ($chain) {
            ChainType::MAIN->value => Env::get('STX_MAIN_ADDRESS', 'SP39DTEJFPPWA3295HEE5NXYGMM7GJ8MA0TQX379'),
            ChainType::TEST->value => Env::get('STX_TEST_ADDRESS', 'ST2FCRYHEYX9EPHX7QE7HH7RQ2S2XXA9WX1FVV26P'),
            default => Env::get('STX_MAIN_ADDRESS', 'SP39DTEJFPPWA3295HEE5NXYGMM7GJ8MA0TQX379'),
        };

        $arguments['serviceAddress'] = $serviceAddress;
        $arguments['deployCost'] = $this->calculateDeployCost($arguments);

        if (!$arguments['removeWatermark']) {
            $request->attributes->set('contractName', ParserHelper::parse("<% name|kebabCase %>-tokenfactory", $arguments));
        } else {
            $request->attributes->set('contractName', ParserHelper::parse("<% name|kebabCase %>", $arguments));
        }

        $request->merge(['arguments' => $arguments]);

        return $next($request);
    }

    private function calculateDeployCost(array $arguments): int
    {
        $cost = 10;

        if (isset($arguments['mintable']) && $arguments['mintable']) {
            $cost += 5;
            if (!$arguments['mintFixedAmount']) {
                $cost += 3;
            }
            if (!$arguments['allowMintToAll']) {
                $cost += 2;
            }
        }

        if (isset($arguments['burnable']) && $arguments['burnable']) {
            $cost += 5;
        }

        if ($arguments['removeWatermark']) {
            $cost += 5;
        }

        return $cost * 1000000; // Convert to uSTX
    }
}
