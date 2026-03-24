<?php
/**
 * SpatieUrlUrlDynamicMethodReturnTypeExtension.php
 */

namespace PiecesPHP\Core\PHPStan\Classes\Spatie\Url\Url;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * SpatieUrlUrlDynamicMethodReturnTypeExtension.
 *
 * Resolución dinámica de tipos para métodos de instancia de Spatie\Url\Url
 *
 * @package PiecesPHP\Core\PHPStan\Classes\Spatie\Url\Url
 */
class SpatieUrlUrlDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return \Spatie\Url\Url::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return isset($this->methodMap()[$methodReflection->getName()]);
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
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
     * Mapa de resolvers
     */
    private function methodMap(): array
    {
        $self = new ObjectType(\Spatie\Url\Url::class);

        return [
            'withQueryParameter' => fn() => $self,
        ];
    }
}
