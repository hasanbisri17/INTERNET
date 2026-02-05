<?php

namespace App\GPT\Functions;

/**
 * Base class for all GPT Functions
 * 
 * This class provides the foundation for creating custom functions
 * that can be called by AI models via OpenRouter API
 */
abstract class BaseFunction
{
    /**
     * Get the function name (must be unique)
     */
    abstract public function name(): string;

    /**
     * Get the function description (used by AI to understand when to call this function)
     */
    abstract public function description(): string;

    /**
     * Get the function parameters schema
     * Should return an array compatible with JSON Schema
     */
    abstract public function parameters(): array;

    /**
     * Execute the function with given arguments
     * 
     * @param array $arguments The arguments passed by AI
     * @return array The result of the function execution
     */
    abstract public function execute(array $arguments): array;

    /**
     * Get required parameters (optional override)
     */
    protected function requiredParameters(): array
    {
        return [];
    }

    /**
     * Convert function to OpenRouter format
     */
    public function toOpenRouterFormat(): array
    {
        $properties = $this->parameters();
        $required = $this->requiredParameters();

        return [
            'name' => $this->name(),
            'description' => $this->description(),
            'parameters' => [
                'type' => 'object',
                'properties' => $properties,
                'required' => $required,
            ],
        ];
    }

    /**
     * Validate arguments before execution
     */
    protected function validateArguments(array $arguments, array $required = []): bool
    {
        foreach ($required as $param) {
            if (!isset($arguments[$param])) {
                return false;
            }
        }
        return true;
    }
}

