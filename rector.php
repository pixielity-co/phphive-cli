<?php

/**
 * Rector Configuration - Production Ready.
 *
 * Automated refactoring and code modernization for the Pixielity Framework.
 * This configuration is optimized for production use with comprehensive
 * rule sets and proper exclusions.
 *
 * @see https://github.com/rectorphp/rector
 * @see https://getrector.com/documentation
 *
 * Usage:
 *   Preview changes:  composer rector
 *   Apply changes:    composer rector:fix
 *   Specific path:    vendor/bin/rector process src/PackageA --dry-run
 *
 * @version 2.0.0
 *
 * @author Pixielity Development Team
 */

declare(strict_types=1);

use Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector;
use Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Privatization\Rector\ClassMethod\PrivatizeFinalClassMethodRector;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ParamTypeByMethodCallTypeRector;

return RectorConfig::configure()
    // =========================================================================
    // PATHS CONFIGURATION
    // =========================================================================
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/bin',
    ])
    // =========================================================================
    // CACHE CONFIGURATION
    // =========================================================================
    ->withCache(__DIR__ . '/build/rector')
    // =========================================================================
    // SKIP CONFIGURATION
    // =========================================================================
    ->withSkip([
        // =====================================================================
        // PATHS TO SKIP
        // =====================================================================
        // Third-party dependencies
        '*/vendor/*',
        __DIR__ . '/vendor',
        // Storage and cache
        '*/storage/*',
        '*/cache/*',
        '*/bootstrap/cache/*',
        // Build artifacts
        '*/build/*',
        '*/dist/*',
        '*/var/*',
        // Test fixtures (intentionally old code)
        '*/tests/Fixtures/*',
        '*/tests/fixtures/*',
        '*/tests/Stubs/*',
        '*/tests/stubs/*',
        // Generated files
        '*/_ide_helper.php',
        '*/_ide_helper_models.php',
        '*/ide-helper.php',
        // =====================================================================
        // RULES TO SKIP
        // =====================================================================
        // Don't convert string interpolation to sprintf (Laravel convention)
        EncapsedStringsToSprintfRector::class,
        // Don't force newlines after statements (formatting handled by Pint)
        NewlineAfterStatementRector::class,
        // Don't add string casts when Filesystem service already returns string
        // PHPStan knows the type, so casts are useless and cause errors
        NullToStrictStringFuncCallArgRector::class,
        // =====================================================================
        // RULES TO SKIP FOR SPECIFIC PATHS
        // =====================================================================
        // Don't make properties readonly in DTOs (they need to be mutable)
        ReadOnlyPropertyRector::class => [
            '*/Data/*',
            '*/DTO/*',
            '*/Dtos/*',
            '*/DataTransferObjects/*',
        ],
        // Don't remove "unused" properties in Models (they're for frameworks)
        RemoveUnusedPrivatePropertyRector::class => [
            '*/Models/*',
        ],
        // Don't remove "unused" methods in Observers (lifecycle hooks)
        RemoveUnusedPrivateMethodRector::class => [
            '*/Observers/*',
        ],
        // Don't add void return types to magic methods
        AddVoidReturnTypeWhereNoReturnRector::class => [
            '*/Concerns/*',
        ],
        // Don't add parameter types to __call magic method
        ParamTypeByMethodCallTypeRector::class => [
            '*/Concerns/*',
            '*/Traits/*',
        ],
        // Don't privatize trait methods
        PrivatizeFinalClassMethodRector::class => [
            '*/Traits/*',
        ],
    ])
    // =========================================================================
    // PHP VERSION TARGET
    // =========================================================================
    ->withPhpSets(
        php84: true  // Target PHP 8.4 features
    )
    // =========================================================================
    // RULE SETS - COMPREHENSIVE PRODUCTION CONFIGURATION
    // =========================================================================
    ->withSets([
        // =====================================================================
        // CODE QUALITY IMPROVEMENTS
        // =====================================================================

        /*
         * Dead Code Removal
         *
         * Removes code that has no effect:
         * - Unused private methods and properties
         * - Unused imports and variables
         * - Unreachable code after return/throw
         * - Empty methods and blocks
         * - Unused parameters
         * - Dead conditions
         */
        SetList::DEAD_CODE,

        /*
         * Code Quality Improvements
         *
         * Improves code quality and readability:
         * - Simplify boolean expressions
         * - Use null coalescing operator
         * - Combine consecutive assignments
         * - Simplify array functions
         * - Inline single-use variables
         * - Remove unnecessary parentheses
         * - Simplify ternary operators
         * - Use spaceship operator
         */
        SetList::CODE_QUALITY,

        /*
         * Coding Style Consistency
         *
         * Applies consistent coding style:
         * - Short array syntax []
         * - Consistent string quotes
         * - Consistent null comparison
         * - Consistent boolean naming
         * - Remove unnecessary semicolons
         * - Consistent use of strict comparison
         */
        SetList::CODING_STYLE,

        /*
         * Early Return Pattern
         *
         * Refactors nested conditions to use early returns:
         * - Reduces nesting levels
         * - Improves readability
         * - Makes code flow clearer
         * - Reduces cognitive complexity
         */
        SetList::EARLY_RETURN,

        /*
         * Privatization
         *
         * Makes class members as private as possible:
         * - Changes public to protected if only used in class
         * - Changes protected to private if only used in class
         * - Makes methods final if not overridden
         * - Reduces public API surface
         */
        SetList::PRIVATIZATION,

        /*
         * Type Declarations
         *
         * Adds missing type declarations:
         * - Parameter types
         * - Return types
         * - Property types
         * - Void return types
         * - Mixed types where needed
         * - Union and intersection types
         */
        SetList::TYPE_DECLARATION,

        /*
         * Naming Conventions
         *
         * Improves naming conventions:
         * - Descriptive variable names
         * - Boolean method names (is*, has*, should*)
         * - Getter/setter standardization
         * - Remove Hungarian notation
         */
        SetList::NAMING,

        /*
         * Instanceof Checks
         *
         * Optimizes instanceof checks:
         * - Removes redundant instanceof checks
         * - Simplifies type checking logic
         * - Improves performance
         */
        SetList::INSTANCEOF,
    ])
    // =========================================================================
    // IMPORT NAMES CONFIGURATION
    // =========================================================================
    ->withImportNames(
        importNames: true,  // Add use statements
        importDocBlockNames: true,  // Import in PHPDoc
        importShortClasses: false,  // Don't import short names (App, User, etc.)
        removeUnusedImports: true,  // Remove unused imports
    )
    // =========================================================================
    // PARALLEL PROCESSING - OPTIMIZED FOR PRODUCTION
    // =========================================================================
    ->withParallel(
        timeoutSeconds: 300,  // 5 minutes timeout (increased for large codebases)
        maxNumberOfProcess: 8,  // 8 parallel processes (balanced for stability)
        jobSize: 15,  // 15 files per job (optimized for balance)
    )
    // =========================================================================
    // ADDITIONAL CONFIGURATION
    // =========================================================================
    /*
     * File Extensions
     *
     * Process only PHP files.
     */
    ->withFileExtensions(['php'])
    /*
     * Root Files
     *
     * Include root-level PHP files.
     */
    ->withRootFiles()
    /*
     * Memory Limit
     *
     * Increase memory limit for large codebases.
     */
    ->withMemoryLimit('2G');
