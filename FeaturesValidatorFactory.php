<?php

namespace OKNManager\BM\Validators\Features;

use \OKNManager\BM\Validators\BaseValidatorFactory;

/**
 * Class to create an action validator of the Features business model
 */
class FeaturesValidatorFactory extends BaseValidatorFactory
{
    /**
     * Validation Error code if the action validator was not found
     */
    const VALIDATION_ERROR_CODE = [
        'code' => '999005008',
        'title' => 'Features action validator not found',
        'description' => 'The action validator `{{actionName}}` was not found'
    ];
    
    /**
     * {@inheritDoc}
     */
    protected function resolveActionValidatorClassName(string $actionName): string
    {
        $actionValidatorClass = __NAMESPACE__.'\Actions\\'.$actionName.'Validator';

        return $actionValidatorClass;
    }
}
