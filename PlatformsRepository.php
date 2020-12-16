<?php

namespace OKNManager\BM\Repositories;

use App\Models\Platform;

/**
 * Repository class of Platform Business Model
 */
class PlatformsRepository
{

    /**
     * Function to return platforms id's in base a platform code array or all string
     * Data must be correct at this point because it's previously validated by BM validator.
     *
     * @param mixed $platforms must be platforms codes array or all string.
     *
     * @return array $platformsID platform id's array.
     */
    public function getPlatformsIdsByCodeOrAll($platforms):array
    {
        if ($platforms === 'all') {
            $platformsID = Platform::all()->pluck('id', 'code')->toArray();

            return $platformsID;
        }

        $platformsID = Platform::whereIn('code', $platforms)->get()->pluck('id', 'code')->toArray();

        return $platformsID;
    }
    /**
     * Checks if the platformCode specified exists
     *
     * @param string $platformCode The platform code to check
     *
     * @return bool
     */
    public function existsPlatformCode(string $platformCode):bool
    {
        $exists = Platform::where('code', $platformCode)->exists();

        return $exists;
    }

    /**
     * Checks if the list of platformCodes specified exists
     *
     * @param array $platformCodes The list of platforms codes
     *
     * @return array An associative array, by platformCode, indicating if the platformCode exists or not
     */
    public function existsPlatformCodes(array $platformCodes):array
    {
        $existing = Platform::whereIn('code', $platformCodes)->pluck('code')->all();
        $return = [];
        \array_map(function ($platformCode) use (&$return, $existing) {
            $return[$platformCode] = \in_array($platformCode, $existing);
        }, $platformCodes);

        return $return;
    }
}
