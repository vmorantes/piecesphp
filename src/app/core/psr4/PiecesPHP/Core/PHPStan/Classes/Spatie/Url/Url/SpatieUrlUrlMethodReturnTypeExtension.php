<?php
/**
 * SpatieUrlUrlMethodReturnTypeExtension.php
 */

namespace PiecesPHP\Core\PHPStan\Classes\Spatie\Url\Url;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * SpatieUrlUrlMethodReturnTypeExtension
 *
 * @package PiecesPHP\Core\PHPStan\Classes\Spatie\Url\Url
 */
class SpatieUrlUrlMethodReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return \Spatie\Url\Url::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return isset($this->methodMap()[$methodReflection->getName()]);
    }

    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope
    ): Type {

        $method = $methodReflection->getName();

        $resolver = $this->methodMap()[$method] ?? null;

        if ($resolver === null) {
            return new MixedType();
        }

        return $resolver($methodCall, $scope);
    }

    /**
     * Mapa de resolvers por método
     *
     * Aquí agregas métodos sin tocar lógica base
     */
    private function methodMap(): array
    {
        return [
            'fromString' => fn() => new ObjectType(\Spatie\Url\Url::class),
        ];
    }
}
